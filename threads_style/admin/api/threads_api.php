<?php
/**
 * ============================================================
 * Threads API 連携ライブラリ - ThreadsStyle
 * ============================================================
 * Threads API との通信を管理する関数群。
 * 投稿の作成、メディアコンテナの取得、インサイト取得、トークン更新。
 * ============================================================
 */

// このファイルは他のファイルから require される関数ライブラリ
// 直接アクセスしない（init.phpは呼び出し元で既にrequireされている前提）

/**
 * Threads APIに投稿を公開する
 * @param string $content 投稿内容
 * @param bool $ai_label AI生成ラベルを付与するか
 * @return array ['success' => bool, 'post_id' => string, 'media_id' => string, 'message' => string]
 */
function threads_publish_post($content, $ai_label = false) {
    $access_token = get_config('threads_access_token');
    $user_id = get_config('threads_user_id');

    if (empty($access_token) || empty($user_id)) {
        return ['success' => false, 'message' => 'Threads API のアクセストークンまたはユーザーIDが設定されていません。設定画面から登録してください。'];
    }

    // Step 1: メディアコンテナ作成
    $container_url = "https://graph.threads.net/v1.0/{$user_id}/threads";
    $params = [
        'media_type' => 'TEXT',
        'text' => $content,
        'access_token' => $access_token,
    ];

    // AI生成ラベル
    if ($ai_label) {
        $params['is_made_with_ai'] = 'true';
    }

    $response = threads_api_request($container_url, $params, 'POST');
    if (!$response['success'] || empty($response['data']['id'])) {
        return ['success' => false, 'message' => 'メディアコンテナの作成に失敗しました: ' . ($response['error'] ?? '不明なエラー')];
    }

    $media_id = $response['data']['id'];

    // Step 2: 投稿を公開
    $publish_url = "https://graph.threads.net/v1.0/{$user_id}/threads_publish";
    $publish_params = [
        'creation_id' => $media_id,
        'access_token' => $access_token,
    ];

    $pub_response = threads_api_request($publish_url, $publish_params, 'POST');
    if (!$pub_response['success'] || empty($pub_response['data']['id'])) {
        return ['success' => false, 'message' => '投稿の公開に失敗しました: ' . ($pub_response['error'] ?? '不明なエラー')];
    }

    return [
        'success' => true,
        'post_id' => $pub_response['data']['id'],
        'media_id' => $media_id,
        'message' => '投稿が公開されました'
    ];
}

/**
 * スレッド（連投）を公開する
 * @param array $contents 投稿内容の配列（順序通り）
 * @param string $reply_to_id 最初の投稿のID（NULLなら新規スレッド）
 * @return array
 */
function threads_publish_thread($contents, $reply_to_id = null) {
    $results = [];
    $parent_id = $reply_to_id;

    foreach ($contents as $i => $content) {
        if ($i === 0 && $parent_id === null) {
            // 最初の投稿
            $result = threads_publish_post($content);
        } else {
            // リプライとして投稿
            $result = threads_publish_reply($content, $parent_id);
        }

        if (!$result['success']) {
            return ['success' => false, 'message' => "スレッドの{$i}番目の投稿に失敗しました: " . $result['message'], 'results' => $results];
        }

        $parent_id = $result['post_id'];
        $results[] = $result;
    }

    return ['success' => true, 'results' => $results, 'message' => count($contents) . '件のスレッドを投稿しました'];
}

/**
 * リプライとして投稿する
 */
function threads_publish_reply($content, $reply_to_id) {
    $access_token = get_config('threads_access_token');
    $user_id = get_config('threads_user_id');

    $container_url = "https://graph.threads.net/v1.0/{$user_id}/threads";
    $params = [
        'media_type' => 'TEXT',
        'text' => $content,
        'reply_to_id' => $reply_to_id,
        'access_token' => $access_token,
    ];

    $response = threads_api_request($container_url, $params, 'POST');
    if (!$response['success'] || empty($response['data']['id'])) {
        return ['success' => false, 'message' => $response['error'] ?? '不明なエラー'];
    }

    $media_id = $response['data']['id'];

    $publish_url = "https://graph.threads.net/v1.0/{$user_id}/threads_publish";
    $pub_response = threads_api_request($publish_url, [
        'creation_id' => $media_id,
        'access_token' => $access_token,
    ], 'POST');

    if (!$pub_response['success'] || empty($pub_response['data']['id'])) {
        return ['success' => false, 'message' => $pub_response['error'] ?? '不明なエラー'];
    }

    return ['success' => true, 'post_id' => $pub_response['data']['id'], 'media_id' => $media_id];
}

/**
 * ユーザーの投稿一覧を取得する（インサイト用）
 * @param int $limit 取得件数
 * @return array
 */
