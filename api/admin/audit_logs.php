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
    send_error('Access denied', 403);
}

$stmt = $pdo->query("
SELECT
audit_logs.id,
users.name,
audit_logs.action,
audit_logs.entity_type,
audit_logs.entity_id,
audit_logs.details,
audit_logs.created_at
FROM audit_logs
LEFT JOIN users
ON users.id=audit_logs.user_id
ORDER BY audit_logs.created_at DESC
LIMIT 100
");

send_success($stmt->fetchAll(PDO::FETCH_ASSOC));