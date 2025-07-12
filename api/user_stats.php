<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
header('Content-Type: application/json');

$passcode = trim($_GET['passcode'] ?? '');
if (!$passcode) {
  echo json_encode(['error' => 'Missing passcode']);
  exit;
}

$stats = [
  'earned' => 0,
  'paid_out' => 0,
  'active_jobs' => 0,
  'submitted_jobs' => 0, // <-- added
  'completed_jobs' => 0,
  'last_submission' => null
];

try {
  // Earned (sum of reward for all completed/in-progress/pending_review tasks)
  $stmt = $pdo->prepare("SELECT SUM(reward) FROM tasks WHERE assigned_to = ? AND status IN ('in_progress', 'pending_review', 'completed')");
  $stmt->execute([$passcode]);
  $stats['earned'] = round($stmt->fetchColumn() ?? 0, 2);

  // Paid out (from payouts table)
  $stmt = $pdo->prepare("SELECT SUM(amount) FROM payouts WHERE passcode = ?");
  $stmt->execute([$passcode]);
  $stats['paid_out'] = round($stmt->fetchColumn() ?? 0, 2);

// Active jobs
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'in_progress'");
$stmt->execute([$passcode]);
$stats['active_jobs'] = intval($stmt->fetchColumn());

// Submitted jobs (pending review)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'pending_review'");
$stmt->execute([$passcode]);
$stats['submitted_jobs'] = intval($stmt->fetchColumn());

// Completed jobs
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
$stmt->execute([$passcode]);
$stats['completed_jobs'] = intval($stmt->fetchColumn());

  // Last submission date
  $stmt = $pdo->prepare("SELECT MAX(submitted_at) FROM submissions WHERE user_passcode = ?");
  $stmt->execute([$passcode]);
  $stats['last_submission'] = $stmt->fetchColumn();

  echo json_encode($stats);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
