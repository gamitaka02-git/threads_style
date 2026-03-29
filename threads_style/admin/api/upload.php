<?php
/**
 * ============================================================
 * 画像アップロード API - ThreadsStyle
 * ============================================================
 */
require_once __DIR__ . '/../init.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// ファイルの存在確認
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_code = $_FILES['image']['error'] ?? 'No file';
    echo json_encode(['success' => false, 'message' => 'ファイルがアップロードされていません。エラーコード: ' . $error_code]);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$max_size = 8 * 1024 * 1024; // 8MB (Threads API 制限に合わせる)

// バリデーション
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => '許可されていないファイル形式です (JPG, PNG, WebPのみ)']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'ファイルサイズが大きすぎます (最大8MB)']);
    exit;
}

// 保存先ディレクトリの準備
$upload_dir = __DIR__ . '/../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ファイル名の整理
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($ext)) {
    $ext = ($file['type'] === 'image/png') ? 'png' : (($file['type'] === 'image/webp') ? 'webp' : 'jpg');
}
$filename = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$target_path = $upload_dir . $filename;

// ファイル移動
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // 公開URLの生成 (現在のホスト名を取得)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']); // /admin/api
    $base_dir = dirname($script_dir); // /admin
    
    $url = $protocol . '://' . $host . $base_dir . '/uploads/' . $filename;
    
    echo json_encode([
        'success' => true, 
        'url' => $url,
        'filename' => $filename,
        'message' => 'アップロード成功'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'ファイルの保存に失敗しました。']);
}
