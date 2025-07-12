<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$taskId = $data["task_id"] ?? null;
$passcode = $data["passcode"] ?? "";

if (!$taskId || !$passcode) {
  http_response_code(400);
  echo json_encode(["error" => "Missing data"]);
  exit;
}

$stmt = $pdo->prepare("UPDATE tasks SET status = 'available', assigned_to = NULL, start_time = NULL WHERE id = ? AND assigned_to = ?");
$success = $stmt->execute([$taskId, $passcode]);

echo json_encode(["success" => $success]);
