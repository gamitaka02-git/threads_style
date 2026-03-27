<?php
/**
 * ============================================================
 * 初期設定（セットアップ）画面
 * ============================================================
 * 役割: ツール初回アクセス時に管理者パスワードの設定と
 *       SQLiteデータベースの初期化を行う。
 *       セットアップ完了後はログイン画面へリダイレクト。
 *
 * 【カスタマイズ箇所】
 * - YOUR_APP_NAME: ツール名に書き換える
 * - テーブル定義: ツール固有のテーブルを追加する場合は修正
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
        // DBファイルはあっても中身が壊れている場合などは、このままセットアップ処理に進む
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
    } else {
        try {
            // 念の為、既存のDBファイルがあれば削除してまっさらにする
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }

            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ----------------------------------------------------------
            // ★ ツール固有のテーブル定義はここに追加する
            // ----------------------------------------------------------
            // 例: URLs管理テーブル
            // $pdo->exec("
            //     CREATE TABLE IF NOT EXISTS urls (
            //         id INTEGER PRIMARY KEY AUTOINCREMENT,
            //         keyword TEXT UNIQUE,
            //         original_url TEXT,
            //         click_count INTEGER DEFAULT 0,
            //         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            //         updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            //     )
            // ");

            // 例: クリック解析ログテーブル
            // $pdo->exec("
            //     CREATE TABLE IF NOT EXISTS click_logs (
            //         id INTEGER PRIMARY KEY AUTOINCREMENT,
            //         url_id INTEGER NOT NULL,
            //         clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            //         referer TEXT DEFAULT '',
            //         FOREIGN KEY (url_id) REFERENCES urls(id) ON DELETE CASCADE
            //     )
            // ");

            // config テーブル作成（管理設定の保存用。必須）
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS config (
                    key TEXT PRIMARY KEY,
                    value TEXT
                )
            ");
            
            // パスワードをハッシュ化して保存
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO config (key, value) VALUES ('admin_password', :pass)");
            $stmt->execute([':pass' => $hashed_password]);

            // セットアップ完了フラグを保存
            $stmt = $pdo->prepare("INSERT INTO config (key, value) VALUES ('setup_complete', '1')");
            $stmt->execute();
            
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
    <title>初期設定 - YOUR_APP_NAME</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm border-t-4 border-blue-600">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">YOUR_APP_NAME｜初期設定</h1>
        <p class="text-sm text-gray-600 mb-4">
            ようこそ！<br>
            使用を開始する前に、管理画面にログインするための管理者パスワードを設定してください。
        </p>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="setup.php" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">管理者パスワード (4文字以上)</label>
                <input type="password" name="password" required autofocus
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="パスワード">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">管理者パスワード (確認用)</label>
                <input type="password" name="password_confirm" required
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="もう一度入力">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition shadow">設定を完了する</button>
        </form>
    </div>

</body>
</html>
