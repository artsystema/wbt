<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT id, title, description, links, attachments, reward, estimated_minutes, date_posted, status, assigned_to, start_time, submission_time, category FROM tasks ORDER BY date_posted DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tasks as &$task) {
        $dir = __DIR__ . '/../uploads/' . $task['id'] . '/in';
        $attachments = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $attachments[] = "/wbt/uploads/{$task['id']}/in/{$file}";
                }
            }
        }
        $task['attachments'] = $attachments;

        if ($task['status'] === 'pending_review') {
            $stmtSub = $pdo->prepare("SELECT comment FROM submissions WHERE task_id = ? ORDER BY submitted_at DESC LIMIT 1");
            $stmtSub->execute([$task['id']]);
            $task['comment'] = $stmtSub->fetchColumn() ?: '';
        }
    }

    echo json_encode($tasks);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = intval($data['task_id'] ?? 0);
    $passcode = trim($data['passcode'] ?? '');

    if (!$taskId || !$passcode) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing task_id or passcode']);
        exit;
    }

    // Try to claim the task
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'available'");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not available']);
        exit;
    }

    // Assign task with current time
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'in_progress', assigned_to = ?, start_time = NOW() WHERE id = ?");
    $stmt->execute([$passcode, $taskId]);

    echo json_encode(['success' => true]);
    exit;
}
?>
