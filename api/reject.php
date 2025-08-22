<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false)) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
require_once __DIR__ . '/../db/db.php';
$mailConfig = require __DIR__ . '/../config.php';

$taskId = intval($_POST['task_id'] ?? 0);
if (!$taskId) {
  http_response_code(400);
  echo "Missing task ID";
  exit;
}

// Reset task status
$stmt = $pdo->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$rejectedUser = $stmt->fetchColumn();

// Reset task status and remember who was rejected
$stmt = $pdo->prepare(
    "UPDATE tasks SET status = 'available', assigned_to = NULL, start_time = NULL, submission_time = NULL, last_rejected = ? WHERE id = ?"
);
$stmt->execute([$rejectedUser, $taskId]);

// Optional: delete submission record
$stmt = $pdo->prepare("DELETE FROM submissions WHERE task_id = ?");
$stmt->execute([$taskId]);

// Notify worker via email
if ($rejectedUser) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE passcode = ?");
    $stmt->execute([$rejectedUser]);
    $userEmail = $stmt->fetchColumn();
    if ($userEmail) {
        $subject = "Task #$taskId Rejected";
        $message = "Your submission for task #$taskId has been rejected. Payout: $0.";
        $headers = 'From: ' . ($mailConfig['smtp_from'] ?? 'no-reply@example.com');
        if (!empty($mailConfig['smtp_host'])) {
            ini_set('SMTP', $mailConfig['smtp_host']);
        }
        mail($userEmail, $subject, $message, $headers);
    }
}

header("Location: /admin.php");
exit;
