<?php
require_once __DIR__ . '/db/db.php';

$passcode = trim($_GET['user'] ?? $_GET['passcode'] ?? '');
if (!$passcode) {
    echo "Missing user";
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, description, reward, estimated_minutes, date_posted, status, start_time, submission_time, category FROM tasks WHERE assigned_to = ? AND status IN ('in_progress','pending_review','completed') ORDER BY date_posted DESC");
$stmt->execute([$passcode]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inProgress = 0;
$pending = 0;
$completed = 0;
foreach ($tasks as $t) {
    if ($t['status'] === 'in_progress') $inProgress++;
    elseif ($t['status'] === 'pending_review') $pending++;
    elseif ($t['status'] === 'completed') $completed++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($passcode) ?> History</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
    <style>
        #taskList::before { content: '<?= htmlspecialchars($passcode) ?> History:'; }
    </style>
</head>
<body>
<div id="top-bar">
    <div class="top-bar-left">
        <div class="top-bar-icon"><img src="assets/windows-95-loading.gif" alt=""></div>
        <div>
            <strong>WBT 1.0</strong>
            <span class="top-bar-info">[<span style="color:blue;"><strong><?= $inProgress ?></strong></span> in progress | <?= $pending ?> submitted | <?= $completed ?> completed]</span>
        </div>
        <div class="top-bar-user">User: <strong><?= htmlspecialchars($passcode) ?></strong></div>
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
<?php foreach ($tasks as $task): ?>
  <?php if ($task['status'] !== 'completed'): ?>
    <div class="task <?= htmlspecialchars($task['status']) ?>">
      <div>
        <div><strong><a href="task.php?id=<?= $task['id'] ?>">[<?= $task['id'] ?>] <?= htmlspecialchars($task['title']) ?></a></strong></div>
        <div class="task-meta">Posted on <?= $task['date_posted'] ?></div>
        <span class="task-category"><?= htmlspecialchars($task['category'] ?? '') ?></span>
      </div>
      <div><?= nl2br(htmlspecialchars($task['description'])) ?></div>
      <div><?= $task['estimated_minutes'] ?> min</div>
      <div>$<?= $task['reward'] ?></div>
      <div><?= str_replace('_', ' ', $task['status']) ?></div>
      <div></div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>
</div>
<div id="taskListCompleted"<?= $completed > 0 ? 'style="display:block;"' : ' style="display:none;"' ?> >
<?php foreach ($tasks as $task): ?>
  <?php if ($task['status'] === 'completed'): ?>
    <div class="task completed">
      <div>
        <div><strong><a href="task.php?id=<?= $task['id'] ?>">[<?= $task['id'] ?>] <?= htmlspecialchars($task['title']) ?></a></strong></div>
        <div class="task-meta">Posted on <?= $task['date_posted'] ?></div>
        <span class="task-category"><?= htmlspecialchars($task['category'] ?? '') ?></span>
      </div>
      <div><?= nl2br(htmlspecialchars($task['description'])) ?></div>
      <div><?= $task['estimated_minutes'] ?> min</div>
      <div>$<?= $task['reward'] ?></div>
      <div>completed</div>
      <div></div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>
</div>
<footer>
  &copy; 2023 <a href="http://docs.artsystema.com/" target="_blank">[j3 docs]</a>
  <a href="https://github.com/artsystema/wbt" target="_blank">
    <img src="assets/icons/github.svg" alt="GitHub">
  </a>
</footer>
</body>
</html>
