<?php
$host = 'localhost';
$db   = 'task_tracker';
$user = 'root';
$pass = 'root'; // default MAMP password; change if different
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
  http_response_code(500);
  echo "DB Connection failed: " . $e->getMessage();
  exit;
}
