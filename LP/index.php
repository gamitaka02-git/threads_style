<?php
/**
 * ============================================================
 * Threads Style - Landing Page
 * ============================================================
 * 役割: 商品紹介およびStripe決済への誘導を行うトップページ
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads Style | 次世代のThreads運用・分析支援ツール</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <meta name="robots" content="noindex">
</head>
<body>
    <header>
        <div class="logo">Threads Style</div>
    </header>

    <main class="container">
        <section class="hero">
            <h1>Threads Style<br>運用をもっと自由に。</h1>
            <p>スケジュール投稿から複数画像アップロードまで。<br>あなたのThreads運用を強力にバックアップするツール。</p>
        </section>

        <section class="cta-card">
            <div class="price-tag">¥2,980 <span>(税込)</span></div>
            <p class="price-subtitle">買い切りライセンス</p>
            
            <ul class="features-list">
                <li>予約投稿（カレンダー管理）</li>
                <li>複数画像（カルーセル）投稿</li>
                <li>AIラベル対応</li>
                <li>インサイト分析機能</li>
                <li>無期限アップデート提供</li>
            </ul>

            <a href="checkout.php" class="btn-buy">今すぐ購入する</a>
            
            <p class="cta-note">
                Stripeによる安全な決済がご利用いただけます
            </p>
        </section>
    </main>

    <footer>
        <div class="footer-links">
            <a href="#">特定商取引法に基づく表記</a>
            <a href="#">プライバシーポリシー</a>
            <a href="#">利用規約</a>
        </div>
        <p>&copy; 2026 Threads Style / Gamitaka Tools. All rights reserved.</p>
    </footer>
</body>
</html>
