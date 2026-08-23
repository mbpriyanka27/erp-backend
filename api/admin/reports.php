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
    'SELECT current_status, COUNT(*) AS total
     FROM event_requests
     GROUP BY current_status'
);
$rows = $stmt->fetchAll();

$statusCounts = [];
$totalRequests = 0;
foreach ($rows as $row) {
    $statusCounts[$row['current_status']] = (int) $row['total'];
    $totalRequests += (int) $row['total'];
}

$stmt2 = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = (int) $stmt2->fetchColumn();

send_success([
    'total_requests' => $totalRequests,
    'total_users' => $totalUsers,
    'status_counts' => $statusCounts,
]);