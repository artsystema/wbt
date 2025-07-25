<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false)) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
require_once __DIR__ . '/../db/db.php';

$action = $_POST['action'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$reward = floatval($_POST['reward'] ?? 0);
$minutes = intval($_POST['minutes'] ?? 0);
$taskId = intval($_POST['task_id'] ?? 0);
$category = trim($_POST['category'] ?? '');
$attachments = [];

function handleUploads($taskId): array {
    $files = $_FILES['attachments'] ?? null;
    if (!$files || !is_array($files['name'])) return [];

    $folder = __DIR__ . "/../uploads/$taskId/in";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $savedPaths = [];

    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $safeName = basename($name);
            $target = "$folder/$safeName";

            if (move_uploaded_file($tmpName, $target)) {
                $savedPaths[] = "uploads/$taskId/in/$safeName";
            }
        }
    }

    return $savedPaths;
}

switch ($action) {
  case 'create':
    $stmt = $pdo->prepare("INSERT INTO tasks (title, description, reward, estimated_minutes, date_posted, category) VALUES (?, ?, ?, ?, NOW(), ?)");
    $stmt->execute([$title, $description, $reward, $minutes, $category]);
    $taskId = $pdo->lastInsertId(); // get inserted task id
    $attachments = handleUploads($taskId);
    if (!empty($attachments)) {
        $json = json_encode($attachments);
        $stmt = $pdo->prepare("UPDATE tasks SET attachments = ? WHERE id = ?");
        $stmt->execute([$json, $taskId]);
    }
    break;

  case 'edit':
    $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, reward = ?, estimated_minutes = ?, category = ? WHERE id = ?");
    $stmt->execute([$title, $description, $reward, $minutes, $category, $taskId]);

    // Only process uploads if files are sent
    if (!empty($_FILES['attachments']['name'][0])) {
        $attachments = handleUploads($taskId);
        if (!empty($attachments)) {
            $stmt = $pdo->prepare("UPDATE tasks SET attachments = ? WHERE id = ?");
            $stmt->execute([json_encode($attachments), $taskId]);
        }
    }
    break;

  case 'delete':
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    break;
}

header("Location: /admin.php");
exit;
