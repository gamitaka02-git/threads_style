<?php
/**
 * ============================================================
 * インサイト / 分析 API エンドポイント - ThreadsStyle
 * ============================================================
 * インサイトデータの取得・保存、優秀投稿の検出、
 * フォロワー推移、キーワード監視
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

    // ----- Threads API からインサイトを取得・保存 -----
    case 'fetch':
        set_time_limit(120); // 多数の投稿を処理するため制限時間を延長

        require_once __DIR__ . '/ThreadsAPI.php';
        $api = new ThreadsAPI();

        // 最新の投稿リストを取得し未登録なら同期する（過去投稿の同期）
        $syncResult = $api->getUserPosts(30);
        if ($syncResult['success'] && !empty($syncResult['posts'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM posts WHERE threads_post_id = :tid");
            $insertStmt = $pdo->prepare("
                INSERT INTO posts (content, status, posted_at, threads_post_id, source_type)
                VALUES (:content, 'posted', :posted_at, :tid, 'sync')
            ");

            foreach ($syncResult['posts'] as $p) {
                $tid = $p['id'];
                $checkStmt->execute([':tid' => $tid]);
                if ($checkStmt->fetch() === false) {
                    $postedAt = date('Y-m-d H:i:s', strtotime($p['timestamp']));
                    $text = $p['text'] ?? '';
                    if (empty($text)) {
                        $mediaType = $p['media_type'] ?? '不明';
                        $text = "({$mediaType}投稿)";
                    }
                    $insertStmt->execute([
                        ':content'   => $text,
                        ':posted_at' => $postedAt,
                        ':tid'       => $tid,
                    ]);
                }
            }
        }

        // 投稿済みの投稿を取得（直近30件）
        $stmt = $pdo->query("SELECT id, threads_post_id FROM posts WHERE status = 'posted' AND threads_post_id != '' ORDER BY posted_at DESC LIMIT 30");
        $posted = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fetched = 0;
        foreach ($posted as $post) {
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
                $fetched++;
            }
            // レートリミットを考慮して間隔を空ける
            usleep(200000); // 200ms
        }

        echo json_encode(['success' => true, 'message' => "{$fetched}件のインサイトを取得しました", 'count' => $fetched]);
        break;

    // ----- インサイト一覧 -----
    case 'list':
        $stmt = $pdo->query("
            SELECT pi.*, p.content, p.posted_at
            FROM post_insights pi
            JOIN posts p ON p.id = pi.post_id
            WHERE pi.id IN (SELECT MAX(id) FROM post_insights GROUP BY post_id)
            ORDER BY pi.fetched_at DESC
            LIMIT 50
        ");
        $insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'insights' => $insights]);
        break;

    // ----- 優秀投稿の自動検出 -----
    case 'detect_top':
        $threshold = (int)get_config('top_post_threshold', 50);

        // 最新のインサイトデータからスコアを計算（いいね*2 + 返信*3 + リポスト*4 + 引用*4）
        $stmt = $pdo->prepare("
            SELECT pi.post_id, p.content,
                   pi.likes, pi.replies, pi.reposts, pi.quotes, pi.views,
                   (pi.likes * 2 + pi.replies * 3 + pi.reposts * 4 + pi.quotes * 4) as score
            FROM post_insights pi
            JOIN posts p ON p.id = pi.post_id
            WHERE pi.id IN (
                SELECT MAX(id) FROM post_insights GROUP BY post_id
            ) AND (pi.likes * 2 + pi.replies * 3 + pi.reposts * 4 + pi.quotes * 4) >= :threshold
            ORDER BY score DESC
            LIMIT 20
        ");
        $stmt->execute([':threshold' => $threshold]);
        $top = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($top as $tp) {
            // 既に登録されていないかチェック
            $check = $pdo->prepare("SELECT 1 FROM top_posts WHERE post_id = :pid");
            $check->execute([':pid' => $tp['post_id']]);
            if ($check->fetch() === false) {
                $ins = $pdo->prepare("INSERT INTO top_posts (post_id, reason, engagement_score) VALUES (:pid, :reason, :score)");
                $ins->execute([
                    ':pid'    => $tp['post_id'],
                    ':reason' => "高エンゲージメント（スコア: {$tp['score']}）",
                    ':score'  => $tp['score'],
                ]);
                $count++;
            }
        }

        echo json_encode(['success' => true, 'count' => $count, 'message' => "{$count}件の新規優秀投稿を検出しました"]);
        break;

    // ----- 優秀投稿一覧 -----
    case 'top_list':
        $stmt = $pdo->query("
            SELECT tp.*, p.content
            FROM top_posts tp
            JOIN posts p ON p.id = tp.post_id
            ORDER BY tp.engagement_score DESC
            LIMIT 30
        ");
        $top_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'top_posts' => $top_posts]);
        break;

    // ----- フォロワー推移データ -----
    case 'follower_history':
        $stmt = $pdo->query("
            SELECT MAX(follower_count) as count, strftime('%m/%d', recorded_at) as date, date(recorded_at) as full_date
            FROM follower_history
            GROUP BY full_date
            ORDER BY full_date DESC
            LIMIT 60
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array_reverse($data); // チャート用に古い順にする
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ----- 投稿時間別エンゲージメントデータ -----
    case 'hourly_engagement':
        $stmt = $pdo->query("
            SELECT
                CAST(strftime('%H', p.posted_at) AS INTEGER) as hour,
                SUM(pi.likes + pi.replies + pi.reposts) as engagement
            FROM post_insights pi
            JOIN posts p ON p.id = pi.post_id
            WHERE p.posted_at IS NOT NULL
            AND pi.id IN (SELECT MAX(id) FROM post_insights GROUP BY post_id)
            GROUP BY hour
            ORDER BY hour ASC
        ");
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 0-23時のデータを埋める
        $hourly = [];
        $hourMap = [];
        foreach ($raw as $r) {
            $hourMap[(int)$r['hour']] = (int)$r['engagement'];
        }
        for ($h = 0; $h < 24; $h++) {
            $hourly[] = ['hour' => $h, 'engagement' => $hourMap[$h] ?? 0];
        }

        echo json_encode(['success' => true, 'data' => $hourly]);
        break;

    // ----- エンゲージメント推移データ -----
    case 'engagement_trend':
        $stmt = $pdo->query("
            SELECT
                strftime('%m/%d', pi.fetched_at) as date,
                date(pi.fetched_at) as full_date,
                SUM(pi.likes) as likes,
                SUM(pi.replies) as replies,
                SUM(pi.reposts) as reposts
            FROM post_insights pi
            WHERE pi.id IN (SELECT MAX(id) FROM post_insights GROUP BY post_id)
            GROUP BY full_date
            ORDER BY full_date DESC
            LIMIT 30
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = array_reverse($data); // チャート用に古い順にする
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // ----- キーワード追加 -----
    case 'add_keyword':
        $keyword = trim($_POST['keyword'] ?? '');
        if (empty($keyword)) {
            echo json_encode(['success' => false, 'message' => 'キーワードを入力してください']);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO keyword_monitors (keyword) VALUES (:kw)");
        $stmt->execute([':kw' => $keyword]);
        echo json_encode(['success' => true, 'message' => 'キーワードを追加しました']);
        break;

    // ----- キーワード一覧 -----
    case 'list_keywords':
        $stmt = $pdo->query("SELECT * FROM keyword_monitors ORDER BY created_at DESC");
        $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'keywords' => $keywords]);
        break;

    // ----- キーワード削除 -----
    case 'remove_keyword':
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM keyword_monitors WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true, 'message' => 'キーワードを削除しました']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
