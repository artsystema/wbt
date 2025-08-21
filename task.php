<?php
require_once __DIR__ . '/db/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo "Missing task ID";
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, description, reward, estimated_minutes, date_posted, status, assigned_to, category, start_time, pinned FROM tasks WHERE id = ?");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[<?= htmlspecialchars($task['id']) ?>] <?= htmlspecialchars($task['title']) ?></title>
    <script src="assets/task.js?v=<?= time() ?>" defer></script>
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body>
<div id="top-bar">
    <div class="top-bar-left">
        <a class="top-bar-icon" href="admin.php"><img src="assets/windows-95-loading.gif"></a>
        <div title="Web-based Task Tracker 1.0">
            <strong>WBT 1.0</strong>
            <span id="taskStats" style="font-weight: normal; font-size: 0.9em;"></span>
        </div>
        <span class="top-bar-info">Balance: [<a href="fund_history.php"><span id="bankDisplay">Loading funds...</span></a>]</span>
    </div>
    <div class="top-bar-right">
        <div id="authControls">
            <input type="text" id="authField" placeholder="Enter passcode..." />
            <button id="authBtn">Authorize</button>
            <span id="authStatus"></span>
        </div>
        <a href="index.php" id="backBtn">Back</a>
    </div>
</div>
<div id="taskList">
    <div class="task header">
        <div>Title</div>
        <div>Description</div>
        <div>Time</div>
        <div>Reward</div>
        <div>Status</div>
        <div>Action</div>
    </div>
    <div id="taskRow" class="task <?= htmlspecialchars($task['status']) ?>" data-id="<?= $task['id'] ?>" data-owner="<?= htmlspecialchars($task['assigned_to'] ?? '') ?>" data-status="<?= htmlspecialchars($task['status']) ?>" data-start="<?= htmlspecialchars($task['start_time'] ? gmdate('c', strtotime($task['start_time'])) : '') ?>" data-estimated-ms="<?= $task['estimated_minutes'] * 60000 ?>">
        <div>
            <div><strong><?= $task['pinned'] ? '📌 ' : '' ?>[<?= $task['id'] ?>] <?= htmlspecialchars($task['title']) ?></strong></div>
            <div class="task-meta">Posted on <?= $task['date_posted'] ?></div>
            <span class="task-category"><?= htmlspecialchars($task['category'] ?? '') ?></span>
        </div>
        <div>
            <?= nl2br($task['description']) ?>
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
        <div id="statusCell"><?= str_replace('_', ' ', $task['status']) ?></div>
        <div id="actionCell">
            <?php if ($task['status'] === 'available'): ?>
                <button id="takeBtn" data-id="<?= $task['id'] ?>">Take</button>
            <?php endif; ?>
        </div>
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
