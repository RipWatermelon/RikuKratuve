<?php
require dirname(__DIR__) . '/config.php';

$user = requireAuth();
$action = $_GET['action'] ?? 'list';
$db = dbConnect('current_lends');

if ($action === 'list') {
    $received = [];
    $stmt = $db->prepare('SELECT * FROM lend_requests WHERE owner_id = :owner_id ORDER BY requested_at DESC');
    $stmt->bindValue(':owner_id', $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $item = dbConnect('items')->querySingle('SELECT name, category FROM items WHERE id = ' . (int) $row['item_id'], true);
        $borrower = getUserById((int) $row['borrower_id']);
        if (!$item || !$borrower) {
            continue;
        }
        $rating = $db->querySingle('SELECT id FROM request_ratings WHERE request_id = ' . (int) $row['id']);
        $received[] = [
            'id' => (int) $row['id'],
            'item_name' => $item['name'],
            'category' => $item['category'],
            'borrower_name' => $borrower['username'],
            'borrower_rating' => (float) $borrower['rating'],
            'reason' => $row['reason'],
            'duration_days' => (int) $row['requested_duration_days'],
            'status' => $row['status'],
            'requested_at' => $row['requested_at'],
            'rated' => (bool) $rating,
        ];
    }

    $pendingRatings = [];
    $result = $db->query('SELECT * FROM lend_requests WHERE owner_id = ' . (int) $user['id'] . ' AND status IN ("approved", "active", "returned") ORDER BY approved_at DESC');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($db->querySingle('SELECT id FROM request_ratings WHERE request_id = ' . (int) $row['id'])) {
            continue;
        }
        $item = dbConnect('items')->querySingle('SELECT name, category FROM items WHERE id = ' . (int) $row['item_id'], true);
        $borrower = getUserById((int) $row['borrower_id']);
        if ($item && $borrower) {
            $pendingRatings[] = [
                'id' => (int) $row['id'],
                'item_name' => $item['name'],
                'category' => $item['category'],
                'borrower_name' => $borrower['username'],
                'reason' => $row['reason'],
                'status' => $row['status'],
            ];
        }
    }

    $borrowed = [];
    $stmt = $db->prepare('SELECT * FROM lend_requests WHERE borrower_id = :borrower_id AND status IN ("approved", "active") ORDER BY approved_at DESC');
    $stmt->bindValue(':borrower_id', $user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $item = dbConnect('items')->querySingle('SELECT name, category, location FROM items WHERE id = ' . (int) $row['item_id'], true);
        if ($item) {
            $borrowed[] = [
                'id' => (int) $row['id'],
                'item_name' => $item['name'],
                'category' => $item['category'],
                'location' => $item['location'],
                'reason' => $row['reason'],
                'duration_days' => (int) $row['requested_duration_days'],
                'status' => $row['status'],
                'approved_at' => $row['approved_at'],
            ];
        }
    }

    $db->close();
    sendJson(['requests' => $received, 'pending_ratings' => $pendingRatings, 'borrowed' => $borrowed]);
}

$payload = requestBody();
$requestId = (int) ($payload['request_id'] ?? 0);
if ($requestId < 1) {
    $db->close();
    sendJson(['error' => 'Nederīgs pieprasījuma ID'], 400);
}

$request = $db->querySingle('SELECT * FROM lend_requests WHERE id = ' . $requestId, true);
if (!$request) {
    $db->close();
    sendJson(['error' => 'Pieprasījums nav atrasts'], 404);
}

