<?php
session_start();
require_once __DIR__ . '/db/db.php';

$stmt = $pdo->query("SELECT password_hash FROM admin_user WHERE id = 1");
$adminHash = $stmt->fetchColumn() ?: '';

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: admin.php');
    exit;
}

if (!$adminHash) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass = trim($_POST['new_pass'] ?? '');
        $confirm = trim($_POST['confirm_pass'] ?? '');
        if ($pass !== '' && $pass === $confirm) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_user (id, password_hash) VALUES (1, ?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)");
            $stmt->execute([$hash]);
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $setupError = 'Passwords do not match';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Setup</title>
        <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
    </head>
    <body>
    <div class="login-container">
        <h3>Set Admin Password</h3>
        <?php if (!empty($setupError)) echo '<p style="color:red;">'.htmlspecialchars($setupError).'</p>'; ?>
        <form method="POST">
            <input type="password" name="new_pass" placeholder="Password" required>
            <input type="password" name="confirm_pass" placeholder="Confirm" required>
            <button type="submit">Save</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass = $_POST['password'] ?? '';
        if (password_verify($pass, $adminHash)) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $loginError = 'Invalid password';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
    </head>
    <body>
    <div class="login-container">
        <h3>Admin Login</h3>
        <?php if (!empty($loginError)) echo '<p style="color:red;">'.htmlspecialchars($loginError).'</p>'; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

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

// Pre-calculate payout amounts with user coefficient
foreach ($pending as &$p) {
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
    $stmtC->execute([$p['assigned_to']]);
    $completed = $stmtC->fetchColumn();

    $stmtB = $pdo->prepare("SELECT bonus_percent FROM bonus_rules WHERE min_tasks <= ? ORDER BY min_tasks DESC LIMIT 1");
    $stmtB->execute([$completed]);
    $bonus = $stmtB->fetchColumn() ?: 0;

    $coeff = 1 + $bonus;
    $p['payout_amount'] = round($p['reward'] * $coeff, 2);
}
unset($p);

// Get all tasks except those pending review (for listing + edit/delete)
$stmt = $pdo->query("SELECT * FROM tasks WHERE status != 'pending_review' ORDER BY date_posted DESC");
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
  <script src="assets/admin.js?v=<?= time() ?>" defer></script>
</head>
<body class="admin">
  <div id="top-bar">
    <div class="top-bar-left">
      <a class="top-bar-icon" href="index.php"><img src="assets/windows-95-loading.gif" alt=""></a>
      <div>
        <strong>Admin Panel</strong>
        <span class="top-bar-info">[<span style="color:green;"><strong><?= $availableCount ?></strong></span> available | <?= $inProgressCount ?> in progress | <?= $completedCount ?> completed] [users: <?= $userCount ?>]</span>
      </div>
    </div>
    <div class="top-bar-right">
      <span>Balance: [<a href="fund_history.php">$<?= number_format($bankFunds, 2) ?></a>]</span>
      <form action="/api/deposit.php" method="POST" style="display:flex;gap:6px;align-items:center;margin-left:10px;">
        <input type="number" step="0.01" name="funds" placeholder="Amount" style="width:80px;">
        <button type="submit">Deposit</button>
      </form>
      <form action="admin.php" method="POST" style="margin-left:10px;display:inline;">
        <input type="hidden" name="logout" value="1">
        <button type="submit">Logout</button>
      </form>
    </div>
  </div>
  <h4 style="margin-top:70px;">Pending Reviews</h4>
<?php if (empty($pending)): ?>
  <p>No tasks awaiting review.</p>
