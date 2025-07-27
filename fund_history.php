<?php
require_once __DIR__ . '/db/db.php';

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
<?php
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$back = 'index.php';
if ($ref) {
    $parts = parse_url($ref);
    if ((!isset($parts['host']) || $parts['host'] === $_SERVER['HTTP_HOST']) && isset($parts['path'])) {
        $path = basename($parts['path']);
        if ($path && $path !== basename($_SERVER['SCRIPT_NAME'])) {
            $back = ltrim($parts['path'], '/');
            if (isset($parts['query'])) {
                $back .= '?' . $parts['query'];
            }
        }
    }
}
?>
<p><a href="<?= htmlspecialchars($back) ?>">Back</a></p>
<footer>
  &copy; 2023 [j3]
  <a href="https://github.com/artsystema/wbt" target="_blank">
    <img src="assets/icons/github.svg" alt="GitHub">
  </a>
</footer>
</body>
</html>
