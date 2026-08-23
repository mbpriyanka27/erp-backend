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

$eventRequestId = $body['event_request_id'] ?? null;
$action = $body['action'] ?? '';
$remarks = trim($body['remarks'] ?? '');

if (!$eventRequestId) {
    send_error('event_request_id is required', 422);
}
if (!in_array($action, ['approve', 'reject', 'send_back'], true)) {
    send_error('action must be "approve", "reject", or "send_back"', 422);
}

// --- Fetch the request ---
$stmt = $pdo->prepare('SELECT * FROM event_requests WHERE id = :id');
$stmt->execute(['id' => $eventRequestId]);
$request = $stmt->fetch();

if (!$request) {
    send_error('Event request not found', 404);
}

// --- Confirm this user's role is the current approver ---
if ((int) $request['current_approver_role_id'] !== (int) $user['role_id']) {
    send_error('Not authorized to act on this request', 403);
}

// --- Hardcoded approval chains (locked scope decision) ---
$chains = [
    'department' => ['Coordinator', 'HOD', 'Principal'],
    'university' => ['Coordinator', 'HOD', 'Principal', 'Director', 'VC'],
];

$chain = $chains[$request['level']] ?? null;
if ($chain === null) {
    send_error('Unknown request level: ' . $request['level'], 500);
}

$currentRoleName = $user['role_name'];
$currentIndex = array_search($currentRoleName, $chain, true);

// --- Determine new status + history action ---
if ($action === 'approve') {
    if ($currentIndex === false) {
        send_error('Current approver role is not part of this level\'s chain', 500);
    }

    if ($currentIndex === count($chain) - 1) {
        // Last step in the chain - fully approved
        $newStatus = 'Approved';
        $newApproverRoleId = null;
        $newApprovalId = 'APR-' . date('Y') . '-' . str_pad($eventRequestId, 4, '0', STR_PAD_LEFT);
    } else {
        $nextRoleName = $chain[$currentIndex + 1];

        $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $nextRoleName]);
        $nextRoleId = $stmt->fetchColumn();

        if (!$nextRoleId) {
            send_error('Next role "' . $nextRoleName . '" not found in roles table', 500);
        }

        $newStatus = 'Pending with ' . $nextRoleName;
        $newApproverRoleId = (int) $nextRoleId;
        $newApprovalId = null;
    }
    $historyAction = 'approved';
} elseif ($action === 'reject') {
    $newStatus = 'Rejected';
    $newApproverRoleId = null;
    $newApprovalId = null;
    $historyAction = 'rejected';
} else { // send_back
    $newStatus = 'Draft';
    $newApproverRoleId = null;
    $newApprovalId = null;
    $historyAction = 'sent_back';
}

// --- Update the request ---
$stmt = $pdo->prepare(
    'UPDATE event_requests
     SET current_status = :status, current_approver_role_id = :approver_role_id,
         approval_id = COALESCE(:approval_id, approval_id), updated_at = NOW()
     WHERE id = :id'
);
$stmt->execute([
    'status' => $newStatus,
    'approver_role_id' => $newApproverRoleId,
    'approval_id' => $newApprovalId,
    'id' => $eventRequestId,
]);

// --- Log to approval_history ---
$stmt = $pdo->prepare(
    'INSERT INTO approval_history (event_request_id, role_id, user_id, action, remarks, created_at)
     VALUES (:event_request_id, :role_id, :user_id, :action, :remarks, NOW())'
);
$stmt->execute([
    'event_request_id' => $eventRequestId,
    'role_id' => (int) $user['role_id'],
    'user_id' => (int) $user['id'],
    'action' => $historyAction,
    'remarks' => $remarks !== '' ? $remarks : null,
]);

// --- Return the updated request ---
$stmt = $pdo->prepare('SELECT * FROM event_requests WHERE id = :id');
$stmt->execute(['id' => $eventRequestId]);
$updated = $stmt->fetch();

send_success([
    'id' => (int) $updated['id'],
    'title' => $updated['title'],
    'level' => $updated['level'],
    'current_status' => $updated['current_status'],
    'current_approver_role_id' => $updated['current_approver_role_id'] !== null ? (int) $updated['current_approver_role_id'] : null,
    'approval_id' => $updated['approval_id'],
    'updated_at' => $updated['updated_at'],
]);