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
    'SELECT id, name, code
     FROM departments
     ORDER BY id ASC'
);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'code' => $row['code'],
    ];
}, $rows);

send_success($result);