<?php
require_once __DIR__ . '/../db/db.php';

$passcode = $_POST['passcode'] ?? '';
$taskId = intval($_POST['task_id'] ?? 0);

if (!$passcode || !$taskId || !isset($_FILES['attachment'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing data']);
  exit;
}

// Check task is in progress and belongs to this passcode
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'in_progress' AND assigned_to = ?");
$stmt->execute([$taskId, $passcode]);
$task = $stmt->fetch();

if (!$task) {
  http_response_code(403);
  echo json_encode(['error' => 'Invalid task or permission']);
  exit;
}

// Save file
$targetDir = __DIR__ . '/../uploads/';
$fileName = basename($_FILES['attachment']['name']);
$uniqueName = time() . '_' . preg_replace("/[^a-zA-Z0-9_.-]/", "_", $fileName);
$targetPath = $targetDir . $uniqueName;

if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to save file']);
  exit;
}

// Mark task pending and create submission
$pdo->beginTransaction();

$stmt = $pdo->prepare("UPDATE tasks SET status = 'pending_review', submission_time = NOW() WHERE id = ?");
$stmt->execute([$taskId]);

$stmt = $pdo->prepare("INSERT INTO submissions (task_id, user_passcode, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$taskId, $passcode, $uniqueName]);

$pdo->commit();

echo json_encode(['success' => true]);
