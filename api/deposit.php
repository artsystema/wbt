<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false)) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
require_once __DIR__ . '/../db/db.php';


$amount = isset($_POST['funds']) ? floatval($_POST['funds']) : 0;
if ($amount <= 0) {
    http_response_code(400);
    echo "Invalid amount";
    exit;
}

$pdo->beginTransaction();
$stmt = $pdo->prepare("UPDATE fund_bank SET total_funds = total_funds + ?, last_updated = NOW() WHERE id = 1");
$stmt->execute([$amount]);

$stmt = $pdo->prepare("INSERT INTO fund_transactions (txn_type, amount, description) VALUES ('deposit', ?, 'Manual deposit')");
$stmt->execute([$amount]);

$pdo->commit();

header("Location: /wbt/admin.php");
exit;
?>