function threads_get_user_posts($limit = 30) {
    $access_token = get_config('threads_access_token');
    $user_id = get_config('threads_user_id');

    if (empty($access_token) || empty($user_id)) {
        return ['success' => false, 'message' => 'API設定が未完了です'];
    }

    $url = "https://graph.threads.net/v1.0/{$user_id}/threads";
    $params = [
        'fields' => 'id,text,timestamp,media_type,is_quote_post',
        'limit' => $limit,
        'access_token' => $access_token,
    ];

    $response = threads_api_request($url . '?' . http_build_query($params), [], 'GET');
    if ($response['success'] && isset($response['data']['data'])) {
        return ['success' => true, 'posts' => $response['data']['data']];
    }

    return ['success' => false, 'message' => $response['error'] ?? '投稿の取得に失敗しました'];
}

/**
 * 投稿のインサイトを取得する
 * @param string $threads_post_id Threads投稿ID
 * @return array
 */
function threads_get_post_insights($threads_post_id) {
    $access_token = get_config('threads_access_token');

    $url = "https://graph.threads.net/v1.0/{$threads_post_id}/insights";
    $params = [
        'metric' => 'views,likes,replies,reposts,quotes',
        'access_token' => $access_token,
    ];

    $response = threads_api_request($url . '?' . http_build_query($params), [], 'GET');
    if ($response['success'] && isset($response['data']['data'])) {
        $metrics = [];
        foreach ($response['data']['data'] as $m) {
            $metrics[$m['name']] = $m['values'][0]['value'] ?? 0;
        }
        return ['success' => true, 'metrics' => $metrics];
    }

    return ['success' => false, 'message' => $response['error'] ?? 'インサイト取得に失敗しました'];
}

/**
 * ユーザープロフィール（フォロワー数）を取得する
 * @return array
 */
function threads_get_user_profile() {
    $access_token = get_config('threads_access_token');
    $user_id = get_config('threads_user_id');

    if (empty($access_token) || empty($user_id)) {
        return ['success' => false, 'message' => 'API設定が未完了です'];
    }

    $url = "https://graph.threads.net/v1.0/{$user_id}";
    $params = [
        'fields' => 'id,username,threads_profile_picture_url,threads_biography',
        'access_token' => $access_token,
    ];

    $response = threads_api_request($url . '?' . http_build_query($params), [], 'GET');
    if ($response['success'] && isset($response['data'])) {
        return ['success' => true, 'profile' => $response['data']];
    }

    return ['success' => false, 'message' => $response['error'] ?? 'プロフィール取得に失敗しました'];
}

/**
 * 長期トークンをリフレッシュする
 * @return array
 */
function threads_refresh_token() {
    $access_token = get_config('threads_access_token');

    if (empty($access_token)) {
        return ['success' => false, 'message' => 'アクセストークンが設定されていません'];
    }

    $url = "https://graph.threads.net/refresh_access_token";
    $params = [
        'grant_type' => 'th_refresh_token',
        'access_token' => $access_token,
    ];

    $response = threads_api_request($url . '?' . http_build_query($params), [], 'GET');

    if ($response['success'] && isset($response['data']['access_token'])) {
        $new_token = $response['data']['access_token'];
        $expires_in = $response['data']['expires_in'] ?? 5184000; // 60 days default
        $expires_at = date('Y-m-d', time() + $expires_in);

        set_config('threads_access_token', $new_token);
        set_config('threads_token_expires_at', $expires_at);

        return ['success' => true, 'message' => 'トークンを更新しました。有効期限: ' . $expires_at, 'expires_at' => $expires_at];
    }

    return ['success' => false, 'message' => 'トークンの更新に失敗しました: ' . ($response['error'] ?? '不明なエラー')];
}

/**
 * 汎用 Threads API リクエスト送信関数
 */
function threads_api_request($url, $params = [], $method = 'GET') {
    $ch = curl_init();

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } else {
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ThreadsStyle/' . (defined('TOOL_VERSION') ? TOOL_VERSION : '1.0.0'));

    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'error' => 'cURL Error: ' . $curl_error];
    }

    $data = json_decode($response_body, true);

    if ($http_code >= 200 && $http_code < 300 && $data) {
        if (isset($data['error'])) {
            return ['success' => false, 'error' => $data['error']['message'] ?? 'API Error', 'data' => $data];
        }
        return ['success' => true, 'data' => $data];
    }

    $error_msg = 'HTTP ' . $http_code;
    if ($data && isset($data['error']['message'])) {
        $error_msg = $data['error']['message'];
    }

    return ['success' => false, 'error' => $error_msg, 'data' => $data];
}
