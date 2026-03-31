<?php
/**
 * ============================================================
 * Posts API エンドポイント - ThreadsStyle
 * ============================================================
 * 投稿のCRUD、スレッド作成、即時投稿、リサイクル
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ----- 投稿一覧 -----
    case 'list':
        $filter = $_POST['filter'] ?? 'all';
        $where = '';
        if ($filter === 'draft')     $where = "WHERE status = 'draft'";
        if ($filter === 'scheduled') $where = "WHERE status = 'scheduled'";
        if ($filter === 'posted')    $where = "WHERE status = 'posted'";
        if ($filter === 'failed')    $where = "WHERE status = 'failed'";

        $stmt = $pdo->query("SELECT * FROM posts {$where} ORDER BY created_at DESC LIMIT 100");
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'posts' => $posts]);
        break;

    // ----- 投稿の取得 -----
    case 'get':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            echo json_encode(['success' => false, 'message' => '投稿が見つかりません']);
        }
        break;

    // ----- 投稿の作成 -----
    case 'create':
        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '投稿内容を入力してください']);
            break;
        }

        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        // datetime-local の T区切りを スペース区切りに正規化（Cron比較用）
        if ($scheduled_at) {
            $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
        }
        $ai_label = (int)($_POST['ai_label'] ?? 0);
        $is_ai_generated = (int)($_POST['is_ai_generated'] ?? 0);
        $source_type = $_POST['source_type'] ?? 'manual';
        $source_url = $_POST['source_url'] ?? '';
        $topic_tag = trim($_POST['topic_tag'] ?? '');
        // 複数画像対応: media_urls (JSON配列) が優先、なければ media_url
        $media_url = '';
        if (!empty($_POST['media_urls'])) {
            $urls = json_decode($_POST['media_urls'], true);
            if (is_array($urls) && count($urls) > 1) {
                $media_url = json_encode(array_values(array_filter($urls)));
            } elseif (is_array($urls) && count($urls) === 1) {
                $media_url = $urls[0];
            }
        } elseif (!empty($_POST['media_url'])) {
            $media_url = $_POST['media_url'];
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            INSERT INTO posts (content, media_url, topic_tag, status, scheduled_at, ai_label, is_ai_generated, source_type, source_url, created_at, updated_at)
            VALUES (:content, :media_url, :topic_tag, :status, :scheduled_at, :ai_label, :is_ai_generated, :source_type, :source_url, :now, :now)
        ");
        $stmt->execute([
            ':content' => $content,
            ':media_url' => $media_url,
            ':topic_tag' => $topic_tag,
            ':status' => $status,
            ':scheduled_at' => $scheduled_at,
            ':ai_label' => $ai_label,
            ':is_ai_generated' => $is_ai_generated,
            ':source_type' => $source_type,
            ':source_url' => $source_url,
            ':now' => $now,
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => '投稿を作成しました']);
        break;

    // ----- 投稿の更新 -----
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        // datetime-local の T区切りを スペース区切りに正規化
        if ($scheduled_at) {
            $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
        }
        $ai_label = (int)($_POST['ai_label'] ?? 0);
        $topic_tag = trim($_POST['topic_tag'] ?? '');
        // 複数画像対応: media_urls (JSON配列) が優先、なければ media_url
        $media_url = '';
        if (!empty($_POST['media_urls'])) {
            $urls = json_decode($_POST['media_urls'], true);
            if (is_array($urls) && count($urls) > 1) {
                $media_url = json_encode(array_values(array_filter($urls)));
            } elseif (is_array($urls) && count($urls) === 1) {
                $media_url = $urls[0];
            }
        } elseif (isset($_POST['media_url'])) {
            $media_url = $_POST['media_url'];
        }

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '投稿内容を入力してください']);
            break;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            UPDATE posts SET content = :content, media_url = :media_url, topic_tag = :topic_tag,
            status = :status, scheduled_at = :scheduled_at,
            ai_label = :ai_label, updated_at = :now
            WHERE id = :id
        ");
        $stmt->execute([
            ':content' => $content,
            ':media_url' => $media_url,
            ':topic_tag' => $topic_tag,
            ':status' => $status,
            ':scheduled_at' => $scheduled_at ?: null,
            ':ai_label' => $ai_label,
            ':id' => $id,
            ':now' => $now,
        ]);

        echo json_encode(['success' => true, 'message' => '投稿を更新しました']);
        break;

    // ----- 投稿の削除 -----
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => '投稿を削除しました']);
        break;

    // ----- 即時投稿 -----
    case 'publish_now':
        $content = trim($_POST['content'] ?? '');
        $ai_label = (int)($_POST['ai_label'] ?? 0);
        $topic_tag = trim($_POST['topic_tag'] ?? '');

        // 複数画像対応: media_urls (JSON配列) が優先、なければ media_url
        $media_url_raw = '';
        $media_for_api = ''; // APIに渡す値（単一URL or 配列）
        if (!empty($_POST['media_urls'])) {
            $urls = json_decode($_POST['media_urls'], true);
            if (is_array($urls) && count($urls) >= 2) {
                $media_for_api = array_values(array_filter($urls));
                $media_url_raw = json_encode($media_for_api);
            } elseif (is_array($urls) && count($urls) === 1) {
                $media_for_api = $urls[0];
                $media_url_raw = $urls[0];
            }
        } elseif (!empty($_POST['media_url'])) {
            $media_for_api = $_POST['media_url'];
            $media_url_raw = $_POST['media_url'];
        }

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '投稿内容を入力してください']);
            break;
        }

        // Threads APIで投稿
        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();
        $result = $api->publishPost($content, $ai_label == 1, $media_for_api, $topic_tag);

        if ($result['success']) {
            // DBに投稿済みとして保存
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                INSERT INTO posts (content, media_url, topic_tag, status, posted_at, threads_post_id, threads_media_id, ai_label, is_ai_generated, source_type, created_at, updated_at)
                VALUES (:content, :media_url, :topic_tag, 'posted', :now, :post_id, :media_id, :ai_label, 0, 'manual', :now, :now)
            ");
            $stmt->execute([
                ':content' => $content,
                ':media_url' => $media_url_raw,
                ':topic_tag' => $topic_tag,
                ':post_id' => $result['post_id'] ?? '',
                ':media_id' => $result['media_id'] ?? '',
                ':ai_label' => $ai_label,
                ':now' => $now,
            ]);
            $msg = isset($result['carousel']) ? "カルーセル投稿({$result['image_count']}枚)しました！" : '投稿しました！';
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? '投稿に失敗しました']);
        }
        break;

    // ----- スレッド（連投）作成 -----
    case 'create_thread':
        $posts_json = $_POST['posts'] ?? '[]';
        $posts = json_decode($posts_json, true);
        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        $topic_tag = trim($_POST['topic_tag'] ?? '');
        // datetime-local の T区切りを スペース区切りに正規化
        if ($scheduled_at) {
            $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
        }

        if (!is_array($posts) || count($posts) < 2) {
            echo json_encode(['success' => false, 'message' => 'スレッドには2つ以上の投稿が必要です']);
            break;
        }

        // スレッドグループID生成（タイムスタンプベース）
        $group_id = (int)(microtime(true) * 1000);

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            INSERT INTO posts (content, topic_tag, status, scheduled_at, thread_group_id, thread_order, created_at, updated_at)
            VALUES (:content, :topic_tag, :status, :scheduled_at, :group_id, :order, :now, :now)
        ");

        foreach ($posts as $i => $content) {
            $stmt->execute([
                ':content' => $content,
                ':topic_tag' => $topic_tag,
                ':status' => $status,
                ':scheduled_at' => $scheduled_at,
                ':group_id' => $group_id,
                ':order' => $i,
                ':now' => $now,
            ]);
        }

        echo json_encode(['success' => true, 'message' => count($posts) . '件のスレッドを作成しました', 'group_id' => $group_id]);
        break;

    // ----- リサイクル（優秀投稿を下書き複製） -----
    case 'recycle':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT content, media_url FROM posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($original) {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO posts (content, media_url, status, source_type, created_at, updated_at) VALUES (:content, :media_url, 'draft', 'recycle', :now, :now)");
            $stmt->execute([':content' => $original['content'], ':media_url' => $original['media_url'] ?? '', ':now' => $now]);
            echo json_encode(['success' => true, 'message' => 'リサイクル投稿を下書きに追加しました']);
        } else {
            echo json_encode(['success' => false, 'message' => '元の投稿が見つかりません']);
        }
        break;

    // ----- 人気トピック一覧 -----
    case 'popular_topics':
        // DB内の過去投稿から使用頻度順に取得
        $stmt = $pdo->query("
            SELECT topic_tag, COUNT(*) as cnt
            FROM posts
            WHERE topic_tag IS NOT NULL AND topic_tag != ''
            GROUP BY topic_tag
            ORDER BY cnt DESC
            LIMIT 20
        ");
        $db_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'tags' => $db_tags]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
