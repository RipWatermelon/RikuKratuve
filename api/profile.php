<?php
require dirname(__DIR__) . '/config.php';

$user = requireAuth();

$action = $_GET['action'] ?? null;

if ($action === 'change-password') {
    $payload = requestBody();
    $oldPassword = (string) ($payload['old_password'] ?? '');
    $newPassword = (string) ($payload['new_password'] ?? '');

    if ($oldPassword === '' || $newPassword === '') {
        sendJson(['error' => 'Both fields are required'], 400);
    }

    $db = dbConnect('users');
    $current = $db->querySingle('SELECT password_hash FROM users WHERE id = ' . (int) $user['id'], true);
    if (!password_verify($oldPassword, $current['password_hash'])) {
        $db->close();
        sendJson(['error' => 'Current password is incorrect'], 400);
    }

    $stmt = $db->prepare('UPDATE users SET password_hash = :new_hash WHERE id = :id');
    $stmt->bindValue(':new_hash', password_hash($newPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();

    sendJson(['message' => 'Password updated successfully']);
}

$profileDb = dbConnect('users');
$notifications = $profileDb->query('SELECT * FROM notifications WHERE user_id = ' . (int) $user['id'] . ' ORDER BY created_at DESC LIMIT 10');
$notificationsList = [];
while ($row = $notifications->fetchArray(SQLITE3_ASSOC)) {
    $notificationsList[] = $row;
}

$historyDb = dbConnect('current_lends');
$history = $historyDb->query('SELECT * FROM lend_requests WHERE borrower_id = ' . (int) $user['id'] . ' ORDER BY requested_at DESC LIMIT 10');
$historyList = [];
while ($row = $history->fetchArray(SQLITE3_ASSOC)) {
    $itemName = 'Item';
    $itemRow = dbConnect('items')->querySingle('SELECT name FROM items WHERE id = ' . (int) $row['item_id'], true);
    if ($itemRow) {
        $itemName = $itemRow['name'];
    }
    $historyList[] = [
        'name' => $itemName,
        'status' => $row['status'],
        'requested_at' => $row['requested_at'],
    ];
}
$historyDb->close();

$ratingsDb = dbConnect('current_lends');
$receivedRatings = $ratingsDb->query('SELECT * FROM request_ratings WHERE rated_user_id = ' . (int) $user['id'] . ' ORDER BY created_at DESC LIMIT 10');
$ratingsList = [];
while ($rating = $receivedRatings->fetchArray(SQLITE3_ASSOC)) {
    $rater = getUserById((int) $rating['rater_id']);
    $ratingsList[] = [
        'score' => (int) $rating['score'],
        'comment' => $rating['comment'],
        'rater_name' => $rater['username'] ?? 'Lietotājs',
        'created_at' => $rating['created_at'],
    ];
}
$ratingsDb->close();

$profileDb->close();

$userProfile = [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role'],
    'group_name' => $user['group_name'],
    'employee_code' => $user['employee_code'],
    'rating' => (float) $user['rating'],
    'notifications' => $notificationsList,
    'history' => $historyList,
    'received_ratings' => $ratingsList,
];

sendJson(['profile' => $userProfile]);
