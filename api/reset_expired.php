<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
header('Content-Type: application/json');
date_default_timezone_set('UTC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // GLOBAL reset (from any user opening the page)
    if (isset($_POST['global_reset'])) {
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET status = 'available', assigned_to = NULL, start_time = NULL 
            WHERE status = 'in_progress' 
            AND TIMESTAMPDIFF(MINUTE, start_time, NOW()) > estimated_minutes
        ");
        $stmt->execute();

        echo json_encode(['success' => true, 'reset' => $stmt->rowCount()]);
        exit;
    }

    // PER-TASK reset (user's own expired task)
    $taskId = intval($_POST['task_id'] ?? 0);
    $passcode = trim($_POST['passcode'] ?? '');

    if (!$taskId || !$passcode) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing input']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task || $task['status'] !== 'in_progress' || $task['assigned_to'] !== $passcode) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized or not in progress']);
        exit;
    }

    $startTime = strtotime($task['start_time']);
    $duration = $task['estimated_minutes'] * 60;
    $now = time();

    if ($now > ($startTime + $duration)) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'available', assigned_to = NULL, start_time = NULL WHERE id = ?");
        $stmt->execute([$taskId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Task has not yet expired']);
    }
}
