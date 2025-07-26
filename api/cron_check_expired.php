<?php
require_once __DIR__ . '/../db/db.php';
// ensure the script runs in UTC
date_default_timezone_set('UTC');

$stmt = $pdo->prepare("UPDATE tasks SET status = 'available', assigned_to = NULL, start_time = NULL
  WHERE status = 'in_progress' AND TIMESTAMPDIFF(MINUTE, start_time, UTC_TIMESTAMP()) > estimated_minutes");
$stmt->execute();
echo "Expired tasks reset: " . $stmt->rowCount();
