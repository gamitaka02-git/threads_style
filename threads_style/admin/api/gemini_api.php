<?php
/**
 * ============================================================
 * Gemini API 連携 - ThreadsStyle
 * ============================================================
 * Gemini APIを使用した自己解析、スタイルガイド生成、投稿生成、リパーパス
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

    // ----- 自己解析（初期分析） -----
    case 'analyze':
        $gemini_key = get_config('gemini_api_key');
        if (empty($gemini_key)) {
            echo json_encode(['success' => false, 'message' => 'Gemini API キーが設定されていません。設定画面から登録してください。']);
            break;
        }

        // 手動投稿のみを取得（純度保持ロジック: AI生成投稿を除外）
        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        $posts_result = $api->getUserPosts(30);

        $sample_posts = [];
        if ($posts_result['success'] && !empty($posts_result['posts'])) {
            foreach ($posts_result['posts'] as $p) {
                if (!empty($p['text'])) {
                    $sample_posts[] = $p['text'];
                }
            }
        }

        // APIから取得できない場合は、DBの手動投稿を使用
        if (empty($sample_posts)) {
            $stmt = $pdo->query("SELECT content FROM posts WHERE is_ai_generated = 0 AND ai_label = 0 ORDER BY created_at DESC LIMIT 30");
            $db_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($db_posts as $p) {
                $sample_posts[] = $p['content'];
            }
        }

        if (empty($sample_posts)) {
            echo json_encode(['success' => false, 'message' => '分析に使用する投稿データがありません。Threads APIの設定を確認するか、手動で投稿を作成してください。']);
            break;
        }

        // Gemini に解析を依頼
        $posts_text = implode("\n---\n", array_slice($sample_posts, 0, 20));
        $prompt = <<<PROMPT
以下はあるユーザーのSNS投稿（Threads）の一覧です。この投稿群を分析し、以下の項目について詳細なスタイルガイドをMarkdown形式で作成してください。

# 分析対象投稿
{$posts_text}

# 出力形式（Markdown）
以下のセクションに沿って出力してください：

## 文体の特徴
- 語尾の傾向（敬体/常体、特徴的な語尾）
- 文の長さの傾向
- 句読点の使い方

## 性格・パーソナリティ
- 全体的な印象
- コミュニケーションスタイル（教育的、親しみやすい、知的 等）

## トーン・雰囲気
- メインのトーン
- 感情表現の傾向

## 頻出トピック・関心領域
- よく言及されるテーマ

## 絵文字・記号の使い方
- 絵文字の使用頻度・種類
- ハッシュタグの傾向

## 投稿の構造パターン
- 冒頭の書き出しパターン
- 本文の展開方法
- 締めくくりの傾向

## AI投稿生成時の注意点
- このユーザーらしさを再現するためのポイント
- やってはいけないこと
PROMPT;

        $gemini_result = call_gemini_api($prompt);
        if ($gemini_result['success']) {
            $style_guide = $gemini_result['text'];

            // スタイルガイドをDBに保存
            $stmt = $pdo->prepare("INSERT INTO style_guides (content_markdown, analysis_data, is_active) VALUES (:content, :data, 1)");
            $stmt->execute([
                ':content' => $style_guide,
                ':data' => json_encode(['analyzed_posts_count' => count($sample_posts), 'analyzed_at' => date('Y-m-d H:i:s')])
            ]);

            // 古いスタイルガイドを無効化（最新のみ有効）
            $new_id = $pdo->lastInsertId();
            $pdo->prepare("UPDATE style_guides SET is_active = 0 WHERE id != :id")->execute([':id' => $new_id]);

            echo json_encode(['success' => true, 'style_guide' => $style_guide]);
        } else {
            echo json_encode(['success' => false, 'message' => $gemini_result['message'] ?? 'Gemini APIとの通信に失敗しました']);
        }
        break;

    // ----- トーン学習型投稿生成（3案） -----
    case 'generate':
        $gemini_key = get_config('gemini_api_key');
        if (empty($gemini_key)) {
            echo json_encode(['success' => false, 'message' => 'Gemini API キーが設定されていません。']);
            break;
        }

        $topic = trim($_POST['topic'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');

        if (empty($topic)) {
            echo json_encode(['success' => false, 'message' => 'トピックを入力してください']);
            break;
        }

        // 有効なスタイルガイドを取得
        $stmt = $pdo->query("SELECT content_markdown FROM style_guides WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $guide = $stmt->fetchColumn();
        $guide_section = $guide ? "\n# 参考スタイルガイド\n{$guide}\n" : "\n（スタイルガイド未作成：一般的なカジュアルなSNSトーンを使用）\n";

        $extra = $instructions ? "\n# 追加指示\n{$instructions}\n" : '';

        $prompt = <<<PROMPT
あなたはSNS（Threads）の投稿作成アシスタントです。
以下のスタイルガイドを参考に、指定されたトピックについて3種類の投稿案を生成してください。

各投稿は500文字以内で、以下の3つのタイプで作成してください：
1. **教育**: 知識や学びを共有する投稿
2. **独り言**: 個人的な考えや気づきを呟く投稿
3. **交流**: フォロワーとの対話を促す投稿

{$guide_section}
{$extra}
# トピック
{$topic}

# 出力形式
各投稿案を以下の区切りで出力してください（区切り文字のみの行で分割）：
---SPLIT---
投稿案1（教育）のテキストのみ
---SPLIT---
投稿案2（独り言）のテキストのみ
---SPLIT---
投稿案3（交流）のテキストのみ
PROMPT;

        $gemini_result = call_gemini_api($prompt);
        if ($gemini_result['success']) {
            $parts = preg_split('/---SPLIT---/', $gemini_result['text']);
            $suggestions = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $suggestions[] = $trimmed;
                }
            }
            // 必ず3つ返す
            while (count($suggestions) < 3) {
                $suggestions[] = '（生成に失敗しました。再度お試しください）';
            }
            echo json_encode(['success' => true, 'suggestions' => array_slice($suggestions, 0, 3)]);
        } else {
            echo json_encode(['success' => false, 'message' => $gemini_result['message'] ?? '生成に失敗しました']);
        }
        break;

    // ----- リパーパス -----
    case 'repurpose':
        $gemini_key = get_config('gemini_api_key');
        if (empty($gemini_key)) {
            echo json_encode(['success' => false, 'message' => 'Gemini API キーが設定されていません。']);
            break;
        }

        $url = trim($_POST['url'] ?? '');
        if (empty($url)) {
            echo json_encode(['success' => false, 'message' => 'URLを入力してください']);
            break;
        }

        // URLからコンテンツを取得
        $page_content = fetch_url_content($url);
        if (empty($page_content)) {
            echo json_encode(['success' => false, 'message' => 'URLからコンテンツを取得できませんでした']);
            break;
        }

        // スタイルガイド取得
        $stmt = $pdo->query("SELECT content_markdown FROM style_guides WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $guide = $stmt->fetchColumn();
        $guide_section = $guide ? "\n# 参考スタイルガイド\n{$guide}\n" : '';

        $prompt = <<<PROMPT
以下のWebページのコンテンツを、Threads（SNS）に適した形式に変換してください。
500文字以内の投稿を3パターン作成してください。

{$guide_section}

# 元のコンテンツ
{$page_content}

# 出力形式
各投稿案を以下の区切りで出力してください：
---SPLIT---
投稿案1
---SPLIT---
投稿案2
---SPLIT---
投稿案3
PROMPT;

        $gemini_result = call_gemini_api($prompt);
        if ($gemini_result['success']) {
            $parts = preg_split('/---SPLIT---/', $gemini_result['text']);
            $suggestions = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (!empty($trimmed)) {
                    $suggestions[] = $trimmed;
                }
            }
            echo json_encode(['success' => true, 'suggestions' => array_slice($suggestions, 0, 3)]);
        } else {
            echo json_encode(['success' => false, 'message' => $gemini_result['message'] ?? '変換に失敗しました']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}

// ===== Gemini API 呼び出し関数 =====
function call_gemini_api($prompt) {
    $api_key = get_config('gemini_api_key');
    $model = get_config('gemini_model', 'gemini-3.1-flash-lite-preview');

    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Gemini API キーが設定されていません'];
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

    $payload = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.8,
            'maxOutputTokens' => 4096,
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $curl_error];
    }

    $data = json_decode($response, true);

    if ($http_code === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['success' => true, 'text' => $data['candidates'][0]['content']['parts'][0]['text']];
    }

    $error_msg = 'Gemini API Error (HTTP ' . $http_code . ')';
    if (isset($data['error']['message'])) {
        $error_msg = $data['error']['message'];
    }

    return ['success' => false, 'message' => $error_msg];
}

// ===== URL コンテンツ取得関数 =====
function fetch_url_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; ThreadsStyle/1.0)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $html = curl_exec($ch);
    curl_close($ch);

    if (empty($html)) return '';

    // HTMLからテキストを抽出（簡易的に）
    $text = strip_tags(
        preg_replace('/<(script|style|noscript)[^>]*>.*?<\/\1>/si', '', $html)
    );

    // 余分な空白を削除
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    // 長すぎる場合は切り詰め
    if (mb_strlen($text, 'UTF-8') > 5000) {
        $text = mb_substr($text, 0, 5000, 'UTF-8') . '...';
    }

    return $text;
}
