<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

$eventRequestId = $_GET['event_request_id'] ?? null;

if (!$eventRequestId) {
    send_error('event_request_id is required', 422);
}

// Confirm the request exists
$stmt = $pdo->prepare('SELECT id FROM event_requests WHERE id = :id');
$stmt->execute(['id' => $eventRequestId]);
if (!$stmt->fetch()) {
    send_error('Event request not found', 404);
}

$stmt = $pdo->prepare(
    'SELECT ah.id, ah.action, ah.remarks, ah.created_at,
            u.name AS user_name, r.name AS role_name
     FROM approval_history ah
     JOIN users u ON u.id = ah.user_id
     JOIN roles r ON r.id = ah.role_id
     WHERE ah.event_request_id = :event_request_id
     ORDER BY ah.created_at ASC'
);
$stmt->execute(['event_request_id' => $eventRequestId]);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'action' => $row['action'],
        'remarks' => $row['remarks'],
        'created_at' => $row['created_at'],
        'user_name' => $row['user_name'],
        'role_name' => $row['role_name'],
    ];
}, $rows);

send_success($result);