<?php
/**
 * ============================================================
 * ログイン画面
 * ============================================================
 * 役割: セットアップ完了後に管理者がツール管理画面にログインするための画面。
 *       パスワード認証後、セッションにログイン状態を記録する。
 *
 * 【カスタマイズ箇所】
 * - YOUR_APP_NAME: ツール名に書き換える
 * ============================================================
 */

// init.php にリダイレクトとセッション開始があるので、最初に読み込む
require_once __DIR__ . '/init.php';

// すでにログイン済みの場合はメイン画面へ
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
$success_message = '';

// setup.phpからのリダイレクト時にメッセージがあれば取得
if (isset($_SESSION['setup_success'])) {
    $success_message = $_SESSION['setup_success'];
    unset($_SESSION['setup_success']); // メッセージを削除
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // パスワードを取得
        $stmt = $pdo->query("SELECT value FROM config WHERE key = 'admin_password'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($password, $row['value'])) {
            // ログイン成功
            $_SESSION['admin_logged_in'] = true;
            header("Location: index.php");
            exit;
        } else {
            $error = 'パスワードが間違っています。';
        }

    } catch (Exception $e) {
        $error = "データベースエラーが発生しました: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - YOUR_APP_NAME</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm border-t-4 border-blue-600">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">YOUR_APP_NAME｜ログイン</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                <input type="password" name="password" required autofocus
                    class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="パスワードを入力">
            </div>
            <button type="submit"
                class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition shadow">ログイン</button>
        </form>
    </div>

</body>

</html>
