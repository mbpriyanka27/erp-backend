<?php

require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../helpers/response.php';
require_once __DIR__.'/../../helpers/auth.php';

if($_SERVER['REQUEST_METHOD']!=='GET'){
    send_error('Method not allowed',405);
}

$pdo=get_db_connection();
$user=require_auth($pdo);

$keyword=$_GET['keyword']??'';

$stmt=$pdo->prepare("
SELECT *
FROM event_requests
WHERE title LIKE ?
ORDER BY created_at DESC
");

$stmt->execute([
"%".$keyword."%"
]);

send_success(
$stmt->fetchAll(PDO::FETCH_ASSOC)
);