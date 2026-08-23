<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

$body = get_json_body();

$requestId = $body['event_request_id'] ?? null;

if (!$requestId) {
    send_error('event_request_id is required', 422);
}

$stmt = $pdo->prepare("
SELECTF
    er.id,
    er.title,
    er.level,
    er.event_date,
    er.event_time,
    er.venue,
    er.participants_count,
    er.budget,
    er.description,
    er.current_status,
    er.approval_id,
    er.created_at,

    u.name AS organizer_name,
    d.name AS department_name,
    ec.name AS category_name

FROM event_requests er
JOIN users u
    ON er.organizer_id = u.id
LEFT JOIN departments d
    ON er.department_id = d.id
LEFT JOIN event_categories ec
    ON er.category_id = ec.id

WHERE er.id = ?
LIMIT 1
");

$stmt->execute([$requestId]);

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    send_error("Request not found", 404);
}

if ($request["current_status"] !== "Approved") {
    send_error("Request has not been fully approved yet", 400);
}

send_success([
    "approval_letter" => [
        "approval_id" => $request["approval_id"],
        "event_title" => $request["title"],
        "category" => $request["category_name"],
        "level" => ucfirst($request["level"]),
        "department" => $request["department_name"],
        "organizer" => $request["organizer_name"],
        "event_date" => $request["event_date"],
        "event_time" => $request["event_time"],
        "venue" => $request["venue"],
        "participants" => (int)$request["participants_count"],
        "budget" => $request["budget"],
        "description" => $request["description"],
        "status" => $request["current_status"],
        "approved_on" => date("Y-m-d"),
        "university" => "GM University",
        "message" =>
            "This is to certify that the above event has been officially approved by the University ERP Event Management System."
    ]
]);