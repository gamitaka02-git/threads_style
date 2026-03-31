<?php
/**
 * ============================================================
 * 初期化処理 + ライセンス認証関数 - ThreadsStyle
 * ============================================================
 * 役割: 
 * 1. ツールのバージョン定義
 * 2. セットアップ完了チェック（未完了ならsetup.phpへリダイレクト）
 * 3. SQLiteデータベースの接続と自動マイグレーション
 * 4. ライセンス認証関数 check_license() の提供
 * ============================================================
 */

// 現在のツールのバージョン (このファイルは自動アップデートで上書きされます)
define('TOOL_VERSION', 'v1.0.2');

// 無限ループ防止
if (basename($_SERVER['PHP_SELF']) === 'setup.php') {
    return;
}

session_start();
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

$dbPath = __DIR__ . '/database.sqlite';
$setup_done = false;

if (file_exists($dbPath)) {
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 外部キー制約を有効化 & WALモード
        $pdo->exec("PRAGMA foreign_keys = ON");
        $pdo->exec("PRAGMA journal_mode = WAL");

        // configテーブルの存在確認
        $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'config'");
        if ($stmt->fetch() !== false) {
            $stmt = $pdo->query("SELECT value FROM config WHERE key = 'setup_complete'");
            if ($stmt->fetchColumn() === '1') {
                $setup_done = true;
            }
        }

        // ----------------------------------------------------------
        // 自動マイグレーション（既存環境対応）
        // アップデート時に新しいテーブルが追加された場合に自動作成する
        // ----------------------------------------------------------
        if ($setup_done) {
            // posts テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'posts'");
            if ($stmt->fetch() === false) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS posts (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        thread_group_id INTEGER DEFAULT NULL,
                        thread_order INTEGER DEFAULT 0,
                        content TEXT NOT NULL,
                        media_url TEXT DEFAULT '',
                        topic_tag TEXT DEFAULT '',
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
            } else {
                // 既存テーブルへの topic_tag カラム追加（マイグレーション）
                $cols = $pdo->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('topic_tag', $cols)) {
                    $pdo->exec("ALTER TABLE posts ADD COLUMN topic_tag TEXT DEFAULT ''");
                }
            }

            // post_insights テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'post_insights'");
            if ($stmt->fetch() === false) {
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
            }

            // top_posts テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'top_posts'");
            if ($stmt->fetch() === false) {
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
            }

            // keyword_monitors テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'keyword_monitors'");
            if ($stmt->fetch() === false) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS keyword_monitors (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        keyword TEXT NOT NULL,
                        is_active INTEGER DEFAULT 1,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            }

            // keyword_alerts テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'keyword_alerts'");
            if ($stmt->fetch() === false) {
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
            }

            // style_guides テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'style_guides'");
            if ($stmt->fetch() === false) {
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
            }

            // token_logs テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'token_logs'");
            if ($stmt->fetch() === false) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS token_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        action TEXT NOT NULL,
                        status TEXT NOT NULL,
                        message TEXT DEFAULT '',
                        logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            }

            // follower_history テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'follower_history'");
            if ($stmt->fetch() === false) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS follower_history (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        follower_count INTEGER NOT NULL,
                        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )
                ");
            }

            // posting_schedule テーブル
            $stmt = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'posting_schedule'");
            if ($stmt->fetch() === false) {
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
            }
        }

    } catch (Exception $e) {
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
 * configテーブルから設定値を取得するヘルパー関数
 */
function get_config($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE key = :key");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * configテーブルに設定値を保存するヘルパー関数
 */
function set_config($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO config (key, value) VALUES (:key, :value)");
        $stmt->execute([':key' => $key, ':value' => $value]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * ライセンスキーを認証サーバーで検証する
 * @return bool 単体呼び出し用（後方互換）
 */
function check_license($license_key) {
    $result = check_license_detail($license_key);
    return $result['valid'];
}

/**
 * ライセンスキーを認証サーバーで検証する（詳細版）
 * @return array ['valid' => bool, 'http_code' => int, 'error' => string, 'response' => string]
 */
function check_license_detail($license_key) {
    if (empty($license_key)) {
        return ['valid' => false, 'http_code' => 0, 'error' => 'ライセンスキーが空です', 'response' => ''];
    }

    if (!defined('AUTH_SERVER_URL') || empty(AUTH_SERVER_URL)) {
        return ['valid' => false, 'http_code' => 0, 'error' => 'AUTH_SERVER_URL が config.php に定義されていません', 'response' => ''];
    }

    if (!defined('SECRET_TOKEN') || empty(SECRET_TOKEN)) {
        return ['valid' => false, 'http_code' => 0, 'error' => 'SECRET_TOKEN が config.php に定義されていません', 'response' => ''];
    }

    $post_data = [
        'license_key' => $license_key,
        'domain' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'api_token' => SECRET_TOKEN
    ];

    $ch = curl_init(AUTH_SERVER_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['valid' => false, 'http_code' => $http_code, 'error' => 'cURL通信エラー: ' . $curl_error, 'response' => ''];
    }

    if ($http_code !== 200) {
        return ['valid' => false, 'http_code' => $http_code, 'error' => "HTTPステータス {$http_code} が返されました", 'response' => $response_body];
    }

    $result = json_decode($response_body, true);
    if ($result === null) {
        return ['valid' => false, 'http_code' => $http_code, 'error' => 'レスポンスがJSONではありません', 'response' => mb_strimwidth($response_body, 0, 500, '...', 'UTF-8')];
    }

    $is_valid = (isset($result['status']) && $result['status'] === 'success');
    return [
        'valid' => $is_valid,
        'http_code' => $http_code,
        'error' => $is_valid ? '' : ($result['message'] ?? '認証失敗（statusがsuccessではありません）'),
        'response' => $response_body
    ];
}
