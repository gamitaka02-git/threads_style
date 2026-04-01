<?php
/**
 * ============================================================
 * マスター Cron ディスパッチャー - ThreadsStyle
 * ============================================================
 * 役割:
 * サーバーのcrontabから定期的に（1時間に1回推奨）呼び出され、
 * 以下の処理を順番に実行する。
 *
 * 1. 予約投稿: scheduled_at が現在時刻を過ぎた投稿を Threads API で公開
 * 2. トークン自動更新: 有効期限が7日以内のトークンを自動リフレッシュ
 * 3. インサイト取得: 投稿済みの直近投稿のエンゲージメントデータを取得
 * 4. フォロワー数記録: 現在のフォロワー数をログに追加
 * 5. 最終実行時刻の記録
 *
 * 【設定方法】
 * サーバーのCron設定に以下を登録してください（1時間に1回）:
 * 0 * * * * cd /path/to/admin; /usr/bin/php8.3 cron_dispatcher.php > /dev/null 2>&1
 *
 * ============================================================
 */

// タイムゾーンの設定（日本時間）
date_default_timezone_set('Asia/Tokyo');

// CLI実行チェック（Webからの直接アクセスを防止）
if (php_sapi_name() !== 'cli' && !defined('CRON_VIA_WEB')) {
    // Webからのアクセスも許可する（cron_web.phpから呼ばれる場合）
    // ただしセキュリティトークンチェック
    $token = $_GET['token'] ?? '';
    $expected = '';

    // config.phpがある場合はトークンをチェック
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
        $expected = defined('SECRET_TOKEN') ? SECRET_TOKEN : '';
    }

    if (empty($expected) || $token !== $expected) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
}

// 出力バッファリング（CLI/Web両対応）
$log = [];
function cron_log($message) {
    global $log;
    $time = date('Y-m-d H:i:s');
    $log[] = "[{$time}] {$message}";
    // ファイルへのデバッグログは無効（デバッグ時は下記をコメント解除）
    // file_put_contents(__DIR__ . '/cron_debug.log', "[{$time}] {$message}\n", FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo "[{$time}] {$message}\n";
    }
}

cron_log("=== ThreadsStyle Cron ディスパッチャー開始 ===");

// DB接続
$dbPath = __DIR__ . '/database.sqlite';
if (!file_exists($dbPath)) {
    cron_log("ERROR: database.sqlite が見つかりません");
    exit(1);
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON");
} catch (Exception $e) {
    cron_log("ERROR: DB接続失敗 - " . $e->getMessage());
    exit(1);
}

// config読み込み
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// ヘルパー関数 (init.phpが読み込まれていないCLI実行時用)
if (!function_exists('get_config')) {
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
}

if (!function_exists('set_config')) {
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
}

// Threads API クラスの読み込み
require_once __DIR__ . '/api/ThreadsAPI.php';
$api = new ThreadsAPI();

// ============================================================
// 1. 予約投稿の実行
// ============================================================
cron_log("--- 予約投稿チェック ---");

$auto_post_enabled = '1'; // 常に予約投稿を有効にする
cron_log("auto_post_enabled (forced) = " . $auto_post_enabled);

