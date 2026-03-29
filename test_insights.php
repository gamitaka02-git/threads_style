<?php
require_once __DIR__ . '/threads_style/admin/init.php';
require_once __DIR__ . '/threads_style/admin/api/ThreadsAPI.php';

$api = new ThreadsAPI();

$stmt = $pdo->query("SELECT id, threads_post_id FROM posts WHERE status = 'posted' AND threads_post_id != '' ORDER BY posted_at DESC LIMIT 1");
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo "No posts to fetch insights for.\n";
    exit;
}

echo "Fetching insights for Post ID: " . $post['threads_post_id'] . "\n";
$result = $api->getPostInsights($post['threads_post_id']);

print_r($result);
