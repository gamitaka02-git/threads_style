<?php
/**
 * ============================================================
 * ログイン画面 - ThreadsStyle
 * ============================================================
 * 役割: セットアップ完了後に管理者がツール管理画面にログインするための画面。
 *       パスワード認証後、セッションにログイン状態を記録する。
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
    unset($_SESSION['setup_success']);
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
    <title>ログイン - ThreadsStyle</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">

    <div class="auth-card">
        <div class="auth-logo">
            <h1>ThreadsStyle</h1>
            <p>管理画面ログイン</p>
        </div>

        <?php if ($error): ?>
            <div class="auth-alert error" style="margin-bottom: var(--space-md);">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="auth-alert success" style="margin-bottom: var(--space-md);">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <div>
                <label class="form-label">パスワード</label>
                <input type="password" name="password" required autofocus
                    class="auth-input"
                    placeholder="パスワードを入力">
            </div>
            <button type="submit" class="auth-submit">ログイン</button>
        </form>
    </div>

</body>
</html>
