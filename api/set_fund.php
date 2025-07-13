<?php
require_once __DIR__ . '/../db/db.php';

$amount = isset($_POST['funds']) ? floatval($_POST['funds']) : null;
if ($amount === null) {
    http_response_code(400);
    echo "Missing funds";
    exit;
}

$stmt = $pdo->prepare("UPDATE fund_bank SET total_funds = ?, last_updated = NOW() WHERE id = 1");
$stmt->execute([$amount]);

header("Location: /wbt/public/admin.php");
exit;
?>
