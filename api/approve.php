<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false)) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
require_once __DIR__ . '/../db/db.php';

$taskId = intval($_POST['task_id'] ?? 0);
$passcode = trim($_POST['passcode'] ?? '');

if (!$taskId || !$passcode) {
  http_response_code(400);
  echo "Missing data";
  exit;
}

// Count user's completed tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
$stmt->execute([$passcode]);
$completedCount = $stmt->fetchColumn();

// Get applicable bonus rule
$stmt = $pdo->prepare("SELECT bonus_percent FROM bonus_rules WHERE min_tasks <= ? ORDER BY min_tasks DESC LIMIT 1");
$stmt->execute([$completedCount]);
$bonusPercent = $stmt->fetchColumn() ?: 0;

// Get task reward
$stmt = $pdo->prepare("SELECT reward FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();
if (!$task) {
  echo "Task not found";
  exit;
}

$reward = $task['reward'];
$bonus = round($reward * $bonusPercent, 2);
$computedTotal = $reward + $bonus;

$posted = isset($_POST['payout']) ? floatval($_POST['payout']) : 0;
$total = $posted > 0 ? $posted : $computedTotal;

// Deduct from fund bank
$pdo->beginTransaction();

// Mark task completed
$stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
$stmt->execute([$taskId]);

// Create/update user if needed
$stmt = $pdo->prepare("INSERT IGNORE INTO users (passcode) VALUES (?)");
$stmt->execute([$passcode]);

// Deduct from fund
$stmt = $pdo->prepare("UPDATE fund_bank SET total_funds = total_funds - ?, last_updated = NOW() WHERE id = 1");
$stmt->execute([$total]);

// Record fund transaction
$stmt = $pdo->prepare("INSERT INTO fund_transactions (txn_type, amount, description) VALUES ('payout', ?, ?)");
$stmt->execute([$total, 'Task ' . $taskId]);

// Record payout
$stmt = $pdo->prepare("INSERT INTO payouts (passcode, amount, paid_at) VALUES (?, ?, NOW())");
$stmt->execute([$passcode, $total]);

$pdo->commit();

header("Location: /wbt/public/admin.php");
exit;
