<?php
require dirname(__DIR__) . '/config.php';

$user = requireAuth();
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if ($action === 'create') {
    $payload = requestBody();
    $name = trim((string) ($payload['name'] ?? ''));
    $category = trim((string) ($payload['category'] ?? ''));
    $description = trim((string) ($payload['description'] ?? ''));
    $location = trim((string) ($payload['location'] ?? ''));
    $ownerType = trim((string) ($payload['owner_type'] ?? 'user'));

    if ($name === '' || $category === '') {
        sendJson(['error' => 'Jānorāda mantas nosaukums un kategorija'], 400);
    }

    if (!in_array($ownerType, ['user', 'school'], true)) {
        sendJson(['error' => 'Nederīgs īpašnieka veids'], 400);
    }

    $db = dbConnect('items');
    $stmt = $db->prepare('INSERT INTO items (name, category, description, location, owner_type, owner_user_id, created_by, status) VALUES (:name, :category, :description, :location, :owner_type, :owner_user_id, :created_by, :status)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':category', $category, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':location', $location !== '' ? $location : null, SQLITE3_TEXT);
    $stmt->bindValue(':owner_type', $ownerType, SQLITE3_TEXT);
    $value = ($ownerType === 'user') ? $user['id'] : null;
    if ($value === null) {
        $stmt->bindValue(':owner_user_id', null, SQLITE3_NULL);
    } else {
        $stmt->bindValue(':owner_user_id', $value, SQLITE3_INTEGER);
    }
    $stmt->bindValue(':created_by', $user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':status', 'available', SQLITE3_TEXT);
    $stmt->execute();
    $db->close();

    sendJson(['message' => 'Manta veiksmīgi pievienota katalogam']);
}

if ($action === 'delete') {
    $payload = requestBody();
    $itemId = (int) ($payload['id'] ?? $_GET['id'] ?? 0);
    if ($itemId < 1) {
        sendJson(['error' => 'Nederīgs mantas ID'], 400);
    }

    $db = dbConnect('items');
    $stmt = $db->prepare('SELECT owner_user_id, created_by FROM items WHERE id = :id');
    $stmt->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $item = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$item) {
        $db->close();
        sendJson(['error' => 'Manta nav atrasta'], 404);
    }

    $isAdmin = ($user['role'] ?? '') === 'admin';
    $isOwner = (int) ($item['owner_user_id'] ?? 0) === (int) $user['id']
        || (int) ($item['created_by'] ?? 0) === (int) $user['id'];
    if (!$isAdmin && !$isOwner) {
        $db->close();
        sendJson(['error' => 'Jums nav tiesību dzēst šo mantu'], 403);
    }

    $delete = $db->prepare('DELETE FROM items WHERE id = :id');
    $delete->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $delete->execute();
    $db->close();

    sendJson(['message' => 'Manta veiksmīgi dzēsta']);
}

if ($action === 'list' || $action === null) {
    $search = trim((string) ($_GET['search'] ?? ''));
    $category = trim((string) ($_GET['category'] ?? 'all'));
    $includeUnavailable = filter_var($_GET['include_unavailable'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $db = dbConnect('items');
    $sql = 'SELECT * FROM items WHERE 1=1';
    $params = [];

    if (!$includeUnavailable) {
        $sql .= ' AND status = :available_status';
        $params[':available_status'] = 'available';
    }

    if ($search !== '') {
        $sql .= ' AND (LOWER(name) LIKE :search OR LOWER(description) LIKE :search)';
        $params[':search'] = '%' . strtolower($search) . '%';
    }

    if ($category !== '' && $category !== 'all') {
        $sql .= ' AND category = :category';
        $params[':category'] = $category;
    }

    $sql .= ' ORDER BY created_at DESC';
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, SQLITE3_TEXT);
    }
    $result = $stmt->execute();

    $items = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $items[] = itemPublicData($row);
    }
    $db->close();

    sendJson(['items' => $items]);
}

sendJson(['error' => 'Nezināma darbība'], 400);
