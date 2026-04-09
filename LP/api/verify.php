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
$user_email = isset($_POST['user_email']) ? trim($_POST['user_email']) : ''; // メール判定用

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
    $stmt = $pdo->prepare("SELECT id, status, user_email FROM licenses WHERE license_key = ?");
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

    // user_email がパラメータとして渡されている場合、一致するか検証する
    if (!empty($user_email)) {
        if (strcasecmp($license['user_email'], $user_email) !== 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email address does not match the license record.']);
            exit;
        }
    }
    
    // リセットメール送信要求がある場合は、中央サーバー(Gamitaka Tools)からメールを送信する
    $send_reset_mail = isset($_POST['send_reset_mail']) ? $_POST['send_reset_mail'] : '';
    $reset_url = isset($_POST['reset_url']) ? trim($_POST['reset_url']) : '';
    
    if ($send_reset_mail === '1' && !empty($reset_url) && !empty($user_email)) {
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");

        $subject = "【Threads_Style】パスワードリセットのご案内";
        $body = "Threads_Styleの管理画面パスワードリセットがリクエストされました。\n\n";
        $body .= "以下のURLにアクセスして、新しいパスワードを設定してください。\n";
        $body .= "（このリンクの有効期限は発行から2時間です）\n\n";
        $body .= $reset_url . "\n\n";
        $body .= "※お心当たりがない場合は、このメールを破棄してください。\n\n";
        $body .= "--------------------------------------------------\n";
        $body .= "Gamitaka Tools\n";
        $body .= "https://www.gamitaka.com/\n";
        $body .= "--------------------------------------------------";

        $from_email = defined('FROM_EMAIL') ? FROM_EMAIL : 'no-reply@gamitaka.com';
        $support_email = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : $from_email;
        $from_name = mb_encode_mimeheader("Gamitaka Tools", "UTF-8", "B") . " <{$from_email}>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . $from_name . "\r\n";
        $headers .= "Reply-To: " . $support_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";

        if (!mb_send_mail($user_email, $subject, $body, $headers, "-f {$from_email}")) {
             echo json_encode(['status' => 'error', 'message' => 'Failed to send email from central server.']);
             exit;
        }
    }

    // 統計のために最終認証日時だけは更新しておく（カラムが無い場合はスキップ）
    try {
        $update_stmt = $pdo->prepare("UPDATE licenses SET last_verified_at = NOW() WHERE id = ?");
        $update_stmt->execute([$license['id']]);
    } catch (PDOException $e) {
        // last_verified_atカラムが存在しない場合は無視
    }

    echo json_encode(['status' => 'success', 'message' => 'License is valid (Unlimited Domains).']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error during verification.']);
}
