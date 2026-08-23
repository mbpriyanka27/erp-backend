<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

$stmt = $pdo->prepare(
    'SELECT id, title, level, current_status, event_date, budget, approval_id, created_at
     FROM event_requests
     WHERE organizer_id = :organizer_id
     ORDER BY created_at DESC'
);
$stmt->execute(['organizer_id' => (int) $user['id']]);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'level' => $row['level'],
        'current_status' => $row['current_status'],
        'event_date' => $row['event_date'],
        'budget' => $row['budget'],
        'approval_id' => $row['approval_id'],
        'created_at' => $row['created_at'],
    ];
}, $rows);

send_success($result);