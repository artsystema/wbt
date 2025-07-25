<?php
require_once __DIR__ . '/../db/db.php';
header('Content-Type: application/json');

$passcode = trim($_GET['passcode'] ?? '');
if (!$passcode) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, description, reward, estimated_minutes, date_posted, status, start_time, submission_time, category FROM tasks WHERE assigned_to = ? AND status IN ('in_progress','pending_review','completed') ORDER BY date_posted DESC");
$stmt->execute([$passcode]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as &$task) {
    $dir = __DIR__ . '/../uploads/' . $task['id'] . '/in';
    $attachments = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $attachments[] = "/uploads/{$task['id']}/in/{$file}";
            }
        }
    }
    $task['attachments'] = $attachments;

    if (in_array($task['status'], ['pending_review','completed'])) {
        $subStmt = $pdo->prepare("SELECT file_path, comment, submitted_at FROM submissions WHERE task_id = ? ORDER BY submitted_at DESC LIMIT 1");
        $subStmt->execute([$task['id']]);
        if ($sub = $subStmt->fetch(PDO::FETCH_ASSOC)) {
            $task['submission'] = [
                'file' => '/uploads/' . $sub['file_path'],
                'comment' => $sub['comment'],
                'submitted_at' => $sub['submitted_at']
            ];
        }
    }
}

echo json_encode($tasks);
