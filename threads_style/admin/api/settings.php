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
            if (in_array($row['key'], ['threads_access_token', 'gemini_api_key'])) {
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

    // ----- トークン手動更新 -----
    case 'refresh_token':
        require_once __DIR__ . '/threads_api.php';
        $result = threads_refresh_token();

        // ログを記録
        $stmt = $pdo->prepare("INSERT INTO token_logs (action, status, message) VALUES ('manual_refresh', :status, :msg)");
        $stmt->execute([
            ':status' => $result['success'] ? 'success' : 'failed',
            ':msg'    => $result['message'] ?? '',
        ]);

        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
