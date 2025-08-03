<?php
require_once __DIR__ . '/db/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo "Missing task ID";
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, description, reward, estimated_minutes, date_posted, status, assigned_to, category FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) {
    echo "Task not found";
    exit;
}

$attachments = [];
$dir = __DIR__ . '/uploads/' . $task['id'] . '/in';
if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if ($file !== '.' && $file !== '..') {
            $attachments[] = "/uploads/{$task['id']}/in/{$file}";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task <?= htmlspecialchars($task['id']) ?></title>
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body>
<div id="top-bar">
    <div class="top-bar-left">
        <div class="top-bar-icon"><img src="assets/windows-95-loading.gif" alt=""></div>
        <div><strong>WBT 1.0</strong></div>
    </div>
    <div class="top-bar-right">
        <a href="index.php">Back</a>
    </div>
</div>
<div id="taskList">
    <div class="task header">
        <div>Title</div>
        <div>Description</div>
        <div>Time</div>
        <div>Reward</div>
        <div>Status</div>
        <div>—</div>
    </div>
    <div class="task <?= htmlspecialchars($task['status']) ?>">
        <div>
            <div><strong>[<?= $task['id'] ?>] <?= htmlspecialchars($task['title']) ?></strong></div>
            <div class="task-meta">Posted on <?= $task['date_posted'] ?></div>
            <span class="task-category"><?= htmlspecialchars($task['category'] ?? '') ?></span>
        </div>
        <div>
            <?= nl2br(htmlspecialchars($task['description'])) ?>
            <?php if (!empty($attachments)): ?>
                <div class="attachments"><strong>Attachments:</strong>
                    <ul>
                        <?php foreach ($attachments as $file): ?>
                            <li><a href="<?= htmlspecialchars($file) ?>" target="_blank"><?= htmlspecialchars(basename($file)) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <div><?= $task['estimated_minutes'] ?> min</div>
        <div>$<?= $task['reward'] ?></div>
        <div><?= str_replace('_', ' ', $task['status']) ?></div>
        <div></div>
    </div>
</div>
<footer>
    &copy; 2025 <a href="http://docs.artsystema.com/" target="_blank">[j3 docs]</a>
    <a href="https://github.com/artsystema/wbt" target="_blank">
        <img src="assets/icons/github.svg" alt="GitHub">
    </a>
</footer>
</body>
</html>
