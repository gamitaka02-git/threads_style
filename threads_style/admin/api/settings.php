<?php
/**
 * ============================================================
 * 設定管理 API エンドポイント - ThreadsStyle
 * ============================================================
 * 各種設定の保存・読み込み、ライセンス認証チェック、トークン更新
 * ============================================================
 */
require_once __DIR__ . '/../init.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

switch ($action) {

    // ----- 設定の保存 -----
    case 'save':
        $allowed_keys = [
            'threads_access_token',
            'threads_user_id',
            'threads_app_id',
            'threads_app_secret',
            'threads_token_expires_at',
            'gemini_api_key',
            'gemini_model',
            'license_key',
            'auto_post_enabled',
            'post_interval_variance',
            'ai_label_default',
            'top_post_threshold',
        ];

        $saved = 0;
        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                set_config($key, $_POST[$key]);
                $saved++;
            }
        }

        if ($saved > 0) {
            echo json_encode(['success' => true, 'message' => "{$saved}件の設定を保存しました"]);
        } else {
            echo json_encode(['success' => false, 'message' => '保存する設定がありませんでした']);
        }
        break;

    // ----- 設定の読み込み -----
    case 'get':
        $key = $_POST['key'] ?? '';
        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'キーを指定してください']);
            break;
        }
        $value = get_config($key);
        echo json_encode(['success' => true, 'key' => $key, 'value' => $value]);
        break;

    // ----- 全設定の読み込み -----
    case 'get_all':
        $stmt = $pdo->query("SELECT key, value FROM config WHERE key != 'admin_password'");
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // トークンなどの機密値はマスク
            if (in_array($row['key'], ['threads_access_token', 'gemini_api_key', 'threads_app_secret'])) {
                $row['value'] = !empty($row['value']) ? '***設定済み***' : '';
            }
            $configs[$row['key']] = $row['value'];
        }
        echo json_encode(['success' => true, 'configs' => $configs]);
        break;

    // ----- ライセンス認証チェック -----
    case 'verify_license':
        $license_key = get_config('license_key');
        if (empty($license_key)) {
            echo json_encode(['success' => true, 'valid' => false, 'message' => 'ライセンスキーが設定されていません']);
            break;
        }

        $detail = check_license_detail($license_key);
        $token_preview = defined('SECRET_TOKEN') ? substr(SECRET_TOKEN, 0, 5) . '***' : '未定義';
        echo json_encode([
            'success'   => true,
            'valid'     => $detail['valid'],
            'message'   => $detail['valid'] ? 'ライセンスは有効です' : $detail['error'],
            'http_code' => $detail['http_code'],
            'debug'     => $detail['valid'] ? null : [
                'url'           => defined('AUTH_SERVER_URL') ? AUTH_SERVER_URL : '未定義',
                'response'      => $detail['response'],
                'sent_token'    => $token_preview,
                'license_key'   => substr($license_key, 0, 4) . '***',
            ],
        ]);
        break;

    // ----- 短期トークンを長期トークンに交換 -----
    case 'exchange_token':
        $short_token = $_POST['short_token'] ?? '';
        if (empty($short_token)) {
            echo json_encode(['success' => false, 'message' => '短期トークンを入力してください']);
            break;
        }

        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        $result = $api->exchangeShortLivedToken($short_token);

        if ($result['success']) {
            // 交換に成功したら、DBに保存
            set_config('threads_access_token', $result['access_token']);
            set_config('threads_token_expires_at', $result['expires_at']);
            
            // ログ記録
            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('exchange_token', 'success', :msg)");
            $stmt->execute([':msg' => '短期から長期への交換に成功: ' . $result['expires_at']]);
        } else {
            // 失敗ログ
            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('exchange_token', 'failed', :msg)");
            $stmt->execute([':msg' => $result['message'] ?? '']);
        }

        echo json_encode($result);
        break;

    // ----- トークン手動更新 -----
    case 'refresh_token':
        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        $result = $api->refreshToken();

        if ($result['success']) {
            // 更新に成功したら、DBに保存
            set_config('threads_access_token', $result['access_token']);
            set_config('threads_token_expires_at', $result['expires_at']);

            // ログを記録
            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('manual_refresh', 'success', :msg)");
            $stmt->execute([':msg' => '長期トークンの更新に成功: ' . $result['expires_at']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('manual_refresh', 'failed', :msg)");
            $stmt->execute([':msg' => $result['message'] ?? '']);
        }

        echo json_encode($result);
        break;

    // ----- Threads プロフィール・インサイト(フォロワー)取得 -----
    case 'get_threads_profile':
        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        
        // 基本プロフィール
        $profile_result = $api->getUserProfile();
        
        if ($profile_result['success']) {
            // フォロワー数取得 (threads_insights エンドポイント)
            $access_token = get_config('threads_access_token');
            $user_id = get_config('threads_user_id');
            $follower_count = 0;

            if ($access_token && $user_id) {
                $insights_url = "https://graph.threads.net/v1.0/{$user_id}/threads_insights?metric=followers_count&access_token={$access_token}";
                $ch = curl_init($insights_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                curl_close($ch);
                $insight_data = json_decode($resp, true);
                if (isset($insight_data['data'][0]['total_value']['value'])) {
                    $follower_count = (int)$insight_data['data'][0]['total_value']['value'];
                    
                    // フォロワー数履歴にも保存（今日分）
                    $stmt = $pdo->prepare("INSERT INTO follower_history (follower_count) VALUES (:count)");
                    $stmt->execute([':count' => $follower_count]);
                }
            }
            
            $profile_result['profile']['follower_count'] = $follower_count;
            echo json_encode($profile_result);
        } else {
            echo json_encode($profile_result);
        }
        break;

    // ----- 過去の投稿データを同期 -----
    case 'sync_posts':
        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        
        $posts_result = $api->getUserPosts(50); // 直近50件を取得
        
        if ($posts_result['success'] && isset($posts_result['posts'])) {
            $count = 0;
            foreach ($posts_result['posts'] as $p) {
                // 既に存在するかチェック (threads_post_id がユニークであることを前提)
                $stmt = $pdo->prepare("SELECT id FROM posts WHERE threads_post_id = :post_id");
                $stmt->execute([':post_id' => $p['id']]);
                if ($stmt->fetch()) continue; // 存在すればスキップ

                // 新規保存
                $stmt = $pdo->prepare("
                    INSERT INTO posts (content, status, posted_at, threads_post_id, created_at, updated_at)
                    VALUES (:content, 'posted', :posted_at, :post_id, :posted_at, :posted_at)
                ");
                $stmt->execute([
                    ':content' => $p['text'] ?? '',
                    ':posted_at' => date('Y-m-d H:i:s', strtotime($p['timestamp'])),
                    ':post_id' => $p['id']
                ]);
                $count++;
            }
            echo json_encode(['success' => true, 'message' => "新たに {$count} 件の投稿を同期しました。"]);
        } else {
            echo json_encode(['success' => false, 'message' => $posts_result['message'] ?? '投稿取得に失敗しました']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
