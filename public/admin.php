<?php
require_once __DIR__ . '/../db/db.php';

// Get pending review tasks with submission info
$stmt = $pdo->query("
  SELECT t.*, s.file_path, s.submitted_at, s.comment, u.note
  FROM tasks t
  JOIN submissions s ON t.id = s.task_id
  LEFT JOIN users u ON s.user_passcode = u.passcode
  WHERE t.status = 'pending_review'
  ORDER BY s.submitted_at DESC
");
$pending = $stmt->fetchAll();

// Get all tasks (for listing + edit/delete)
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY date_posted DESC");
$tasks = $stmt->fetchAll();

// Stats for top bar
$availableCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'available'")->fetchColumn();
$inProgressCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
$completedCount = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$bankFunds = $pdo->query("SELECT total_funds FROM fund_bank WHERE id = 1")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel</title>
  <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body class="admin">
  <div id="top-bar">
    <div class="top-bar-left">
      <div class="top-bar-icon"><img src="assets/windows-95-loading.gif" alt=""></div>
      <div>
        <strong>Admin Panel</strong>
        <span class="top-bar-info">[<span style="color:green;"><strong><?= $availableCount ?></strong></span> available | <?= $inProgressCount ?> in progress | <?= $completedCount ?> completed] [users: <?= $userCount ?>]</span>
      </div>
    </div>
    <div class="top-bar-right">
      <form action="/wbt/api/set_fund.php" method="POST" style="display:flex;gap:6px;align-items:center;">
        <label>Funds: <input type="number" step="0.01" name="funds" value="<?= $bankFunds ?>" style="width:80px;"></label>
        <button type="submit">Set</button>
      </form>
    </div>
  </div>
  <h3 style="margin-top:70px;">Admin Panel: Task Management</h3>
<h4>Pending Reviews</h4>
<?php if (empty($pending)): ?>
  <p>No tasks awaiting review.</p>
<?php else: ?>
  <?php foreach ($pending as $task): ?>
    <div class="task" style="border:2px solid orange;">
      <div><strong><?= htmlspecialchars($task['title']) ?> (Pending) </strong></div>
      <p><strong>User:</strong> <?= htmlspecialchars($task['assigned_to']) ?></p>
      <p><strong>Submitted:</strong> <?= $task['submitted_at'] ?></p>
      <p><strong>Note:</strong> <?= htmlspecialchars($task['note'] ?? '—') ?></p>
      <p><strong>Comment:</strong> <?= htmlspecialchars($task['comment'] ?? '—') ?></p>
      <a href="/wbt/uploads/<?= htmlspecialchars($task['file_path']) ?>" target="_blank">Download submission</a>
		<div>      
		<form action="/wbt/api/approve.php" method="POST">
        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
        <input type="hidden" name="passcode" value="<?= $task['assigned_to'] ?>">
        <button type="submit">Approve & Pay</button>
      </form>
      <form action="/wbt/api/reject.php" method="POST" style="margin-top:5px;">
        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
        <button type="submit" onclick="return confirm('Reject and relist this task?')">Reject</button>
      </form></div>	  

    </div>

  <?php endforeach; ?>
<?php endif; ?>

<h4>All Tasks</h4>
<div class="task header">
  <div>Title</div>
  <div>Description</div>
  <div>Category</div>
  <div>Reward</div>
  <div>Time</div>
  <div>Status</div>
</div>
<form class="task post" action="/wbt/api/admin_tasks.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="action" value="create">
  <div><input type="text" name="title" placeholder="Title" required></div>
  <div><textarea name="description" rows="2" placeholder="Description"></textarea> <div><label>Attachments (optional, multiple): <input type="file" name="attachments[]" multiple></label></div></div>
  <div><input type="text" name="category" placeholder="Category"></div>
  <div><input type="number" name="reward" step="0.01" placeholder="$" required></div>
  <div><input type="number" name="minutes" placeholder="Min" required></div>
  <div>—</div>
  <div><button type="submit">Add Task</button></div>
</form>
<?php foreach ($tasks as $task): ?>
  <div class="task">
    <div><strong><?= htmlspecialchars($task['title']) ?></strong>
	      <form action="/wbt/api/admin_tasks.php" method="POST" style="display:inline;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
        <button type="submit" onclick="return confirm('Delete this task?')">Delete</button>
      </form>
	
	</div>
    <div><?= nl2br(htmlspecialchars($task['description'])) ?>
      <?php if (!empty($task['quit_comment'])): ?>
        <p><strong>Last Quit:</strong> <?= htmlspecialchars($task['quit_comment']) ?></p>
      <?php endif; ?>
	
		<?php
			$dir = __DIR__ . "/../uploads/{$task['id']}/in";
			$webDir = "/wbt/uploads/{$task['id']}/in";
			if (is_dir($dir)) {
				$files = array_diff(scandir($dir), ['.', '..']);
				if (!empty($files)) {
					echo "<div><strong>Attachments:</strong><ul>";
					foreach ($files as $file) {
						$encoded = urlencode($file);
						echo "<li><a href='{$webDir}/{$encoded}' target='_blank'>{$file}</a>
							<form action='/wbt/api/delete_attachment.php' method='POST' style='display:inline; margin-left:10px;'>
							<input type='hidden' name='task_id' value='{$task['id']}'>
							<input type='hidden' name='file' value='{$file}'>
							<button type='submit' onclick='return confirm(\"Delete this file?\")'>Delete</button>
							</form></li>";
						}
					echo "</ul></div>";
				}
			}
		?>
	</div>
    <div><?= htmlspecialchars($task['category'] ?? '') ?></div>
    <div>$<?= $task['reward'] ?></div>
    <div><?= $task['estimated_minutes'] ?> min</div>
    <div><?= $task['status'] ?></div>
    <div class="details">

      <details>
        <summary>Edit</summary>
        <form action="/wbt/api/admin_tasks.php" method="POST">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <label>Title: <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>"></label><br>
          <label>Description:<br><textarea name="description" rows="4" cols="50"><?= htmlspecialchars($task['description']) ?></textarea></label><br>
          <label>Category: <input type="text" name="category" value="<?= htmlspecialchars($task['category'] ?? '') ?>"></label><br>
          <label>Reward: <input type="number" step="0.01" name="reward" value="<?= $task['reward'] ?>"></label><br>
          <label>Minutes: <input type="number" name="minutes" value="<?= $task['estimated_minutes'] ?>"></label><br>
          <button type="submit">Save</button>
        </form>
      </details>
    </div>
  </div>
<?php endforeach; ?>
</body>
</html>
