<?php
session_start();

const APP_ROOT = __DIR__;
const DATA_DIR = APP_ROOT . DIRECTORY_SEPARATOR . 'data';

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

function dbConnect(string $dbName): SQLite3
{
    $path = DATA_DIR . DIRECTORY_SEPARATOR . $dbName . '.sqlite';
    $db = new SQLite3($path, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $db->exec('PRAGMA foreign_keys = ON;');
    $db->exec('PRAGMA journal_mode = WAL;');
    return $db;
}

function ensureSchema(): void
{
    $userDb = dbConnect('users');
    $userDb->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ("student","teacher","admin")),
        group_name TEXT,
        employee_code TEXT,
        rating REAL NOT NULL DEFAULT 5,
        access_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );');

    $userDb->exec('CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        channel TEXT NOT NULL DEFAULT "push",
        is_read INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    );');

    $userDb->exec('CREATE TABLE IF NOT EXISTS rating_events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        score INTEGER NOT NULL,
        reason TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    );');

    $userDb->exec('CREATE TABLE IF NOT EXISTS user_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    );');

    $userDb->close();

    $itemsDb = dbConnect('items');
    $tableInfo = $itemsDb->querySingle("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'items'");
    if ($tableInfo !== null && str_contains(strtolower((string) $tableInfo), 'references users')) {
        $itemsDb->exec('DROP TABLE IF EXISTS items;');
    }
    $itemsDb->exec('CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        description TEXT,
        location TEXT,
        owner_type TEXT NOT NULL CHECK(owner_type IN ("user","school")),
        owner_user_id INTEGER,
        created_by INTEGER,
        status TEXT NOT NULL DEFAULT "available" CHECK(status IN ("available","borrowed","requested")),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );');
    $itemsDb->close();

    $lendsDb = dbConnect('current_lends');
    $lendSql = $lendsDb->querySingle("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'lend_requests'");
    if ($lendSql !== null && str_contains(strtolower((string) $lendSql), 'references users')) {
        $lendsDb->exec('DROP TABLE IF EXISTS lend_requests;');
    }
    $lendsDb->exec('CREATE TABLE IF NOT EXISTS lend_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        borrower_id INTEGER NOT NULL,
        owner_id INTEGER,
        requested_duration_days INTEGER NOT NULL,
        reason TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT "pending" CHECK(status IN ("pending","approved","rejected","returned","active")),
        requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at TEXT,
        due_at TEXT,
        return_at TEXT,
        location_hidden INTEGER NOT NULL DEFAULT 0
    );');
    $lendsDb->exec('CREATE TABLE IF NOT EXISTS request_ratings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        request_id INTEGER NOT NULL UNIQUE,
        rater_id INTEGER NOT NULL,
        rated_user_id INTEGER NOT NULL,
        score INTEGER NOT NULL CHECK(score IN (1, 5)),
        comment TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );');
    $lendsDb->close();

    $usersDb = dbConnect('users');
    $adminCheck = $usersDb->querySingle("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $usersDb->close();
    if ((int) $adminCheck === 0) {
        $adminDb = dbConnect('users');
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $adminDb->exec("INSERT INTO users (username, email, password_hash, role, group_name, employee_code, rating, access_active) VALUES ('admin', 'admin@rikukratuve.lv', '$adminHash', 'admin', 'Administration', 'ADMIN-01', 5, 1)");
        $adminDb->close();
    }
}

ensureSchema();

function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $db = dbConnect('users');
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $db->close();

    return $user ?: null;
}

function requireAuth(): array
{
    $user = currentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    return $user;
}

function requestBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_REQUEST;
}

function sendJson(mixed $payload, int $statusCode = 200): void
{
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($statusCode);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function fuzzyMatch(string $query, string $value): bool
{
    $needle = strtolower(trim($query));
    $haystack = strtolower(trim($value));
    if ($needle === '') {
        return true;
    }

    if (str_contains($haystack, $needle)) {
        return true;
    }

    $parts = preg_split('/\s+/', $needle);
    foreach ($parts as $part) {
        if ($part !== '' && str_contains($haystack, $part)) {
            return true;
        }
    }

    return false;
}

function userCanBorrowItem(array $user, array $item): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }

    if ($user['role'] === 'teacher') {
        return true;
    }

    return strtolower($item['category']) === 'grāmatas' || strtolower($item['category']) === 'books';
}

function addNotification(int $userId, string $title, string $message, string $channel = 'push'): void
{
    $db = dbConnect('users');
    $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, channel) VALUES (:user_id, :title, :message, :channel)');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->bindValue(':channel', $channel, SQLITE3_TEXT);
    $stmt->execute();
    $db->close();
}

function getUserById(int $userId): ?array
{
    $db = dbConnect('users');
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $db->close();
    return $result ?: null;
}

function itemLocationForCurrentUser(array $item): string
{
    return $item['location'] ?? 'Nav norādīta atrašanās vieta';
}

function itemPublicData(array $item): array
{
    $location = itemLocationForCurrentUser($item);
    $user = currentUser();
    $hasRequested = false;
    if ($user) {
        $lendsDb = dbConnect('current_lends');
        $stmt = $lendsDb->prepare('SELECT 1 FROM lend_requests WHERE item_id = :item_id AND borrower_id = :borrower_id LIMIT 1');
        $stmt->bindValue(':item_id', $item['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':borrower_id', $user['id'], SQLITE3_INTEGER);
        $hasRequested = (bool) $stmt->execute()->fetchArray(SQLITE3_NUM);
        $lendsDb->close();
    }

    return [
        'id' => (int) $item['id'],
        'name' => $item['name'],
        'category' => $item['category'],
        'description' => $item['description'],
        'location' => $location,
        'status' => $item['status'],
        'owner_type' => $item['owner_type'],
        'owner_user_id' => $item['owner_user_id'],
        'created_by' => $item['created_by'],
        'has_requested' => $hasRequested,
    ];
}
