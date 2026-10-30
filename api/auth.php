<?php
require dirname(__DIR__) . '/config.php';

header('Cache-Control: no-store');

$action = $_GET['action'] ?? null;

if ($action === 'me') {
    $user = currentUser();
    if (!$user) {
        sendJson(['error' => 'Unauthorized'], 401);
    }
    sendJson(['user' => $user]);
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    sendJson(['message' => 'Logged out']);
}

if ($action === 'login') {
    $payload = requestBody();
    $username = trim((string) ($payload['username'] ?? ''));
    $password = (string) ($payload['password'] ?? '');

    if ($username === '' || $password === '') {
        sendJson(['error' => 'Username and password are required'], 400);
    }

    $db = dbConnect('users');
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username OR email = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    $db->close();

    if (!$result || !password_verify($password, $result['password_hash'])) {
        sendJson(['error' => 'Invalid credentials'], 401);
    }

    $_SESSION['user_id'] = (int) $result['id'];
    sendJson(['message' => 'Login successful', 'user' => $result]);
}

if ($action === 'register') {
    $payload = requestBody();
    $username = trim((string) ($payload['username'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $password = (string) ($payload['password'] ?? '');
    $role = (string) ($payload['role'] ?? 'student');
    $groupName = trim((string) ($payload['group_name'] ?? ''));
    $employeeCode = trim((string) ($payload['employee_code'] ?? ''));

    if ($username === '' || $email === '' || $password === '') {
        sendJson(['error' => 'All required fields must be filled'], 400);
    }

    if (!in_array($role, ['student', 'teacher', 'admin'], true)) {
        sendJson(['error' => 'Invalid user role'], 400);
    }

    if ($role === 'student' && $groupName === '') {
        sendJson(['error' => 'Students must provide a group'], 400);
    }

    if ($role === 'teacher' && $employeeCode === '') {
        sendJson(['error' => 'Teachers must provide an employee code'], 400);
    }

    $db = dbConnect('users');
    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, role, group_name, employee_code) VALUES (:username, :email, :password_hash, :role, :group_name, :employee_code)');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':group_name', $groupName !== '' ? $groupName : null, SQLITE3_TEXT);
    $stmt->bindValue(':employee_code', $employeeCode !== '' ? $employeeCode : null, SQLITE3_TEXT);
    try {
        $stmt->execute();
    } catch (Throwable $e) {
        $db->close();
        sendJson(['error' => 'Username or email already exists'], 409);
    }

    $db->close();
    sendJson(['message' => 'Registration successful. You can log in now.']);
}

sendJson(['error' => 'Unknown action'], 400);
