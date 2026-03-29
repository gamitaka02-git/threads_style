<?php
require_once __DIR__ . '/threads_style/admin/init.php';
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(', ', $tables) . "\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM posts");
echo "Posts count: " . $stmt->fetchColumn() . "\n";
