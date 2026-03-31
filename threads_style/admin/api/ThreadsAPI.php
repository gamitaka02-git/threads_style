<?php
/**
 * ============================================================
 * Threads API 連携クラス - ThreadsStyle
 * ============================================================
 * Threads API との通信を一元管理する。
 * 投稿作成、スレッド管理、インサイト取得、トークン管理。
 * ============================================================
 */

class ThreadsAPI {
    private $accessToken;
    private $userId;
    private $appId;
    private $appSecret;

    /**
     * コンストラクタ
     * 引数が空の場合は get_config() から取得する
     */
    public function __construct($accessToken = null, $userId = null) {
        $this->accessToken = $accessToken ?? get_config('threads_access_token');
        $this->userId = $userId ?? get_config('threads_user_id');
        
        // アプリIDとシークレットはDBから取得（定数はフォールバック）
        $this->appId = get_config('threads_app_id', defined('THREADS_APP_ID') ? THREADS_APP_ID : '');
        $this->appSecret = get_config('threads_app_secret', defined('THREADS_APP_SECRET') ? THREADS_APP_SECRET : '');
    }

    /**
     * 短期トークンを長期トークンに交換する
     * @param string $shortLivedToken
     * @return array
     */
    public function exchangeShortLivedToken($shortLivedToken) {
        if (empty($this->appId) || empty($this->appSecret)) {
            return [
                'success' => false, 
                'message' => 'THREADS_APP_ID または THREADS_APP_SECRET が設定されていません。Meta Developer Portal で取得し、config.php に設定してください。'
            ];
        }

        $url = "https://graph.threads.net/access_token";
        $params = [
            'grant_type' => 'th_exchange_token',
            'client_secret' => $this->appSecret,
            'access_token' => $shortLivedToken
        ];

        $response = $this->apiRequest($url . '?' . http_build_query($params), [], 'GET');

        if ($response['success'] && isset($response['data']['access_token'])) {
            $longLivedToken = $response['data']['access_token'];
            $expiresIn = $response['data']['expires_in'] ?? 5184000; // デフォルト60日
            $expiresAt = date('Y-m-d', time() + $expiresIn);

            // 内部プロパティを更新
            $this->accessToken = $longLivedToken;

            return [
                'success' => true,
                'access_token' => $longLivedToken,
                'expires_at' => $expiresAt,
                'message' => '短期トークンを長期トークンに交換しました。'
            ];
        }

        return ['success' => false, 'message' => 'トークン交換に失敗しました: ' . ($response['error'] ?? '不明なエラー')];
    }