<?php else: ?>
  <?php foreach ($pending as $task): ?>
    <div class="task review-task pending_review">
      <div><strong><?= htmlspecialchars($task['title']) ?></strong></div>
      <div>
        <?= htmlspecialchars($task['assigned_to']) ?><br>
        <span class="task-meta"><?= $task['submitted_at'] ?></span>
      </div>
      <div><?= htmlspecialchars($task['comment'] ?? '—') ?></div>
      <div>
        <a href="/uploads/<?= htmlspecialchars($task['file_path']) ?>" target="_blank">Download</a>
        <form action="/api/approve.php" method="POST" style="margin-top:4px;">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <input type="hidden" name="passcode" value="<?= $task['assigned_to'] ?>">
          <input type="number" step="0.01" name="payout" value="<?= $task['payout_amount'] ?>" style="width:80px;">
          <button type="submit">Approve & Pay</button>
        </form>
        <form action="/api/reject.php" method="POST" style="margin-top:5px;">
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
          <button type="submit" onclick="return confirm('Reject and relist this task?')">Reject</button>
        </form>
      </div>
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
<form class="task post" action="/api/admin_tasks.php" method="POST" enctype="multipart/form-data">
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
  <?php if ($task['status'] === 'completed'): ?>
    <div class="task <?= $task['status'] ?>">
      <div><strong><?= htmlspecialchars($task['title']) ?></strong>
        <form action="/api/admin_tasks.php" method="POST" style="display:inline;">
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
            $dir = __DIR__ . "/uploads/{$task['id']}/in";
            $webDir = "/uploads/{$task['id']}/in";
            if (is_dir($dir)) {
                $files = array_diff(scandir($dir), ['.', '..']);
                if (!empty($files)) {
                    echo "<div><strong>Attachments:</strong><ul>";
                    $i = 0;
                    foreach ($files as $file) {
                        $encoded = urlencode($file);
                        $fid = "del-{$task['id']}-comp-{$i}";
                        echo "<li><a href='{$webDir}/{$encoded}' target='_blank'>{$file}</a>";
                        echo " <button type='submit' form='{$fid}' style='margin-left:10px;' onclick='return confirm(\"Delete this file?\")'>Delete</button></li>";
                        echo "<form id='{$fid}' action='/api/delete_attachment.php' method='POST' style='display:none;'>";
                        echo "<input type='hidden' name='task_id' value='{$task['id']}'>";
                        echo "<input type='hidden' name='file' value='{$file}'>";
                        echo "</form>";
                        $i++;
                    }
                    echo "</ul></div>";
                }
            }
        ?>
      </div>
      <div><?= htmlspecialchars($task['category'] ?? '') ?></div>
      <div>$<?= $task['reward'] ?></div>
      <div><?= $task['estimated_minutes'] ?> min</div>
      <div>
        <?= $task['status'] ?>
        <?php if (!empty($task['assigned_to'])): ?>
          <br><span class="task-meta"><?= htmlspecialchars($task['assigned_to']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <form class="task edit <?= $task['status'] ?>" action="/api/admin_tasks.php" method="POST" enctype="multipart/form-data" id="edit-<?= $task['id'] ?>">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
      <div><input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required></div>
      <div>
        <textarea name="description" rows="2"><?= htmlspecialchars($task['description']) ?></textarea>
        <?php if (!empty($task['quit_comment'])): ?>
          <p><strong>Last Quit:</strong> <?= htmlspecialchars($task['quit_comment']) ?></p>
        <?php endif; ?>
        <?php
            $dir = __DIR__ . "/uploads/{$task['id']}/in";
            $webDir = "/uploads/{$task['id']}/in";
            $extraForms = '';
            if (is_dir($dir)) {
                $files = array_diff(scandir($dir), ['.', '..']);
                if (!empty($files)) {
                    echo "<div><strong>Attachments:</strong><ul>";
                    $i = 0;
                    foreach ($files as $file) {
                        $encoded = urlencode($file);
                        $fid = "del-{$task['id']}-{$i}";
                        echo "<li><a href='{$webDir}/{$encoded}' target='_blank'>{$file}</a>";
                        echo " <button type='submit' form='{$fid}' style='margin-left:10px;' onclick='return confirm(\"Delete this file?\")'>Delete</button></li>";
                        $extraForms .= "<form id='{$fid}' action='/api/delete_attachment.php' method='POST' style='display:none;'>";
                        $extraForms .= "<input type='hidden' name='task_id' value='{$task['id']}'>";
                        $extraForms .= "<input type='hidden' name='file' value='{$file}'>";
                        $extraForms .= "</form>";
                        $i++;
                    }
                    echo "</ul></div>";
                }
            }
        ?>
        <div><label>Attach new: <input type="file" name="attachments[]" multiple></label></div>
      </div>
      <div><input type="text" name="category" value="<?= htmlspecialchars($task['category'] ?? '') ?>"></div>
      <div><input type="number" step="0.01" name="reward" value="<?= $task['reward'] ?>"></div>
      <div><input type="number" name="minutes" value="<?= $task['estimated_minutes'] ?>"></div>
      <div>
        <?php if ($task['status'] === 'in_progress' && !empty($task['start_time'])): ?>
          <?php
            $endTs = strtotime($task['start_time']) + ($task['estimated_minutes'] * 60);
            $endIso = gmdate('c', $endTs);
          ?>
          <span class="countdown" data-end="<?= $endIso ?>" data-estimated-ms="<?= $task['estimated_minutes'] * 60000 ?>"></span>
        <?php else: ?>
          <?= $task['status'] ?>
        <?php endif; ?>
        <?php if (!empty($task['assigned_to'])): ?>
          <br><span class="task-meta"><?= htmlspecialchars($task['assigned_to']) ?></span>
        <?php endif; ?>
      </div>
      <div>
        <button type="submit">Save</button>
        <button type="submit" formaction="/api/admin_tasks.php" formmethod="POST" name="action" value="delete" onclick="return confirm('Delete this task?')">Delete</button>
      </div>
    </form>
    <?= $extraForms ?>
  <?php endif; ?>
<?php endforeach; ?>
</body>
</html>
