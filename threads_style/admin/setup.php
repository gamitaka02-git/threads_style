<?php
/**
 * ============================================================
 * 初期設定（セットアップ）画面 - ThreadsStyle
 * ============================================================
 * 役割: ツール初回アクセス時に管理者パスワードの設定と
 *       SQLiteデータベースの初期化（全テーブル作成）を行う。
 *       セットアップ完了後はログイン画面へリダイレクト。
 * ============================================================
 */
session_start();

$dbPath = __DIR__ . '/database.sqlite';
$setup_done = false;

// 既にDBが存在し、セットアップも完了しているかチェック
if (file_exists($dbPath)) {
    try {
        $pdo_check = new PDO('sqlite:' . $dbPath);
        $pdo_check->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo_check->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'config'");
        if ($stmt->fetch() !== false) {
            $stmt = $pdo_check->query("SELECT value FROM config WHERE key = 'setup_complete'");
            if ($stmt->fetchColumn() === '1') {
                $setup_done = true;
            }
        }
    } catch (Exception $e) {
        // DBファイルはあっても中身が壊れている場合はセットアップ処理に進む
    }
}

// セットアップ完了済みなら、ログイン画面へ
if ($setup_done) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($password_confirm)) {
        $error = 'パスワードと確認用パスワードの両方を入力してください。';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません。';
    } elseif (strlen($password) < 4) {
        $error = 'パスワードは4文字以上で設定してください。';
    } elseif (empty(trim($_POST['license_key'] ?? ''))) {
        $error = 'ライセンスキーを入力してください。';
    } else {
        try {
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }

            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("PRAGMA foreign_keys = ON");
            $pdo->exec("PRAGMA journal_mode = WAL");

            // ----------------------------------------------------------
            // config テーブル（設定管理・必須）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS config (
                    key TEXT PRIMARY KEY,
                    value TEXT
                )
            ");

            // ----------------------------------------------------------
            // posts テーブル（投稿データ管理）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    thread_group_id INTEGER DEFAULT NULL,
                    thread_order INTEGER DEFAULT 0,
                    content TEXT NOT NULL,
                    media_url TEXT DEFAULT '',
                    status TEXT DEFAULT 'draft',
                    scheduled_at DATETIME DEFAULT NULL,
                    posted_at DATETIME DEFAULT NULL,
                    threads_media_id TEXT DEFAULT '',
                    threads_post_id TEXT DEFAULT '',
                    is_ai_generated INTEGER DEFAULT 0,
                    ai_label INTEGER DEFAULT 0,
                    source_type TEXT DEFAULT 'manual',
                    source_url TEXT DEFAULT '',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // ----------------------------------------------------------
            // post_insights テーブル（投稿エンゲージメント）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS post_insights (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    post_id INTEGER NOT NULL,
                    threads_post_id TEXT DEFAULT '',
                    likes INTEGER DEFAULT 0,
                    replies INTEGER DEFAULT 0,
                    reposts INTEGER DEFAULT 0,
                    quotes INTEGER DEFAULT 0,
                    views INTEGER DEFAULT 0,
                    reach INTEGER DEFAULT 0,
                    fetched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                )
            ");

            // ----------------------------------------------------------
            // top_posts テーブル（優秀投稿DB）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS top_posts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    post_id INTEGER NOT NULL,
                    reason TEXT DEFAULT '',
                    engagement_score REAL DEFAULT 0,
                    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                )
            ");

            // ----------------------------------------------------------
            // keyword_monitors テーブル（キーワード監視設定）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS keyword_monitors (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    keyword TEXT NOT NULL,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // ----------------------------------------------------------
            // keyword_alerts テーブル（キーワードアラート結果）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS keyword_alerts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    monitor_id INTEGER NOT NULL,
                    threads_post_id TEXT DEFAULT '',
                    author_username TEXT DEFAULT '',
                    content_preview TEXT DEFAULT '',
                    found_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    is_read INTEGER DEFAULT 0,
                    FOREIGN KEY (monitor_id) REFERENCES keyword_monitors(id) ON DELETE CASCADE
                )
            ");

            // ----------------------------------------------------------
            // style_guides テーブル（AIスタイルガイド）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS style_guides (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    content_markdown TEXT DEFAULT '',
                    analysis_data TEXT DEFAULT '',
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // ----------------------------------------------------------
            // token_logs テーブル（トークン更新履歴）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS token_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    action TEXT NOT NULL,
                    status TEXT NOT NULL,
                    message TEXT DEFAULT '',
                    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // ----------------------------------------------------------
            // follower_history テーブル（フォロワー数推移）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS follower_history (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    follower_count INTEGER NOT NULL,
                    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // ----------------------------------------------------------
            // posting_schedule テーブル（自動投稿スケジュール）
            // ----------------------------------------------------------
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS posting_schedule (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    time_slot TEXT NOT NULL,
                    variance_minutes INTEGER DEFAULT 30,
                    is_active INTEGER DEFAULT 1,
                    days_of_week TEXT DEFAULT '1,2,3,4,5,6,7',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // パスワードをハッシュ化して保存
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO config (key, value) VALUES ('admin_password', :pass)");
            $stmt->execute([':pass' => $hashed_password]);

            // セットアップ完了フラグを保存
            $pdo->exec("INSERT INTO config (key, value) VALUES ('setup_complete', '1')");

            // デフォルト設定の投入（フォームから入力された値を反映）
            $license_key = trim($_POST['license_key'] ?? '');
            $threads_token = trim($_POST['threads_access_token'] ?? '');
            $threads_user_id = trim($_POST['threads_user_id'] ?? '');

            $defaults = [
                ['threads_access_token', $threads_token],
                ['threads_user_id', $threads_user_id],
                ['threads_app_id', trim($_POST['threads_app_id'] ?? '')],
                ['threads_app_secret', trim($_POST['threads_app_secret'] ?? '')],
                ['threads_token_expires_at', $threads_token ? date('Y-m-d', strtotime('+60 days')) : ''],
                ['gemini_api_key', ''],
                ['gemini_model', 'gemini-3.1-flash-lite-preview'],
                ['license_key', $license_key],
                ['auto_post_enabled', '0'],
                ['ai_label_default', '0'],
                ['post_interval_variance', '30'],
                ['top_post_threshold', '50'],
            ];
            $stmt = $pdo->prepare("INSERT OR IGNORE INTO config (key, value) VALUES (:key, :value)");
            foreach ($defaults as $d) {
                $stmt->execute([':key' => $d[0], ':value' => $d[1]]);
            }

            // ログイン画面へリダイレクト
            $_SESSION['setup_success'] = '初期設定が完了しました。作成したパスワードでログインしてください。';
            header('Location: login.php');
            exit;

        } catch (Exception $e) {
            $error = 'データベースの初期化に失敗しました: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>初期設定 - ThreadsStyle</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">

    <div class="auth-card">
        <div class="auth-logo">
            <h1>ThreadsStyle</h1>
            <p>初期設定</p>
        </div>

        <p style="color: var(--color-text-secondary); font-size: var(--font-size-sm); margin-bottom: var(--space-lg); line-height: 1.7;">
            ようこそ！<br>
            使用を開始する前に、管理画面にログインするための管理者パスワードを設定してください。
        </p>

        <?php if ($error): ?>
            <div class="auth-alert error" style="margin-bottom: var(--space-md);">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="setup.php" class="auth-form">
            <div>
                <label class="form-label">管理者パスワード (4文字以上) <span style="color:var(--color-error);">*必須</span></label>
                <input type="password" name="password" required autofocus
                    class="auth-input"
                    placeholder="パスワード">
            </div>
            <div>
                <label class="form-label">管理者パスワード (確認用) <span style="color:var(--color-error);">*必須</span></label>
                <input type="password" name="password_confirm" required
                    class="auth-input"
                    placeholder="もう一度入力">
            </div>

            <div style="border-top:1px solid var(--color-border); padding-top:var(--space-lg); margin-top:var(--space-md);">
                <p style="color:var(--color-text-secondary); font-size:var(--font-size-xs); margin-bottom:var(--space-md); line-height:1.7;">
                    ライセンスキーは必須です。その他の項目はあとから設定画面で変更できます。
                </p>
                <div>
                    <label class="form-label">ライセンスキー <span style="color:var(--color-error);">*必須</span></label>
                    <input type="text" name="license_key" required
                        class="auth-input"
                        placeholder="XXXX-XXXX-XXXX-XXXX">
                </div>
                <div style="margin-top:var(--space-md);">
                    <label class="form-label">Threads アクセストークン（任意）</label>
                    <input type="text" name="threads_access_token"
                        class="auth-input"
                        placeholder="アクセストークン">
                </div>
                <div style="margin-top:var(--space-md);">
                    <label class="form-label">Threads ユーザーID（任意）</label>
                    <input type="text" name="threads_user_id"
                        class="auth-input"
                        placeholder="ユーザーID">
                </div>
                <div style="margin-top:var(--space-md);">
                    <label class="form-label">Meta App ID（任意）</label>
                    <input type="text" name="threads_app_id"
                        class="auth-input"
                        placeholder="App ID">
                </div>
                <div style="margin-top:var(--space-md);">
                    <label class="form-label">Meta App Secret（任意）</label>
                    <input type="password" name="threads_app_secret"
                        class="auth-input"
                        placeholder="App Secret">
                </div>
            </div>

            <button type="submit" class="auth-submit">設定を完了する</button>
        </form>
    </div>

</body>
</html>
