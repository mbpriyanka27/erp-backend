<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$body = get_json_body();
$email = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if ($email === '' || $password === '') {
    send_error('Email and password are required', 422);
}

$pdo = get_db_connection();

$stmt = $pdo->prepare(
    'SELECT u.id, u.name, u.email, u.password_hash, u.is_active,
            u.role AS role_name, u.department_id
     FROM users u
     WHERE u.email = :email'
);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// Deliberately identical error for "no such user" and "wrong password"
// so the API doesn't leak which emails exist.
if (!$user || !password_verify($password, $user['password_hash'])) {
    send_error('Invalid email or password', 401);
}

if ((int) $user['is_active'] !== 1) {
    send_error('This account has been deactivated', 403);
}

$tokenData = issue_token($pdo, (int) $user['id']);

$stmt = $pdo->prepare(
    'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
     VALUES (:user_id, :action, :entity_type, :entity_id, :details)'
);
$stmt->execute([
    'user_id' => $user['id'],
    'action' => 'login',
    'entity_type' => 'user',
    'entity_id' => $user['id'],
    'details' => 'Successful login',
]);

send_success([
    'token' => $tokenData['token'],
    'expires_at' => $tokenData['expires_at'],
    'user' => [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role_name'],
        'department_id' => $user['department_id'] !== null ? (int) $user['department_id'] : null,
    ],
]);
