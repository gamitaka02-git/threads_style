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
        $ai_label = (int)($_POST['ai_label'] ?? 0);
        $is_ai_generated = (int)($_POST['is_ai_generated'] ?? 0);
        $source_type = $_POST['source_type'] ?? 'manual';
        $source_url = $_POST['source_url'] ?? '';

        $stmt = $pdo->prepare("
            INSERT INTO posts (content, status, scheduled_at, ai_label, is_ai_generated, source_type, source_url)
            VALUES (:content, :status, :scheduled_at, :ai_label, :is_ai_generated, :source_type, :source_url)
        ");
        $stmt->execute([
            ':content' => $content,
            ':status' => $status,
            ':scheduled_at' => $scheduled_at,
            ':ai_label' => $ai_label,
            ':is_ai_generated' => $is_ai_generated,
            ':source_type' => $source_type,
            ':source_url' => $source_url,
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => '投稿を作成しました']);
        break;

    // ----- 投稿の更新 -----
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        $ai_label = (int)($_POST['ai_label'] ?? 0);

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '投稿内容を入力してください']);
            break;
        }

        $stmt = $pdo->prepare("
            UPDATE posts SET content = :content, status = :status, scheduled_at = :scheduled_at,
            ai_label = :ai_label, updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            ':content' => $content,
            ':status' => $status,
            ':scheduled_at' => $scheduled_at ?: null,
            ':ai_label' => $ai_label,
            ':id' => $id,
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

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => '投稿内容を入力してください']);
            break;
        }

        // Threads APIで投稿
        require_once __DIR__ . '/threads_api.php';
        $result = threads_publish_post($content, $ai_label);

        if ($result['success']) {
            // DBに投稿済みとして保存
            $stmt = $pdo->prepare("
                INSERT INTO posts (content, status, posted_at, threads_post_id, threads_media_id, ai_label, source_type)
                VALUES (:content, 'posted', datetime('now'), :post_id, :media_id, :ai_label, 'manual')
            ");
            $stmt->execute([
                ':content' => $content,
                ':post_id' => $result['post_id'] ?? '',
                ':media_id' => $result['media_id'] ?? '',
                ':ai_label' => $ai_label,
            ]);
            echo json_encode(['success' => true, 'message' => '投稿しました！']);
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

        if (!is_array($posts) || count($posts) < 2) {
            echo json_encode(['success' => false, 'message' => 'スレッドには2つ以上の投稿が必要です']);
            break;
        }

        // スレッドグループID生成（タイムスタンプベース）
        $group_id = (int)(microtime(true) * 1000);

        $stmt = $pdo->prepare("
            INSERT INTO posts (content, status, scheduled_at, thread_group_id, thread_order)
            VALUES (:content, :status, :scheduled_at, :group_id, :order)
        ");

        foreach ($posts as $i => $content) {
            $stmt->execute([
                ':content' => $content,
                ':status' => $status,
                ':scheduled_at' => $scheduled_at,
                ':group_id' => $group_id,
                ':order' => $i,
            ]);
        }

        echo json_encode(['success' => true, 'message' => count($posts) . '件のスレッドを作成しました', 'group_id' => $group_id]);
        break;

    // ----- リサイクル（優秀投稿を下書き複製） -----
    case 'recycle':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT content FROM posts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($original) {
            $stmt = $pdo->prepare("INSERT INTO posts (content, status, source_type) VALUES (:content, 'draft', 'recycle')");
            $stmt->execute([':content' => $original['content']]);
            echo json_encode(['success' => true, 'message' => 'リサイクル投稿を下書きに追加しました']);
        } else {
            echo json_encode(['success' => false, 'message' => '元の投稿が見つかりません']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