if ($auto_post_enabled !== '1') {
    cron_log("予約投稿は無効です。スキップします。");
} else {
    $variance = (int)get_config('post_interval_variance', 30);

    // 現在時刻 ± ゆらぎ の範囲内に予約されている投稿を検索
    $now = date('Y-m-d H:i:s');
    cron_log("現在時刻: {$now}");

    $stmt = $pdo->prepare("
        SELECT * FROM posts 
        WHERE status = 'scheduled' 
        AND scheduled_at <= :now 
        ORDER BY scheduled_at ASC, thread_order ASC
    ");
    $stmt->execute([':now' => $now]);
    $pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pending_posts)) {
        cron_log("投稿すべきデータはありません。");
        
        // デバッグ: 全予約投稿の状態を確認
        $stmt2 = $pdo->query("SELECT id, status, scheduled_at FROM posts WHERE status = 'scheduled' ORDER BY scheduled_at ASC LIMIT 5");
        $future = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($future)) {
            foreach ($future as $f) {
                cron_log("  待機中: ID={$f['id']} scheduled_at={$f['scheduled_at']}");
            }
        } else {
            cron_log("  scheduled状態の投稿は0件です。");
        }
    } else {
        cron_log(count($pending_posts) . "件の予約投稿があります。");

        // スレッドグループをまとめる
        $groups = [];
        $singles = [];
        foreach ($pending_posts as $post) {
            if ($post['thread_group_id']) {
                $groups[$post['thread_group_id']][] = $post;
            } else {
                $singles[] = $post;
            }
        }

        // 単独投稿の処理
        foreach ($singles as $post) {
            cron_log("投稿処理開始 ID:{$post['id']} scheduled_at:{$post['scheduled_at']}");
            
            // ゆらぎ（ランダム遅延）
            if ($variance > 0) {
                $delay = rand(0, min($variance * 60, 60)); // 最大60秒
                cron_log("ゆらぎ遅延: {$delay}秒（投稿ID: {$post['id']}）");
                sleep($delay);
            }

            cron_log("API呼び出し開始 ID:{$post['id']} content_length:" . strlen($post['content']) . ($post['media_url'] ? " image:yes" : "") . ($post['topic_tag'] ? " tag:{$post['topic_tag']}" : ""));
            $result = $api->publishPost($post['content'], $post['ai_label'] == 1, $post['media_url'] ?? '', $post['topic_tag'] ?? '');
            cron_log("API呼び出し完了 ID:{$post['id']} result: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['success']) {
                $now_jst = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("
                    UPDATE posts SET status = 'posted', posted_at = :now,
                    threads_post_id = :tid, threads_media_id = :mid
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':now' => $now_jst,
                    ':tid' => $result['post_id'] ?? '',
                    ':mid' => $result['media_id'] ?? '',
                    ':id'  => $post['id'],
                ]);
                cron_log("✅ 投稿成功 ID:{$post['id']}");
            } else {
                $stmt = $pdo->prepare("UPDATE posts SET status = 'failed' WHERE id = :id");
                $stmt->execute([':id' => $post['id']]);
                cron_log("❌ 投稿失敗 ID:{$post['id']} - " . ($result['message'] ?? '不明なエラー'));
            }

            // レートリミット対策（投稿間隔）
            sleep(3);
        }

        // スレッドグループの処理
        foreach ($groups as $group_id => $thread_posts) {
            usort($thread_posts, fn($a, $b) => $a['thread_order'] - $b['thread_order']);
            $contents = array_column($thread_posts, 'content');

            cron_log("スレッド投稿開始 グループID:{$group_id}");
            $result = $api->publishThread($contents);

            if ($result['success']) {
                $now_jst = date('Y-m-d H:i:s');
                foreach ($thread_posts as $i => $post) {
                    $tid = isset($result['results'][$i]) ? ($result['results'][$i]['post_id'] ?? '') : '';
                    $stmt = $pdo->prepare("
                        UPDATE posts SET status = 'posted', posted_at = :now, threads_post_id = :tid
                        WHERE id = :id
                    ");
                    $stmt->execute([':now' => $now_jst, ':tid' => $tid, ':id' => $post['id']]);
                }
                cron_log("✅ スレッド投稿成功 グループID:{$group_id}");
            } else {
                foreach ($thread_posts as $post) {
                    $stmt = $pdo->prepare("UPDATE posts SET status = 'failed' WHERE id = :id");
                    $stmt->execute([':id' => $post['id']]);
                }
                cron_log("❌ スレッド投稿失敗 グループID:{$group_id} - " . ($result['message'] ?? ''));
            }

            sleep(5);
        }
    }
}

// ============================================================
// 2. トークン自動更新（有効期限が7日以内の場合）
// ============================================================
cron_log("--- トークン更新チェック ---");

