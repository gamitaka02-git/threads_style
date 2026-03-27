<?php
/**
 * ============================================================
 * ライセンス認証API
 * ============================================================
 * 役割: ツール側（tool_app）からのリクエストを受け取り、
 *       ライセンスキーの有効性をMySQL上で検証して結果を返す。
 *       ドメイン不問（無制限ドメイン対応）の認証方式。
 *
 * 【カスタマイズ箇所】
 * - 特になし（config.phpの設定値に依存）
 * - ドメイン制限を追加する場合はステップ4を修正
 * ============================================================
 */

// 設定ファイルを読み込む
require_once __DIR__ . '/../admin/config.php'; 

header('Content-Type: application/json');

// --- 1. APIトークンを検証 ---
$api_token = isset($_POST['api_token']) ? trim($_POST['api_token']) : '';
if ($api_token !== SECRET_TOKEN) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API token.']);
    exit;
}

// --- 2. POSTデータを取得 ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$license_key = isset($_POST['license_key']) ? trim($_POST['license_key']) : '';
// domain は受け取りますが、判定には使いません（無制限仕様）

if (empty($license_key)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'License key is required.']);
    exit;
}

// --- 3. データベース接続 ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// --- 4. 認証ロジック (ドメイン不問) ---
try {
    // ライセンスキーが存在し、かつ status が 'active' であることを確認
    $stmt = $pdo->prepare("SELECT id, status FROM licenses WHERE license_key = ?");
    $stmt->execute([$license_key]);
    $license = $stmt->fetch();

    if (!$license) {
        echo json_encode(['status' => 'error', 'message' => 'License key not found.']);
        exit;
    }

    if ($license['status'] !== 'active') {
        echo json_encode(['status' => 'error', 'message' => 'This license is inactive or expired.']);
        exit;
    }

    // 統計のために最終認証日時だけは更新しておく（ドメインは無視）
    $update_stmt = $pdo->prepare("UPDATE licenses SET last_verified_at = NOW() WHERE id = ?");
    $update_stmt->execute([$license['id']]);

    echo json_encode(['status' => 'success', 'message' => 'License is valid (Unlimited Domains).']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error during verification.']);
}
