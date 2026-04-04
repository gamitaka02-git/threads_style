<?php
/**
 * ============================================================
 * Stripe 決済リダイレクト
 * ============================================================
 * 役割: Stripe Checkout セッションを作成し、決済ページへリダイレクトする。
 * 
 * 【カスタマイズ箇所】
 * - $price_id: Stripeダッシュボードから取得した「価格ID (Price ID)」に書き換える
 * ============================================================
 */

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/stripe-php/init.php';

// エラー出力（本番環境では適宜OFFにするかログ出力にする）
ini_set('display_errors', 1);
error_reporting(E_ALL);

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// HTTPリダイレクト用のドメインの取得（動的生成）
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] . '';
$base_url = $protocol . $domainName . dirname($_SERVER['REQUEST_URI']);

// Stripeダッシュボードから取得した商品の「価格ID (Price ID)」
$price_id = STRIPE_PRICE_ID; 

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $price_id,
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        // 成功時、キャンセル時の戻り先URL
        'success_url' => $base_url . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $base_url . '/index.php',
    ]);

    // Checkoutページへリダイレクト
    header("Location: " . $session->url);
    exit();

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo "Stripe Error: " . htmlspecialchars($e->getMessage());
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo "System Error: " . htmlspecialchars($e->getMessage());
    exit();
}
