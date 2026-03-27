<?php
/**
 * ============================================================
 * 初期化処理 + ライセンス認証関数
 * ============================================================
 * 役割: 
 * 1. ツールのバージョン定義
 * 2. セットアップ完了チェック（未完了ならsetup.phpへリダイレクト）
 * 3. SQLiteデータベースの接続と自動マイグレーション
 * 4. ライセンス認証関数 check_license() の提供
 *
 * 【カスタマイズ箇所】
 * - TOOL_VERSION: リリースごとに更新
 * - テーブルのマイグレーション部分: ツール固有のテーブルを追加する場合は修正
 * ============================================================
 */

// 現在のツールのバージョン (このファイルは自動アップデートで上書きされます)
define('TOOL_VERSION', 'v1.0.0');

// 無限ループ防止
if (basename($_SERVER['PHP_SELF']) === 'setup.php') {
    return;
}

session_start();

$dbPath = __DIR__ . '/database.sqlite';
$setup_done = false;

if (file_exists($dbPath)) {
    try {
        // DBファイルがあってもテーブルが存在しない・壊れているケースを考慮
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 外部キー制約を有効化
        $pdo->exec("PRAGMA foreign_keys = ON");

        // configテーブルの存在確認
        $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'config'");
        if ($stmt->fetch() !== false) {
            // セットアップ完了フラグの確認
            $stmt = $pdo->query("SELECT value FROM config WHERE key = 'setup_complete'");
            if ($stmt->fetchColumn() === '1') {
                $setup_done = true;
            }
        }

        // ----------------------------------------------------------
        // 自動マイグレーション（既存環境対応）
        // ツール固有のテーブルがまだ存在しない場合に自動作成する。
        // 例: クリック解析用の click_logs テーブル
        // ----------------------------------------------------------
        // $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'click_logs'");
        // if ($stmt->fetch() === false) {
        //     $pdo->exec("
        //         CREATE TABLE IF NOT EXISTS click_logs (
        //             id INTEGER PRIMARY KEY AUTOINCREMENT,
        //             url_id INTEGER NOT NULL,
        //             clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        //             referer TEXT DEFAULT '',
        //             FOREIGN KEY (url_id) REFERENCES urls(id) ON DELETE CASCADE
        //         )
        //     ");
        // }

    } catch (Exception $e) {
        // DB接続に失敗した場合は未セットアップとみなし、セットアップ画面へ進む
        $setup_done = false;
    }
}

if (!$setup_done) {
    header('Location: setup.php');
    exit;
}

// 設定ファイルを読み込む
require_once __DIR__ . '/config.php';

/**
 * ライセンスキーを認証サーバーで検証する
 * @param string $license_key 検証するライセンスキー
 * @return bool 認証成功でtrue、失敗でfalse
 */
function check_license($license_key) {
    if (empty($license_key)) return false;

    $post_data = [
        'license_key' => $license_key,
        'domain' => $_SERVER['SERVER_NAME'],
        'api_token' => SECRET_TOKEN
    ];

    $ch = curl_init(AUTH_SERVER_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // Xserver同士の通信エラー対策
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) return false;

    $result = json_decode($response_body, true);
    return (isset($result['status']) && $result['status'] === 'success');
}
