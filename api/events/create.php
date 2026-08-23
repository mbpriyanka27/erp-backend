<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Method not allowed', 405);
}

$pdo = get_db_connection();
$user = require_auth($pdo);

$body = get_json_body();

$title = trim($body['title'] ?? '');
$level = trim($body['level'] ?? '');
$categoryId = $body['category_id'] ?? null;
$departmentId = $body['department_id'] ?? $user['department_id'];
$facultyCoordinatorId = $body['faculty_coordinator_id'] ?? null;
$eventDate = trim($body['event_date'] ?? '');
$eventTime = trim($body['event_time'] ?? '');
$venue = trim($body['venue'] ?? '');
$participants = $body['participants'] ?? null;
$budget = $body['budget'] ?? 0;
$description = trim($body['description'] ?? '');
$submit = $body['submit'] ?? false;

// --- Validation ---
if ($title === '') {
    send_error('Title is required', 422);
}
if (!in_array($level, ['department', 'university'], true)) {
    send_error('Level must be "department" or "university"', 422);
}
if ($eventDate === '') {
    send_error('Event date is required', 422);
}
if ($eventTime === '') {
    send_error('Event time is required', 422);
}
if ($venue === '') {
    send_error('Venue is required', 422);
}
if ($participants === null || (int) $participants <= 0) {
    send_error('Participants must be a positive number', 422);
}

$organizerId = (int) $user['id'];

// --- Determine status + next approver ---
if ($submit === true || $submit === 'true' || $submit === 1) {
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => 'Coordinator']);
    $coordinatorRoleId = $stmt->fetchColumn();

    if (!$coordinatorRoleId) {
        send_error('Coordinator role not found in roles table', 500);
    }

    $currentStatus = 'Pending with Coordinator';
    $currentApproverRoleId = (int) $coordinatorRoleId;
} else {
    $currentStatus = 'Draft';
    $currentApproverRoleId = null;
}

// --- Insert ---
try {
    $stmt = $pdo->prepare(
    'INSERT INTO event_requests
        (title, category_id, level, department_id, organizer_id,
         faculty_coordinator_id, event_date, event_time, venue,
         participants_count, budget, description, current_status,
         current_approver_role_id, created_at, updated_at)
     VALUES
        (:title, :category_id, :level, :department_id, :organizer_id,
         :faculty_coordinator_id, :event_date, :event_time, :venue,
         :participants_count, :budget, :description, :current_status,
         :current_approver_role_id, NOW(), NOW())'
);

$stmt->execute([
    'title' => $title,
    'category_id' => $categoryId,
    'level' => $level,
    'department_id' => $departmentId,
    'organizer_id' => $organizerId,
    'faculty_coordinator_id' => $facultyCoordinatorId,
    'event_date' => $eventDate,
    'event_time' => $eventTime,
    'venue' => $venue,
    'participants_count' => (int) $participants,
    'budget' => $budget,
    'description' => $description,
    'current_status' => $currentStatus,
    'current_approver_role_id' => $currentApproverRoleId,
]);

$newId = (int) $pdo->lastInsertId();

// --- Return the created row ---
$stmt = $pdo->prepare('SELECT * FROM event_requests WHERE id = :id');
$stmt->execute(['id' => $newId]);
$created = $stmt->fetch();

send_success([
    'id' => (int)$created['id'],
    'title' => $created['title'],
    'category_id' => $created['category_id'] !== null ? (int)$created['category_id'] : null,
    'level' => $created['level'],
    'department_id' => $created['department_id'] !== null ? (int)$created['department_id'] : null,
    'organizer_id' => (int)$created['organizer_id'],
    'faculty_coordinator_id' => $created['faculty_coordinator_id'] !== null ? (int)$created['faculty_coordinator_id'] : null,
    'event_date' => $created['event_date'],
    'event_time' => $created['event_time'],
    'venue' => $created['venue'],
    'participants_count' => (int)$created['participants_count'],
    'budget' => $created['budget'],
    'description' => $created['description'],
    'current_status' => $created['current_status'],
    'current_approver_role_id' => $created['current_approver_role_id'] !== null
        ? (int)$created['current_approver_role_id']
        : null,
    'created_at' => $created['created_at'],
    'updated_at' => $created['updated_at'],
]);

} catch (PDOException $e) {
    send_error($e->getMessage(), 500);
}