    /**
     * 長期トークンをリフレッシュする
     * @return array
     */
    public function refreshToken() {
        if (empty($this->accessToken)) {
            return ['success' => false, 'message' => 'アクセストークンが設定されていません。'];
        }

        $url = "https://graph.threads.net/refresh_access_token";
        $params = [
            'grant_type' => 'th_refresh_token',
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiRequest($url . '?' . http_build_query($params), [], 'GET');

        if ($response['success'] && isset($response['data']['access_token'])) {
            $newToken = $response['data']['access_token'];
            $expiresIn = $response['data']['expires_in'] ?? 5184000;
            $expiresAt = date('Y-m-d', time() + $expiresIn);

            $this->accessToken = $newToken;

            return [
                'success' => true,
                'access_token' => $newToken,
                'expires_at' => $expiresAt,
                'message' => 'トークンを更新しました。有効期限: ' . $expiresAt
            ];
        }

        return ['success' => false, 'message' => 'トークンの更新に失敗しました: ' . ($response['error'] ?? '不明なエラー')];
    }

    /**
     * Threads APIに投稿を公開する
     * @param string $content
     * @param bool $aiLabel
     * @param string|array $mediaUrl 画像URL（単一文字列 or 複数URLの配列）
     * @param string $topicTag トピックタグ（1-50文字・「.」「&」不可）
     * @return array
     */
    public function publishPost($content, $aiLabel = false, $mediaUrl = '', $topicTag = '') {
        if (empty($this->accessToken) || empty($this->userId)) {
            return ['success' => false, 'message' => 'API設定が不足しています（トークンまたはユーザーIDが無効）。'];
        }

        // 複数画像URLの場合はカルーセル投稿へ委譲
        $mediaUrls = $this->parseMediaUrls($mediaUrl);
        if (count($mediaUrls) >= 2) {
            return $this->publishCarouselPost($content, $mediaUrls, $aiLabel, $topicTag);
        }

        // Step 1: メディアコンテナ作成
        $containerUrl = "https://graph.threads.net/v1.0/{$this->userId}/threads";
        
        $params = [
            'access_token' => $this->accessToken,
        ];

        $singleUrl = count($mediaUrls) === 1 ? $mediaUrls[0] : '';

        if (!empty($singleUrl)) {
            $params['media_type'] = 'IMAGE';
            $params['image_url'] = $singleUrl;
            $params['text'] = $content;
        } else {
            $params['media_type'] = 'TEXT';
            $params['text'] = $content;
        }

        if ($aiLabel) {
            $params['is_made_with_ai'] = 'true';
        }

        // topic_tag を適用（API制約: 1-50文字、「.」「&」不可）
        $topicTag = $this->sanitizeTopicTag($topicTag);
        if (!empty($topicTag)) {
            $params['topic_tag'] = $topicTag;
        }

        $response = $this->apiRequest($containerUrl, $params, 'POST');
        if (!$response['success'] || empty($response['data']['id'])) {
            return ['success' => false, 'message' => 'メディアコンテナ作成失敗: ' . ($response['error'] ?? '不明なエラー')];
        }

        $mediaId = $response['data']['id'];
        
        // Step 2: 公開
        return $this->publishContainer($mediaId);
    }

    /**
     * カルーセル投稿（複数画像）を公開する
     * Threads API のカルーセル投稿は3ステップ:
     *   1. 各画像のメディアコンテナ作成 (is_carousel_item=true)
     *   2. カルーセルコンテナ作成 (media_type=CAROUSEL, children=id1,id2,...)
     *   3. 公開
     * @param string $content 投稿テキスト
     * @param array $imageUrls 画像URLの配列（2〜20枚）
     * @param bool $aiLabel
     * @param string $topicTag
     * @return array
     */
    public function publishCarouselPost($content, array $imageUrls, $aiLabel = false, $topicTag = '') {
        if (empty($this->accessToken) || empty($this->userId)) {
            return ['success' => false, 'message' => 'API設定が不足しています。'];
        }

        if (count($imageUrls) < 2) {
            return ['success' => false, 'message' => 'カルーセル投稿には2枚以上の画像が必要です。'];
        }
        if (count($imageUrls) > 20) {
            $imageUrls = array_slice($imageUrls, 0, 20); // 最大20枚
        }

        $containerUrl = "https://graph.threads.net/v1.0/{$this->userId}/threads";
        $childIds = [];

        // Step 1: 各画像のメディアコンテナを作成
        foreach ($imageUrls as $idx => $imageUrl) {
            $params = [
                'media_type' => 'IMAGE',
                'image_url' => $imageUrl,
                'is_carousel_item' => 'true',
                'access_token' => $this->accessToken,
            ];
            if ($aiLabel) {
                $params['is_made_with_ai'] = 'true';
            }

            $response = $this->apiRequest($containerUrl, $params, 'POST');
            if (!$response['success'] || empty($response['data']['id'])) {
                return ['success' => false, 'message' => "画像{$idx}のコンテナ作成失敗: " . ($response['error'] ?? '不明なエラー')];
            }
            $childIds[] = $response['data']['id'];
        }

        // Threads API の推奨: コンテナ作成後 30 秒程度待機
        sleep(2); // サーバー負荷を考慮して短めに設定

        // Step 2: カルーセルコンテナを作成
        $carouselParams = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childIds),
            'text' => $content,
            'access_token' => $this->accessToken,
        ];
        if ($aiLabel) {
            $carouselParams['is_made_with_ai'] = 'true';
        }
        $topicTag = $this->sanitizeTopicTag($topicTag);
        if (!empty($topicTag)) {
            $carouselParams['topic_tag'] = $topicTag;
        }

        $carouselResponse = $this->apiRequest($containerUrl, $carouselParams, 'POST');
        if (!$carouselResponse['success'] || empty($carouselResponse['data']['id'])) {
            return ['success' => false, 'message' => 'カルーセルコンテナ作成失敗: ' . ($carouselResponse['error'] ?? '不明なエラー')];
        }

        $carouselId = $carouselResponse['data']['id'];