if ($action === 'return') {
    if ((int) $request['borrower_id'] !== (int) $user['id']) {
        $db->close();
        sendJson(['error' => 'Jums nav tiesību atgriezt šo mantu'], 403);
    }
    if (!in_array($request['status'], ['approved', 'active'], true)) {
        $db->close();
        sendJson(['error' => 'Šī manta vairs nav aizņemta'], 400);
    }

    $stmt = $db->prepare('UPDATE lend_requests SET status = "returned", return_at = :return_at WHERE id = :id');
    $stmt->bindValue(':return_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':id', $requestId, SQLITE3_INTEGER);
    $stmt->execute();
    $itemsDb = dbConnect('items');
    $itemStatus = $itemsDb->prepare('UPDATE items SET status = "available" WHERE id = :id');
    $itemStatus->bindValue(':id', $request['item_id'], SQLITE3_INTEGER);
    $itemStatus->execute();
    $itemsDb->close();
    $db->close();
    sendJson(['message' => 'Manta veiksmīgi atgriezta']);
}

if ((int) $request['owner_id'] !== (int) $user['id']) {
    $db->close();
    sendJson(['error' => 'Pieprasījums nav atrasts'], 404);
}

if ($action === 'decision') {
    if ($request['status'] !== 'pending') {
        $db->close();
        sendJson(['error' => 'Šis pieprasījums jau ir izskatīts'], 400);
    }
    $decision = $payload['decision'] ?? '';
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        $db->close();
        sendJson(['error' => 'Nederīgs lēmums'], 400);
    }
    $stmt = $db->prepare('UPDATE lend_requests SET status = :status, approved_at = :approved_at WHERE id = :id');
    $stmt->bindValue(':status', $decision, SQLITE3_TEXT);
    $stmt->bindValue(':approved_at', $decision === 'approved' ? date('Y-m-d H:i:s') : null, $decision === 'approved' ? SQLITE3_TEXT : SQLITE3_NULL);
    $stmt->bindValue(':id', $requestId, SQLITE3_INTEGER);
    $stmt->execute();
    $itemsDb = dbConnect('items');
    $itemStatus = $itemsDb->prepare('UPDATE items SET status = :status WHERE id = :id');
    $itemStatus->bindValue(':status', $decision === 'approved' ? 'borrowed' : 'available', SQLITE3_TEXT);
    $itemStatus->bindValue(':id', $request['item_id'], SQLITE3_INTEGER);
    $itemStatus->execute();
    $itemsDb->close();
    $db->close();
    sendJson(['message' => $decision === 'approved' ? 'Pieprasījums apstiprināts' : 'Pieprasījums noraidīts']);
}

if ($action === 'rate') {
    $score = (int) ($payload['score'] ?? 0);
    $comment = trim((string) ($payload['comment'] ?? ''));
    if (!in_array($request['status'], ['approved', 'active', 'returned'], true) || !in_array($score, [1, 5], true) || $comment === '') {
        $db->close();
        sendJson(['error' => 'Izvēlieties vērtējumu un pievienojiet komentāru'], 400);
    }
    if ($db->querySingle('SELECT id FROM request_ratings WHERE request_id = ' . $requestId)) {
        $db->close();
        sendJson(['error' => 'Šis pieprasījums jau ir novērtēts'], 409);
    }
    $stmt = $db->prepare('INSERT INTO request_ratings (request_id, rater_id, rated_user_id, score, comment) VALUES (:request_id, :rater_id, :rated_user_id, :score, :comment)');
    $stmt->bindValue(':request_id', $requestId, SQLITE3_INTEGER);
    $stmt->bindValue(':rater_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':rated_user_id', $request['borrower_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':score', $score, SQLITE3_INTEGER);
    $stmt->bindValue(':comment', $comment, SQLITE3_TEXT);
    $stmt->execute();
    $average = $db->querySingle('SELECT AVG(score) FROM request_ratings WHERE rated_user_id = ' . (int) $request['borrower_id']);
    $usersDb = dbConnect('users');
    $update = $usersDb->prepare('UPDATE users SET rating = :rating WHERE id = :id');
    $update->bindValue(':rating', $average ?: 5, SQLITE3_FLOAT);
    $update->bindValue(':id', $request['borrower_id'], SQLITE3_INTEGER);
    $update->execute();
    $usersDb->close();
    $db->close();
    sendJson(['message' => 'Vērtējums saglabāts']);
}

$db->close();
sendJson(['error' => 'Nezināma darbība'], 400);
