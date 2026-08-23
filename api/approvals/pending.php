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
    'SELECT er.id, er.title, er.level, er.current_status, er.event_date,
            er.venue, er.participants_count, er.budget, er.description,
            u.name AS organizer_name
     FROM event_requests er
     JOIN users u ON u.id = er.organizer_id
     WHERE er.current_approver_role_id = :role_id
     ORDER BY er.created_at ASC'
);
$stmt->execute(['role_id' => (int) $user['role_id']]);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'level' => $row['level'],
        'current_status' => $row['current_status'],
        'event_date' => $row['event_date'],
        'venue' => $row['venue'],
        'participants_count' => (int) $row['participants_count'],
        'budget' => $row['budget'],
        'description' => $row['description'],
        'organizer_name' => $row['organizer_name'],
    ];
}, $rows);

send_success($result);