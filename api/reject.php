<?php
require_once __DIR__ . '/../db/db.php';

$taskId = intval($_POST['task_id'] ?? 0);
if (!$taskId) {
  http_response_code(400);
  echo "Missing task ID";
  exit;
}

// Reset task status
$stmt = $pdo->prepare("
  UPDATE tasks
  SET status = 'available',
      assigned_to = NULL,
      start_time = NULL,
      submission_time = NULL
  WHERE id = ?
");
$stmt->execute([$taskId]);

// Optional: delete submission record
$stmt = $pdo->prepare("DELETE FROM submissions WHERE task_id = ?");
$stmt->execute([$taskId]);

header("Location: /wbt/public/admin.php");
exit;
