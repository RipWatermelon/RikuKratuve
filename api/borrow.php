<?php
require dirname(__DIR__) . '/config.php';

$user = requireAuth();
$action = $_GET['action'] ?? null;

if ($action === 'request') {
    $payload = requestBody();
    $itemId = (int) ($payload['item_id'] ?? 0);
    $duration = (int) ($payload['duration_days'] ?? 0);
    $reason = trim((string) ($payload['reason'] ?? ''));

    if ($itemId <= 0 || $duration <= 0 || $reason === '') {
        sendJson(['error' => 'Jānorāda manta, izmantošanas ilgums un iemesls'], 400);
    }

    $itemsDb = dbConnect('items');
    $item = $itemsDb->querySingle('SELECT * FROM items WHERE id = ' . $itemId, true);
    $itemsDb->close();
    if (!$item) {
        sendJson(['error' => 'Manta nav atrasta'], 404);
    }

    if ((int) ($item['created_by'] ?? 0) === (int) $user['id']) {
        sendJson(['error' => 'Jūs nevarat nosūtīt aizņemšanās pieprasījumu savai mantai'], 403);
    }

    $ownerId = null;
    if (($item['owner_type'] ?? 'user') === 'school') {
        $ownerId = getUserById((int) $item['created_by'])['id'] ?? null;
    } else {
        $ownerId = (int) ($item['owner_user_id'] ?? 0);
    }

    $db = dbConnect('current_lends');
    $db->exec('BEGIN IMMEDIATE');
    $duplicate = $db->prepare('SELECT 1 FROM lend_requests WHERE item_id = :item_id AND borrower_id = :borrower_id LIMIT 1');
    $duplicate->bindValue(':item_id', $itemId, SQLITE3_INTEGER);
    $duplicate->bindValue(':borrower_id', $user['id'], SQLITE3_INTEGER);
    if ($duplicate->execute()->fetchArray(SQLITE3_NUM)) {
        $db->exec('ROLLBACK');
        $db->close();
        sendJson(['error' => 'Jūs jau esat nosūtījis pieprasījumu šai mantai'], 409);
    }

    $stmt = $db->prepare('INSERT INTO lend_requests (item_id, borrower_id, owner_id, requested_duration_days, reason, status, location_hidden) VALUES (:item_id, :borrower_id, :owner_id, :requested_duration_days, :reason, :status, :location_hidden)');
    $stmt->bindValue(':item_id', $itemId, SQLITE3_INTEGER);
    $stmt->bindValue(':borrower_id', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':owner_id', $ownerId, SQLITE3_INTEGER);
    $stmt->bindValue(':requested_duration_days', $duration, SQLITE3_INTEGER);
    $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);

    if (($item['owner_type'] ?? 'user') === 'school') {
        $stmt->bindValue(':status', 'approved', SQLITE3_TEXT);
        $stmt->bindValue(':location_hidden', 0, SQLITE3_INTEGER);
        $message = 'Pieprasījums automātiski apstiprināts, jo manta pieder skolai.';
    } else {
        $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
        $stmt->bindValue(':location_hidden', 0, SQLITE3_INTEGER);
        $message = 'Pieprasījums gaida īpašnieka apstiprinājumu.';
    }

    $stmt->execute();
    $db->exec('COMMIT');
    $db->close();

    if (($item['owner_type'] ?? 'user') !== 'school') {
        $recipient = getUserById((int) $ownerId);
        if ($recipient) {
            addNotification((int) $recipient['id'], 'Jauns aizņemšanās pieprasījums', 'Lietotājs ' . $user['username'] . ' pieprasīja jūsu mantu: ' . $item['name'], 'push');
        }
    }

    sendJson(['message' => $message, 'status' => 'ok']);
}

sendJson(['error' => 'Nezināma darbība'], 400);