$token_expires = get_config('threads_token_expires_at');
if (!empty($token_expires)) {
    $expires_ts = strtotime($token_expires);
    $days_left = ($expires_ts - time()) / 86400;

    if ($days_left <= 7 && $days_left > 0) {
        cron_log("トークン残り" . round($days_left, 1) . "日 - 自動更新を実行します");
        $refresh_result = $api->refreshToken();

        if ($refresh_result['success']) {
            // DB保存
            set_config('threads_access_token', $refresh_result['access_token']);
            set_config('threads_token_expires_at', $refresh_result['expires_at']);

            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('auto_refresh', 'success', :msg)");
            $stmt->execute([':msg' => '自動更新成功: ' . $refresh_result['expires_at']]);

            cron_log("✅ トークン更新成功 新しい有効期限: " . $refresh_result['expires_at']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('auto_refresh', 'failed', :msg)");
            $stmt->execute([':msg' => $refresh_result['message'] ?? '']);

            cron_log("❌ トークン更新失敗: " . ($refresh_result['message'] ?? ''));
        }
    } elseif ($days_left <= 0) {
        cron_log("⚠️ トークンが期限切れです！手動で更新してください。");
    } else {
        cron_log("トークン残り" . round($days_left) . "日 - 更新不要");
    }
} else {
    cron_log("トークン有効期限が未設定です。");
}

// ============================================================
// 3. インサイト取得（直近10件の投稿済み投稿）
// ============================================================
cron_log("--- インサイト取得 ---");

$stmt = $pdo->query("SELECT id, threads_post_id FROM posts WHERE status = 'posted' AND threads_post_id != '' ORDER BY posted_at DESC LIMIT 10");
$recent_posted = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insight_count = 0;
foreach ($recent_posted as $post) {
    $result = $api->getPostInsights($post['threads_post_id']);
    if ($result['success'] && !empty($result['metrics'])) {
        $m = $result['metrics'];
        $stmt = $pdo->prepare("
            INSERT INTO post_insights (post_id, threads_post_id, likes, replies, reposts, quotes, views)
            VALUES (:post_id, :tid, :likes, :replies, :reposts, :quotes, :views)
        ");
        $stmt->execute([
            ':post_id'  => $post['id'],
            ':tid'      => $post['threads_post_id'],
            ':likes'    => $m['likes'] ?? 0,
            ':replies'  => $m['replies'] ?? 0,
            ':reposts'  => $m['reposts'] ?? 0,
            ':quotes'   => $m['quotes'] ?? 0,
            ':views'    => $m['views'] ?? 0,
        ]);
        $insight_count++;
    }
    usleep(300000); // 300ms
}
cron_log("{$insight_count}件のインサイトを取得しました。");

// ============================================================
// 4. フォロワー数記録
// ============================================================
cron_log("--- フォロワー数取得 ---");

$profile_result = $api->getUserProfile();
if ($profile_result['success'] && isset($profile_result['profile'])) {
    // Threads APIのプロフィールにはフォロワー数が含まれない場合がある
    // User Insights APIを使用
    $access_token = get_config('threads_access_token');
    $user_id = get_config('threads_user_id');

    if (!empty($access_token) && !empty($user_id)) {
        $insights_url = "https://graph.threads.net/v1.0/{$user_id}/threads_insights?metric=followers_count&access_token={$access_token}";
        $ch = curl_init($insights_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        if (isset($data['data'][0]['total_value']['value'])) {
            $follower_count = (int)$data['data'][0]['total_value']['value'];
            $stmt = $pdo->prepare("INSERT INTO follower_history (follower_count) VALUES (:count)");
            $stmt->execute([':count' => $follower_count]);
            cron_log("フォロワー数: {$follower_count}");
        } else {
            cron_log("フォロワー数の取得に失敗しました。");
        }
    }
} else {
    cron_log("プロフィール取得スキップ（API未設定）");
}

// ============================================================
// 5. 最終実行時刻の記録
// ============================================================
set_config('cron_last_run', date('Y-m-d H:i:s'));
cron_log("=== Cron ディスパッチャー完了 ===");

// Web経由の場合はJSON応答
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'log' => $log]);
}
