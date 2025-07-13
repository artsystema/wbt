<?php
require_once __DIR__ . '/../db/db.php';

$stmt = $pdo->query("SELECT txn_type, amount, description, created_at FROM fund_transactions ORDER BY created_at DESC");
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fund History</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body class="admin">
<h3>Fund Transaction History</h3>
<table border="1" cellpadding="4" cellspacing="0">
<tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th></tr>
<?php foreach ($transactions as $t): ?>
<tr>
  <td><?= $t['created_at'] ?></td>
  <td><?= htmlspecialchars($t['txn_type']) ?></td>
  <td>$<?= number_format($t['amount'], 2) ?></td>
  <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</table>
<p><a href="admin.php">Back to Admin</a></p>
</body>
</html>
