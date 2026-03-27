<?php
/**
 * ============================================================
 * Stripe Webhook 受信 → ライセンス自動発行 → メール送信
 * ============================================================
 * 役割: Stripeからの決済完了イベント(checkout.session.completed)を受け取り、
 *       1. ランダムなライセンスキーを生成
 *       2. MySQLデータベースに保存
 *       3. 購入者にライセンスキーをメール送信
 *
 * 【カスタマイズ箇所】
 * - EXPECTED_PRICE_ID: Stripeダッシュボードの価格IDに書き換える
 * - ライセンスキーのプレフィックス（YOUR_PREFIX）を変更する
 * - メール本文中のアプリ名・URL・連絡先を書き換える
 * ============================================================
 */

// 1. 設定ファイルとStripeライブラリの読み込み
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../admin/stripe-php/init.php';

// この商品の価格ID（Stripeダッシュボードから取得）
// 同一Stripeアカウントで複数商品を扱う場合、自分の商品以外のイベントを無視するために使用
define('EXPECTED_PRICE_ID', '[ここにStripe価格ID（price_xxx）を入力]');

// データベース接続関数
function get_db_connection()
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// 2. Stripeからのリクエストを取得
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    // 署名検証（なりすまし防止）
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit();
}

// 3. 決済完了イベントの処理
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    // --- Price ID による商品フィルタリング ---
    // 同一Stripeアカウントで複数Webhookを運用している場合、
    // 自分の商品以外の購入イベントを無視する
    $line_items = \Stripe\Checkout\Session::allLineItems($session->id, ['limit' => 1]);
    $purchased_price_id = $line_items->data[0]->price->id ?? null;

    if ($purchased_price_id !== EXPECTED_PRICE_ID) {
        error_log("YOUR_APP_NAME Webhook: 対象外の商品のため処理をスキップ (price_id: $purchased_price_id)");
        http_response_code(200);
        exit();
    }

    $customer_email = $session->customer_details->email;

    // ランダムなライセンスキー生成 (例: YOUR_PREFIX-ABCD-1234-EFGH)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $key_part = function () use ($chars) {
        return substr(str_shuffle($chars), 0, 4);
    };
    $license_key = "YOUR_PREFIX-" . $key_part() . "-" . $key_part() . "-" . $key_part();

    // DB（MySQL）に保存
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("INSERT INTO licenses (license_key, user_email, status, created_at) VALUES (?, ?, 'active', NOW())");
        $stmt->execute([$license_key, $customer_email]);

        error_log("License Issued: $license_key for $customer_email");

        // --- ライセンスキーのメール送信処理 ---
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");

        $subject = "【重要】ライセンスキーのご送付 / YOUR_APP_NAME｜Gamitaka Tools";
        
        $body = "この度は「YOUR_APP_NAME」をご購入いただき、誠にありがとうございます。\n\n";
        $body .= "以下の通り、ライセンスキーを発行いたしました。\n\n";
        $body .= "【ライセンスキー】\n";
        $body .= "{$license_key}\n\n";
        $body .= "【設置・設定マニュアル】\n";
        $body .= "以下のURLよりマニュアルをご参照の上、ツールの設置・設定をお願いいたします。\n";
        $body .= "[ここにマニュアルURLを入力]\n\n";
        $body .= "【お問い合わせ先】\n";
        $body .= "ご不明な点がございましたら、以下のメールアドレスまでお問い合わせください。\n";
        $body .= (defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : '[ここにサポートメールアドレスを入力]') . "\n\n";
        $body .= "今後とも何卒よろしくお願い申し上げます。";

        $from_email = defined('FROM_EMAIL') ? FROM_EMAIL : '[ここに送信元メールアドレスを入力]';
        $support_email = defined('SUPPORT_EMAIL') ? SUPPORT_EMAIL : $from_email;

        // 送信元表示名（日本語をMIMEエンコード）
        $from_name = mb_encode_mimeheader("YOUR_APP_NAME 開発事務局", "UTF-8", "B") . " <{$from_email}>";

        // メールヘッダー
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . $from_name . "\r\n";
        $headers .= "Reply-To: " . $support_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit";

        // メール送信の成否をログに記録（失敗しても例外にせず後続の200 OKへ進む）
        if (!mb_send_mail($customer_email, $subject, $body, $headers, "-f {$from_email}")) {
            error_log("Mail Deliver Error: Failed to send license key to $customer_email");
        } else {
            error_log("Mail Delivered: License key sent to $customer_email");
        }
    } catch (PDOException $e) {
        error_log("DB Error in Webhook: " . $e->getMessage());
        http_response_code(500);
        exit();
    }
}

http_response_code(200);
