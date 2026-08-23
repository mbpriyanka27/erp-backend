<?php
require_once __DIR__ . '/config/database.php';

$approvalId = $_GET['id'] ?? null;

if (!$approvalId) {
    http_response_code(400);
    echo "<h2>Missing approval ID</h2>";
    exit;
}

$pdo = get_db_connection();

$stmt = $pdo->prepare(
    'SELECT er.*, u.name AS organizer_name
     FROM event_requests er
     JOIN users u ON u.id = er.organizer_id
     WHERE er.approval_id = :approval_id'
);
$stmt->execute(['approval_id' => $approvalId]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    echo "<h2>Not Found</h2><p>No approved event found for ID: " . htmlspecialchars($approvalId) . "</p>";
    exit;
}

$stmt2 = $pdo->prepare(
    'SELECT ah.action, ah.created_at, u.name AS user_name, r.name AS role_name
     FROM approval_history ah
     JOIN users u ON u.id = ah.user_id
     JOIN roles r ON r.id = ah.role_id
     WHERE ah.event_request_id = :id
     ORDER BY ah.created_at ASC'
);
$stmt2->execute(['id' => $event['id']]);
$history = $stmt2->fetchAll();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Event Approval Verification</title>
<style>
  body { font-family: -apple-system, Roboto, sans-serif; background:#5A1E1E; color:#fff; padding:24px; margin:0; }
  .card { background:#6A2525; border-radius:16px; padding:24px; max-width:480px; margin:0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
  h1 { color:#D4AF37; font-size:20px; margin: 0 0 12px; }
  .badge { display:inline-block; padding:4px 14px; border-radius:20px; background:rgba(60,154,95,0.15); border:1px solid #3C9A5F; color:#3C9A5F; font-weight:bold; font-size:13px; margin-bottom: 16px; }
  .row { margin:12px 0; }
  .label { color:#D4AF37; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom: 2px; }
  .value { font-size:15px; }
  hr { border:none; border-top: 1px solid rgba(255,255,255,0.15); margin: 20px 0; }
  .timeline-item { margin: 8px 0; font-size:13px; color:#eee; }
</style>
</head>
<body>
  <div class="card">
    <h1>Verified Event Approval</h1>
    <div class="badge"><?= htmlspecialchars($event['current_status']) ?></div>

    <div class="row"><div class="label">Event</div><div class="value"><?= htmlspecialchars($event['title']) ?></div></div>
    <div class="row"><div class="label">Approval ID</div><div class="value"><?= htmlspecialchars($event['approval_id']) ?></div></div>
    <div class="row"><div class="label">Level</div><div class="value"><?= htmlspecialchars(ucfirst($event['level'])) ?></div></div>
    <div class="row"><div class="label">Venue</div><div class="value"><?= htmlspecialchars($event['venue']) ?></div></div>
    <div class="row"><div class="label">Date &amp; Time</div><div class="value"><?= htmlspecialchars($event['event_date']) ?> at <?= htmlspecialchars($event['event_time']) ?></div></div>
    <div class="row"><div class="label">Participants</div><div class="value"><?= htmlspecialchars($event['participants_count']) ?></div></div>
    <div class="row"><div class="label">Budget</div><div class="value">&#8377;<?= htmlspecialchars($event['budget']) ?></div></div>
    <div class="row"><div class="label">Organizer</div><div class="value"><?= htmlspecialchars($event['organizer_name']) ?></div></div>
    <?php if (!empty($event['description'])): ?>
    <div class="row"><div class="label">Description</div><div class="value"><?= nl2br(htmlspecialchars($event['description'])) ?></div></div>
    <?php endif; ?>

    <hr>

    <div class="row">
      <div class="label">Approval Chain</div>
      <?php foreach ($history as $h): ?>
        <div class="timeline-item">&#10003; <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $h['action']))) ?> by <?= htmlspecialchars($h['role_name']) ?> (<?= htmlspecialchars($h['user_name']) ?>) &mdash; <?= htmlspecialchars($h['created_at']) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>