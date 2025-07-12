<?php
require_once __DIR__ . '/../db/db.php';

header('Content-Type: application/json');

// Get total fund
$stmt = $pdo->query("SELECT total_funds FROM fund_bank WHERE id = 1");
$total = $stmt->fetchColumn() ?: 0;

// Sum all pending tasks' reward
$stmt = $pdo->query("SELECT SUM(reward) FROM tasks WHERE status = 'pending_review'");
$reserved = $stmt->fetchColumn() ?: 0;

$available = $total - $reserved;

echo json_encode([
  "available" => round($available, 2),
  "reserved" => round($reserved, 2),
  "total" => round($total, 2)
]);
