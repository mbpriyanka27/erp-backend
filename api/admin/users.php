<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

if ($user['role_name'] !== 'Admin') {
    send_error('Admin access required', 403);
}

$stmt = $pdo->query(
    'SELECT u.id, u.name, u.email, r.name AS role_name, u.department_id, u.is_active
     FROM users u
     JOIN roles r ON r.id = u.role_id
     ORDER BY u.id ASC'
);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'role_name' => $row['role_name'],
        'department_id' => $row['department_id'] !== null ? (int) $row['department_id'] : null,
        'is_active' => (bool) $row['is_active'],
    ];
}, $rows);

send_success($result);