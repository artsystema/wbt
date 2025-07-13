<?php
require_once __DIR__ . '/../db/db.php';

$taskId = intval($_POST['task_id'] ?? 0);
$file = $_POST['file'] ?? '';

if (!$taskId || !$file) {
    http_response_code(400);
    echo "Missing task_id or file";
    exit;
}

$path = __DIR__ . "/../uploads/$taskId/in/$file";

if (is_file($path)) {
    unlink($path);
    header("Location: /wbt/public/admin.php");
    exit;
} else {
    http_response_code(404);
    echo "File not found";
    exit;
}