        // Step 3: 公開
        $result = $this->publishContainer($carouselId);
        if ($result['success']) {
            $result['carousel'] = true;
            $result['image_count'] = count($imageUrls);
        }
        return $result;
    }

    /**
     * topic_tag をサニタイズする（API制約を適用）
     * - 先頭の # を除去
     * - . と & を除去
     * - 50文字までに指定
     */
    private function sanitizeTopicTag($tag) {
        if (empty($tag)) return '';
        $tag = trim($tag);
        $tag = ltrim($tag, '#');          // 先頭 # を除去
        $tag = str_replace(['.', '&'], '', $tag); // 禁止文字を除去
        $tag = mb_strimwidth($tag, 0, 50, '', 'UTF-8'); // 50文字まで
        return $tag;
    }

    /**
     * media_url フィールドを画像URLの配列に変換する（後方互換性あり）
     * @param string|array $mediaUrl
     * @return array
     */
    private function parseMediaUrls($mediaUrl) {
        if (empty($mediaUrl)) return [];
        if (is_array($mediaUrl)) {
            return array_values(array_filter($mediaUrl));
        }
        // JSON配列として試みる
        $decoded = json_decode($mediaUrl, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded));
        }
        // 単一URL文字列
        return [$mediaUrl];
    }

    /**
     * コンテナを公開する（内部用）
     */
    private function publishContainer($mediaId) {
        $publishUrl = "https://graph.threads.net/v1.0/{$this->userId}/threads_publish";
        $publishParams = [
            'creation_id' => $mediaId,
            'access_token' => $this->accessToken,
        ];

        $pubResponse = $this->apiRequest($publishUrl, $publishParams, 'POST');
        if (!$pubResponse['success'] || empty($pubResponse['data']['id'])) {
            return ['success' => false, 'message' => '投稿公開失敗: ' . ($pubResponse['error'] ?? '不明なエラー')];
        }

        return [
            'success' => true,
            'post_id' => $pubResponse['data']['id'],
            'media_id' => $mediaId,
            'message' => '投稿が公開されました'
        ];
    }

    /**
     * スレッド（連投）を公開する
     */
    public function publishThread($contents) {
        $results = [];
        $parentId = null;

        foreach ($contents as $i => $content) {
            if ($i === 0) {
                $result = $this->publishPost($content);
            } else {
                $result = $this->publishReply($content, $parentId);
            }

            if (!$result['success']) {
                return ['success' => false, 'message' => "スレッド投稿エラー({$i}): " . $result['message'], 'results' => $results];
            }

            $parentId = $result['post_id'];
            $results[] = $result;
        }

        return ['success' => true, 'results' => $results, 'message' => count($contents) . '件のスレッドを投稿しました'];
    }

    /**
     * リプライとして投稿する
     */
    public function publishReply($content, $replyToId) {
        if (empty($this->accessToken) || empty($this->userId)) {
            return ['success' => false, 'message' => 'API設定が不足しています。'];
        }

        $containerUrl = "https://graph.threads.net/v1.0/{$this->userId}/threads";
        $params = [
            'media_type' => 'TEXT',
            'text' => $content,
            'reply_to_id' => $replyToId,
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiRequest($containerUrl, $params, 'POST');
        if (!$response['success'] || empty($response['data']['id'])) {
            return ['success' => false, 'message' => $response['error'] ?? '不明なエラー'];
        }

        $mediaId = $response['data']['id'];
        return $this->publishContainer($mediaId);
    }

    /**
     * ユーザーの投稿一覧を取得する
     */
    public function getUserPosts($limit = 30) {
        if (empty($this->accessToken) || empty($this->userId)) {
            return ['success' => false, 'message' => 'API設定が未完了です'];
        }

        $url = "https://graph.threads.net/v1.0/{$this->userId}/threads";
        $params = [
            'fields' => 'id,text,timestamp,media_type,is_quote_post',
            'limit' => $limit,
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiRequest($url . '?' . http_build_query($params), [], 'GET');
        if ($response['success'] && isset($response['data']['data'])) {
            return ['success' => true, 'posts' => $response['data']['data']];
        }

        return ['success' => false, 'message' => $response['error'] ?? '投稿取得失敗'];
    }

    /**
     * インサイトを取得する
     */
    public function getPostInsights($threadsPostId) {
        $url = "https://graph.threads.net/v1.0/{$threadsPostId}/insights";
        $params = [
            'metric' => 'views,likes,replies,reposts,quotes',
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiRequest($url . '?' . http_build_query($params), [], 'GET');
        if ($response['success'] && isset($response['data']['data'])) {
            $metrics = [];
            foreach ($response['data']['data'] as $m) {
                $metrics[$m['name']] = $m['values'][0]['value'] ?? 0;
            }
            return ['success' => true, 'metrics' => $metrics];
        }

        return ['success' => false, 'message' => $response['error'] ?? 'インサイト取得失敗'];
    }

    /**
     * プロフィールを取得する
     */
    public function getUserProfile() {
        if (empty($this->accessToken) || empty($this->userId)) {
            return ['success' => false, 'message' => 'API設定が未完了です'];
        }

        $url = "https://graph.threads.net/v1.0/{$this->userId}";
        $params = [
            'fields' => 'id,username,threads_profile_picture_url,threads_biography',
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiRequest($url . '?' . http_build_query($params), [], 'GET');
        if ($response['success'] && isset($response['data'])) {
            return ['success' => true, 'profile' => $response['data']];
        }

        return ['success' => false, 'message' => $response['error'] ?? 'プロフィール取得失敗'];
    }

    /**
     * APIリクエスト送信
     */
    private function apiRequest($url, $params = [], $method = 'GET') {
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

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
        }

        $data = json_decode($responseBody, true);

        if ($httpCode >= 200 && $httpCode < 300 && $data) {
            if (isset($data['error'])) {
                return ['success' => false, 'error' => $data['error']['message'] ?? 'API Error', 'data' => $data];
            }
            return ['success' => true, 'data' => $data];
        }

        $error_msg = 'HTTP ' . $httpCode;
        if ($data && isset($data['error']['message'])) {
            $error_msg = $data['error']['message'];
        }

        return ['success' => false, 'error' => $error_msg, 'data' => $data];
    }
}
