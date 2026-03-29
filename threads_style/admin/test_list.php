<?php
require_once __DIR__ . '/init.php';

$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT 100");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$json = json_encode(['success' => true, 'posts' => $posts]);
if ($json === false) {
    echo "JSON Encode Error: " . json_last_error_msg() . "\n";
    // 問題のある投稿を特定する
    foreach ($posts as $post) {
        if (json_encode($post) === false) {
            echo "Failed on post ID " . $post['id'] . ": " . json_last_error_msg() . "\n";
        }
    }
} else {
    echo "JSON Encode Success!\n";
    // echo $json;
}
