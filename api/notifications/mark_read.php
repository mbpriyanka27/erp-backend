<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['notification_id'])) {
    send_error('Notification ID is required');
}

$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ?
      AND user_id = ?
");

$stmt->execute([
    $input['notification_id'],
    $user['id']
]);

send_success([
    "message" => "Notification marked as read"
]);