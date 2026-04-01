<?php
/**
 * ============================================================
 * ダッシュボード - ThreadsStyle メイン管理画面
 * ============================================================
 */
require_once __DIR__ . '/init.php';

// ログインチェック
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// ダッシュボード用のデータ取得
$total_posts = 0;
$scheduled_posts = 0;
$posted_count = 0;
$latest_follower = 0;
$next_scheduled = null;
$recent_posts = [];
$token_days_left = null;
$cron_last_run = null;

try {
    // 投稿統計
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $total_posts = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'scheduled'");
    $scheduled_posts = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'posted'");
    $posted_count = (int) $stmt->fetchColumn();

    // 最新フォロワー数
    $stmt = $pdo->query("SELECT follower_count FROM follower_history ORDER BY recorded_at DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row)
        $latest_follower = (int) $row['follower_count'];

    // 次の予約投稿
    $stmt = $pdo->query("SELECT * FROM posts WHERE status = 'scheduled' AND scheduled_at > datetime('now') ORDER BY scheduled_at ASC LIMIT 1");
    $next_scheduled = $stmt->fetch(PDO::FETCH_ASSOC);

    // 最近の投稿（直近10件）
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT 10");
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // トークン残り日数
    $token_expires = get_config('threads_token_expires_at');
    if ($token_expires) {
        $expires_ts = strtotime($token_expires);
        if ($expires_ts) {
            $token_days_left = (int) ceil(($expires_ts - time()) / 86400);
        }
    }

    // Cron最終実行時刻
    $cron_last_run = get_config('cron_last_run');
} catch (Exception $e) {
    // エラー時はデフォルト値のまま
}

$version = defined('TOOL_VERSION') ? TOOL_VERSION : 'v1.0.0';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads_Style | Dashboard</title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</head>

<body>

    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="メニュー">☰</button>

    <div class="app-layout">
        <!-- ===== Sidebar ===== -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    Threads_Style
                    <span>運用・分析効率化ツール</span>
                </div>
                <!-- Connected Account Info -->
                <div id="threadsProfileSidebar"></div>
            </div>

            <nav class="sidebar-nav">
                <a href="#" data-section="dashboard" class="active">
                    <span class="nav-icon">📊</span>
                    ダッシュボード
                </a>
                <a href="#" data-section="posts">
                    <span class="nav-icon">✏️</span>
                    投稿管理
                    <?php if ($scheduled_posts > 0): ?>
                        <span class="nav-badge"><?= $scheduled_posts ?></span>
                    <?php endif; ?>
                </a>
                <a href="#" data-section="ai-generate">
                    <span class="nav-icon">🤖</span>
                    AI生成
                </a>
                <a href="#" data-section="analytics">
                    <span class="nav-icon">📈</span>
                    分析
                </a>
                <a href="#" data-section="settings">
                    <span class="nav-icon">⚙️</span>
                    設定
                </a>
            </nav>

            <div class="sidebar-footer">
                <span class="version-tag"><?= htmlspecialchars($version) ?></span>
                <a href="?logout=1" class="logout-btn">ログアウト</a>
            </div>
        </aside>

        <!-- ===== Main Content ===== -->
        <main class="main-content">

            <!-- ==================== DASHBOARD ==================== -->
            <section id="section-dashboard" class="section active">
                <div class="section-header">
                    <div class="flex justify-between items-end w-full">
                        <div>
                            <h2>ダッシュボード</h2>
                            <p>Threads_Style の概要と主要な指標です</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="syncPastPosts()"
                                style="color:var(--color-info);">🔄 過去投稿の同期</button>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-label">システム総投稿数</div>
                        <div class="kpi-value text-xl"><?= $total_posts ?></div>
                        <div class="kpi-change positive">このツールから</div>
                        <div class="kpi-icon">✏️</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">投稿完了</div>
                        <div class="kpi-value"><?= $posted_count ?></div>
                        <div class="kpi-change positive">配信済み</div>
                        <div class="kpi-icon">✅</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">予約中</div>
                        <div class="kpi-value"><?= $scheduled_posts ?></div>
                        <div class="kpi-change"><?= $scheduled_posts > 0 ? 'スタンバイ' : '予約なし' ?></div>
                        <div class="kpi-icon">📅</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-label">フォロワー数</div>
                        <div class="kpi-value"><?= number_format($latest_follower) ?></div>
                        <div class="kpi-change">最新取得値</div>
                        <div class="kpi-icon">👥</div>
                    </div>

                    <?php if ($token_days_left !== null): ?>
                        <div class="kpi-card">
                            <div class="kpi-label">トークン残り</div>
                            <div
                                class="kpi-value <?= $token_days_left <= 7 ? 'style="color:var(--color-error)"' : ($token_days_left <= 14 ? 'style="color:var(--color-warning)"' : '') ?>">
                                <?= $token_days_left ?>日
                            </div>
                            <div class="kpi-change <?= $token_days_left <= 7 ? 'negative' : '' ?>">
                                <?= $token_days_left <= 7 ? '要更新' : '有効' ?>
                            </div>
                            <div class="kpi-icon">🔑</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cron Status -->
                <div class="card" style="margin-bottom: var(--space-lg);">
                    <div class="card-header">
                        <h3 class="card-title">⏱️ 自動実行ステータス</h3>
                    </div>
                    <div style="display:flex; align-items:center; gap: var(--space-lg); flex-wrap:wrap;">
                        <div>
                            <div
                                style="font-size:var(--font-size-sm); color:var(--color-text-secondary); margin-bottom:4px;">
                                最終実行確認</div>
                            <?php if ($cron_last_run): ?>
                                <div style="font-size:var(--font-size-lg); font-weight:700;">
                                    <?= htmlspecialchars($cron_last_run) ?>
                                </div>
                                <div class="text-xs text-muted" style="margin-top:4px;">
                                    <?php
                                    $diff = time() - strtotime($cron_last_run);
                                    if ($diff < 3600)
                                        echo round($diff / 60) . '分前';
                                    elseif ($diff < 86400)
                                        echo round($diff / 3600) . '時間前';
                                    else
                                        echo round($diff / 86400) . '日前';
                                    ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size:var(--font-size-md); color:var(--color-warning); font-weight:600;">未設定
                                </div>
                                <p class="text-xs text-muted" style="margin-top:4px;">設定画面からCronの設定を行ってください</p>
                            <?php endif; ?>
                        </div>
                        <div style="margin-left:auto;">
                            <?php if ($cron_last_run && $diff < 7200): ?>
                                <span class="badge badge-success">✅ 正常稼働中</span>
                            <?php elseif ($cron_last_run): ?>
                                <span class="badge badge-warning">⚠️ 実行が遅延しています</span>
                            <?php else: ?>
                                <span class="badge badge-error">❌ 未設定</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Next Scheduled Post -->
                <div class="card" style="margin-bottom: var(--space-lg);">
                    <div class="card-header">
                        <h3 class="card-title">📅 次の予約投稿</h3>
                    </div>
                    <?php if ($next_scheduled): ?>
                        <div style="display:flex; align-items:center; gap: var(--space-lg); flex-wrap:wrap;">
                            <div>
                                <div
                                    style="font-size:var(--font-size-sm); color:var(--color-text-secondary); margin-bottom:4px;">
                                    投稿予定日時</div>
                                <div style="font-size:var(--font-size-lg); font-weight:700;">
                                    <?= htmlspecialchars($next_scheduled['scheduled_at']) ?>
                                </div>
                            </div>
                            <div style="flex:1; min-width:200px;">
                                <div
                                    style="font-size:var(--font-size-sm); color:var(--color-text-secondary); margin-bottom:4px;">
                                    内容プレビュー</div>
                                <div style="font-size:var(--font-size-sm); line-height:1.6; color:var(--color-text);">
                                    <?= htmlspecialchars(mb_strimwidth($next_scheduled['content'], 0, 140, '...', 'UTF-8')) ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding:var(--space-lg);">
                            <p>予約中の投稿はありません</p>
                            <button class="btn btn-primary btn-sm" onclick="switchSection('posts')">新規投稿を作成</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Posts -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📝 最近の投稿</h3>
                        <button class="btn btn-ghost btn-sm" onclick="switchSection('posts')">すべて見る →</button>
                    </div>
                    <?php if (count($recent_posts) > 0): ?>
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>内容</th>
                                        <th>ステータス</th>
                                        <th>予約日時</th>
                                        <th>作成日</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recent_posts, 0, 5) as $post): ?>
                                        <tr>
                                            <td class="post-preview">
                                                <?= htmlspecialchars(mb_strimwidth($post['content'], 0, 60, '...', 'UTF-8')) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'badge-draft';
                                                $badge_text = '下書き';
                                                switch ($post['status']) {
                                                    case 'posted':
                                                        $badge_class = 'badge-success';
                                                        $badge_text = '投稿済';
                                                        break;
                                                    case 'scheduled':
                                                        $badge_class = 'badge-info';
                                                        $badge_text = '予約中';
                                                        break;
                                                    case 'failed':
                                                        $badge_class = 'badge-error';
                                                        $badge_text = '失敗';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                                                <?php if ($post['ai_label']): ?>
                                                    <span class="badge badge-warning" style="margin-left:4px;">AI</span>
                                                <?php endif; ?>
                                            </td>
                                            <td
                                                style="white-space:nowrap; color:var(--color-info); font-size:var(--font-size-xs); font-weight:600;">
                                                <?= htmlspecialchars($post['status'] === 'scheduled' ? $post['scheduled_at'] : '-') ?>
                                            </td>
                                            <td
                                                style="white-space:nowrap; color:var(--color-text-secondary); font-size:var(--font-size-xs);">
                                                <?= htmlspecialchars($post['created_at']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">✏️</div>
                            <p>まだ投稿がありません</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ==================== POSTS ==================== -->
            <section id="section-posts" class="section">
                <div class="section-header">
                    <h2>投稿管理</h2>
                    <p>投稿の作成・編集・予約・スレッド管理</p>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="tab-post-create">新規作成</button>
                    <button class="tab-btn" data-tab="tab-post-list">投稿一覧</button>
                    <button class="tab-btn" data-tab="tab-post-thread">スレッド作成</button>
                    <button class="tab-btn" data-tab="tab-post-repurpose">リパーパス</button>
                </div>

                <!-- Tab: 新規作成 -->
                <div class="tab-panel active" id="tab-post-create">
                    <div class="post-composer">
                        <!-- 複数画像プレビューエリア -->
                        <div id="postMediaGallery" class="post-media-gallery" style="display:none;"></div>
                        <!-- 一枚目の画像の場合は従来のプレビュー -->
                        <div id="postMediaPreview" class="post-media-preview">
                            <img src="" alt="Preview">
                            <button class="remove-media-btn" onclick="removePostMedia('create', 0)">✕</button>
                        </div>
                        <input type="hidden" id="postMediaUrl" value="">
                        <div class="post-composer-body">
                            <textarea id="postContent"
                                placeholder="いま何を考えていますか？&#10;&#10;Threads に投稿する内容を入力してください..."></textarea>
                        </div>
                        <div class="post-char-count">
                            <span id="charCount">0</span> / 500
                        </div>

                        <!-- トピック入力エリア -->
                        <div class="topic-tag-area">
                            <div class="topic-tag-input-row">
                                <span class="topic-tag-hash">#</span>
                                <input type="text" id="postTopicTag" class="topic-tag-input"
                                    placeholder="トピックタグ（任意）"
                                    maxlength="50"
                                    oninput="sanitizeTopicTagInput(this)">
                                <button class="topic-popular-btn" onclick="togglePopularTopics('create')" id="createPopularBtn" title="人気トピックを見る">📊 人気</button>
                            </div>
                            <div id="createPopularTopics" class="popular-topics-panel" style="display:none;"></div>
                        </div>

                        <div class="post-composer-footer">
                            <div class="post-composer-options">
                                <div class="toggle-wrap" title="ツール内でAI生成投稿を識別する管理用タグです。Threads上には表示されません。">
                                    <label class="toggle">
                                        <input type="checkbox" id="aiLabelToggle">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="text-sm">AIタグ <span style="font-size:var(--font-size-xs); color:var(--color-text-muted);">（管理用）</span></span>
                                </div>
                                <button class="btn-upload" id="createUploadBtn" onclick="triggerImageUpload('create')" title="画像を追加（最大10枚）">
                                    🖼️ <span style="font-size:var(--font-size-xs); margin-left:10px;">画像を追加</span>
                                    <span id="createImageCount" class="image-count-badge" style="display:none;"></span>
                                </button>
                                <div class="form-group" style="margin:0;">
                                    <input type="datetime-local" id="scheduleAt" class="form-input"
                                        style="width:auto; padding:6px 10px; font-size:var(--font-size-xs);">
                                </div>
                            </div>
                            <div class="post-composer-actions">
                                <button class="btn btn-secondary btn-sm" onclick="savePostDraft()">下書き保存</button>
                                <button class="btn btn-primary btn-sm" onclick="schedulePost()">予約投稿</button>
                                <button class="btn btn-primary btn-sm" onclick="publishPostNow()"
                                    style="background:var(--color-success);高さ:10px;"><span>今すぐ投稿</span></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: 投稿一覧 -->
                <div class="tab-panel" id="tab-post-list">
                    <div class="flex justify-between items-center mb-md">
                        <div class="flex gap-sm">
                            <button class="btn btn-ghost btn-sm active" data-filter="all">すべて</button>
                            <button class="btn btn-ghost btn-sm" data-filter="draft">下書き</button>
                            <button class="btn btn-ghost btn-sm" data-filter="scheduled">予約中</button>
                            <button class="btn btn-ghost btn-sm" data-filter="posted">投稿済</button>
                        </div>
                        <!-- <button class="btn btn-secondary btn-sm"
                            onclick="loadPostList(document.querySelector('[data-filter].active')?.dataset.filter || 'all')">🔄
                            更新</button> -->
                    </div>
                    <div id="postListContainer">
                        <div class="empty-state">
                            <div class="spinner" style="margin:0 auto var(--space-md);"></div>
                            <p>読み込み中...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: スレッド作成 -->
                <div class="tab-panel" id="tab-post-thread">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🧵 スレッド（連投）作成</h3>
                            <button class="btn btn-secondary btn-sm" onclick="addThreadItem()">＋ 投稿を追加</button>
                        </div>
                        <p class="text-sm text-muted mb-lg">長文を複数の投稿に分割し、一連のスレッドとして予約投稿できます。</p>

                        <div class="thread-posts" id="threadPosts">
                            <div class="thread-item">
                                <textarea class="form-textarea thread-textarea" placeholder="スレッド 1つ目の投稿..."
                                    rows="3"></textarea>
                            </div>
                            <div class="thread-item">
                                <textarea class="form-textarea thread-textarea" placeholder="スレッド 2つ目の投稿..."
                                    rows="3"></textarea>
                            </div>
                        </div>

                        <!-- トピックタグ（スレッド作成） -->
                        <div class="topic-tag-area">
                            <div class="topic-tag-input-row">
                                <span class="topic-tag-hash">#</span>
                                <input type="text" id="threadTopicTag" class="topic-tag-input"
                                    placeholder="トピックタグ（任意・スレッド全体に適用）"
                                    maxlength="50"
                                    oninput="sanitizeTopicTagInput(this)">
                                <button class="topic-popular-btn" onclick="togglePopularTopics('thread')" id="threadPopularBtn" title="人気トピックを見る">📊 人気</button>
                            </div>
                            <div id="threadPopularTopics" class="popular-topics-panel" style="display:none;"></div>
                        </div>

                        <div class="flex justify-between items-center mt-lg"
                            style="flex-wrap:wrap; gap:var(--space-sm);">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">予約日時</label>
                                <input type="datetime-local" id="threadScheduleAt" class="form-input"
                                    style="width:auto;">
                            </div>
                            <div class="flex gap-sm">
                                <button class="btn btn-secondary" onclick="saveThreadDraft()">下書き保存</button>
                                <button class="btn btn-primary" onclick="scheduleThread()">スレッドを予約</button>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Tab: リパーパス -->
                <div class="tab-panel" id="tab-post-repurpose">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">♻️ リパーパス</h3>
                        </div>
                        <p class="text-sm text-muted mb-lg">ブログ記事URLや過去のX投稿から、Threads形式のコンテンツを自動生成します。</p>

                        <div class="form-group">
                            <label class="form-label">コンテンツURL</label>
                            <input type="url" id="repurposeUrl" class="form-input"
                                placeholder="https://example.com/blog/my-article">
                            <p class="form-help">WordPress、note、X(Twitter) の投稿URLに対応</p>
                        </div>

                        <button class="btn btn-primary" onclick="repurposeContent()" id="repurposeBtn">
                            🔄 Threads形式に変換
                        </button>

                        <div id="repurposeResult" class="mt-lg hidden">
                            <h4 style="margin-bottom:var(--space-md);">生成結果</h4>
                            <div class="ai-suggestions" id="repurposeSuggestions"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== AI GENERATE ==================== -->
            <section id="section-ai-generate" class="section">
                <div class="section-header">
                    <h2>AI生成</h2>
                    <p>Gemini AIによるトーン学習型投稿生成</p>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="tab-ai-analyze">自己解析</button>
                    <button class="tab-btn" data-tab="tab-ai-style">スタイルガイド</button>
                    <button class="tab-btn" data-tab="tab-ai-generate">投稿生成</button>
                </div>

                <!-- Tab: 自己解析 -->
                <div class="tab-panel active" id="tab-ai-analyze">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🔍 初期自己解析</h3>
                        </div>
                        <p class="text-sm text-muted mb-lg">
                            Threads APIから直近の手動投稿を取得し、Gemini AIがあなたの文体・性格を分析します。<br>
                            <strong>※ AIラベルなしの手動投稿のみ</strong>が学習対象です（純度保持ロジック）。
                        </p>

                        <div id="analysisStatus">
                            <button class="btn btn-primary btn-lg" onclick="startAnalysis()" id="startAnalysisBtn">
                                🧠 自己解析を開始
                            </button>
                        </div>

                        <!-- Analysis Animation -->
                        <div id="analysisAnimation" class="analysis-animation hidden">
                            <div class="pulse-ring">🧠</div>
                            <h3>分析中...</h3>
                            <p class="text-sm text-muted">あなたの投稿スタイルを解析しています</p>
                            <div class="analysis-steps">
                                <div class="analysis-step" id="step-fetch">
                                    <span>⏳</span> 投稿データを取得中...
                                </div>
                                <div class="analysis-step" id="step-analyze">
                                    <span>⏳</span> 文体・トーンを分析中...
                                </div>
                                <div class="analysis-step" id="step-generate">
                                    <span>⏳</span> スタイルガイドを生成中...
                                </div>
                                <div class="analysis-step" id="step-save">
                                    <span>⏳</span> 結果を保存中...
                                </div>
                            </div>
                        </div>

                        <div id="analysisResult" class="hidden mt-lg">
                            <div class="card" style="background:var(--color-bg); border-color:var(--color-success);">
                                <h4 style="color:var(--color-success); margin-bottom:var(--space-md);">✅ 分析完了</h4>
                                <div id="analysisResultContent" class="text-sm"
                                    style="line-height:1.8; white-space:pre-wrap;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: スタイルガイド -->
                <div class="tab-panel" id="tab-ai-style">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 スタイルガイド</h3>
                            <button class="btn btn-primary btn-sm" onclick="saveStyleGuide()">💾 保存</button>
                        </div>
                        <p class="text-sm text-muted mb-md">
                            AI解析結果をMarkdown形式で編集できます。投稿生成時にこのガイドが参照されます。
                        </p>
                        <div class="style-guide-editor">
                            <textarea id="styleGuideEditor"
                                placeholder="# スタイルガイド&#10;&#10;ここにAI解析結果が表示されます。&#10;手動で編集することもできます。"></textarea>
                            <div class="style-guide-preview" id="styleGuidePreview">
                                <p class="text-muted">プレビューがここに表示されます</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: 投稿生成 -->
                <div class="tab-panel" id="tab-ai-generate">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">✨ トーン学習型投稿生成</h3>
                        </div>
                        <p class="text-sm text-muted mb-lg">
                            学習済みのトーンに基づき、3つのバリエーションを生成します。
                        </p>

                        <div class="form-group">
                            <label class="form-label">トピック・テーマ</label>
                            <input type="text" id="generateTopic" class="form-input"
                                placeholder="例: 朝のルーティン、プログラミング学習のコツ">
                        </div>

                        <div class="form-group">
                            <label class="form-label">追加の指示（任意）</label>
                            <textarea id="generateInstructions" class="form-textarea" rows="3"
                                placeholder="例: カジュアルな口調で、絵文字多めに"></textarea>
                        </div>

                        <button class="btn btn-primary" onclick="generatePosts()" id="generateBtn">
                            ✨ 3案を生成
                        </button>

                        <div id="generateResults" class="mt-lg hidden">
                            <h4 style="margin-bottom:var(--space-md);">生成結果</h4>
                            <div class="ai-suggestions" id="generateSuggestions"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== ANALYTICS ==================== -->
            <section id="section-analytics" class="section">
                <div class="section-header">
                    <h2>分析</h2>
                    <p>投稿パフォーマンスとアカウントの成長分析</p>
                </div>

                <!-- Tabs -->
                <div class="tabs">
                    <button class="tab-btn active" data-tab="tab-analytics-overview">概要</button>
                    <button class="tab-btn" data-tab="tab-analytics-engagement">エンゲージメント</button>
                    <button class="tab-btn" data-tab="tab-analytics-top">優秀投稿</button>
                    <!-- <button class="tab-btn" data-tab="tab-analytics-keywords">キーワード監視</button> -->
                </div>

                <!-- Tab: 概要 -->
                <div class="tab-panel active" id="tab-analytics-overview">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-lg);">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">👥 フォロワー推移</h3>
                                <button class="btn btn-ghost btn-sm" onclick="refreshFollowerChart()">更新</button>
                            </div>
                            <div class="chart-container">
                                <canvas id="followerChart"></canvas>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">⏰ 投稿時間別エンゲージメント</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="hourlyEngagementChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-lg">
                        <div class="card-header">
                            <h3 class="card-title">📊 エンゲージメント推移</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="engagementTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tab: エンゲージメント -->
                <div class="tab-panel" id="tab-analytics-engagement">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 投稿別エンゲージメント</h3>
                            <div class="flex" style="gap:var(--space-sm);">
                                <button class="btn btn-ghost btn-sm" onclick="downloadInsightsCSV()">📄
                                    CSVダウンロード</button>
                                <button class="btn btn-secondary btn-sm" onclick="fetchInsights()">📥 最新データを取得</button>
                            </div>
                        </div>
                        <div id="engagementTable">
                            <div class="empty-state">
                                <div class="empty-state-icon">📊</div>
                                <p>インサイトデータを取得してください</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: 優秀投稿 -->
                <div class="tab-panel" id="tab-analytics-top">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏆 優秀投稿DB</h3>
                            <button class="btn btn-secondary btn-sm" onclick="detectTopPosts()">🔍 自動検出</button>
                        </div>
                        <p class="text-sm text-muted mb-lg">エンゲージメントの高い投稿を自動保存し、リサイクル（再利用）に活用します。</p>
                        <div id="topPostsList">
                            <div class="empty-state">
                                <div class="empty-state-icon">🏆</div>
                                <p>まだ優秀投稿が登録されていません</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: キーワード監視 (将来的な拡張用として一時コメントアウト)
                <div class="tab-panel" id="tab-analytics-keywords">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🔍 キーワード監視</h3>
                        </div>
                        <p class="text-sm text-muted mb-lg">指定キーワードやメンションを監視し、交流のきっかけをリスト化します。</p>

                        <div class="flex gap-sm mb-lg">
                            <input type="text" id="newKeyword" class="form-input" placeholder="監視するキーワードを入力"
                                style="flex:1;">
                            <button class="btn btn-primary" onclick="addKeyword()">追加</button>
                        </div>

                        <div id="keywordList">
                            <div class="empty-state">
                                <div class="empty-state-icon">🔍</div>
                                <p>キーワードを追加して監視を開始してください</p>
                            </div>
                        </div>
                    </div>
                </div>
                -->
            </section>

            <!-- ==================== SETTINGS ==================== -->
            <section id="section-settings" class="section">
                <div class="section-header">
                    <h2>設定</h2>
                    <p>API連携・ライセンス・システム設定</p>
                </div>

                <div class="settings-grid">
                    <!-- Threads API -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon threads">🧵</div>
                            <div>
                                <h4>Threads API</h4>
                                <p class="text-xs text-muted">Meta Threads API との連携設定</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label class="form-label">アクセストークン</label>
                                <input type="password" id="settingsThreadsToken" class="form-input"
                                    placeholder="アクセストークンを入力"
                                    value="<?= htmlspecialchars(get_config('threads_access_token')) ?>">
                                <div class="flex mt-sm justify-end">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="exchangeShortToken()"
                                        style="color:var(--color-primary);">✨ 短期トークンを長期に交換</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Meta App ID</label>
                                <input type="text" id="settingsThreadsAppId" class="form-input"
                                    placeholder="<?= defined('THREADS_APP_ID') && THREADS_APP_ID ? 'config.php で設定中' : 'App ID を入力' ?>"
                                    value="<?= htmlspecialchars(get_config('threads_app_id')) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Meta App Secret</label>
                                <input type="password" id="settingsThreadsAppSecret" class="form-input"
                                    placeholder="<?= defined('THREADS_APP_SECRET') && THREADS_APP_SECRET ? 'config.php で設定中' : 'App Secret を入力' ?>"
                                    value="<?= get_config('threads_app_secret') ? '***設定済み***' : '' ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">ユーザーID</label>
                                <input type="text" id="settingsThreadsUserId" class="form-input"
                                    placeholder="Threads User ID"
                                    value="<?= htmlspecialchars(get_config('threads_user_id')) ?>">

                                <div class="flex mt-sm justify-end">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="getThreadsProfile()"
                                        style="color:var(--color-info);">🔄 アカウント情報を同期</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">トークン有効期限</label>
                                <input type="date" id="settingsTokenExpires" class="form-input"
                                    value="<?= htmlspecialchars(get_config('threads_token_expires_at')) ?>">
                                <p class="form-help">長期トークンの有効期限（60日間）。自動リフレッシュの基準。</p>
                                <p class="form-help text-xs" style="color:var(--color-info);">※これらの設定は <code>config.php</code> よりも優先されます。空欄の場合は <code>config.php</code> の値が使用されます。
                                </p>
                            </div>

                            <!-- Token Countdown -->
                            <?php if ($token_days_left !== null): ?>
                                <div class="token-countdown mt-md">
                                    <div
                                        class="token-days <?= $token_days_left <= 7 ? 'danger' : ($token_days_left <= 14 ? 'warning' : 'safe') ?>">
                                        <?= $token_days_left ?>
                                    </div>
                                    <div>
                                        <div class="font-bold">日</div>
                                        <div class="text-xs text-muted">トークン残り有効期間</div>
                                    </div>
                                    <button class="btn btn-secondary btn-sm" onclick="refreshToken()"
                                        style="margin-left:auto;">🔄 手動更新</button>
                                </div>
                            <?php endif; ?>

                            <div class="mt-md">
                                <button class="btn btn-secondary btn-sm sync" onclick="syncPastPosts()">📥
                                    過去の投稿を同期</button>
                                <p class="text-xs text-muted mt-sm">※ツール導入前の投稿データを分析対象として取り込みます。</p>
                            </div>

                            <button class="btn btn-primary mt-md" onclick="saveThreadsSettings()">保存</button>
                        </div>
                    </div>

                    <!-- Gemini API -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon gemini">✨</div>
                            <div>
                                <h4>Gemini API</h4>
                                <p class="text-xs text-muted">Google Gemini API 連携設定</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label class="form-label">APIキー</label>
                                <input type="password" id="settingsGeminiKey" class="form-input"
                                    placeholder="Gemini API Key"
                                    value="<?= htmlspecialchars(get_config('gemini_api_key')) ?>">
                                <p class="form-help"><a href="https://aistudio.google.com/" target="_blank">Google AI
                                        Studio</a> で取得</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">モデル</label>
                                <select id="settingsGeminiModel" class="form-select">
                                    <?php $current_model = get_config('gemini_model', 'gemini-3.1-flash-lite-preview'); ?>
                                    <option value="gemini-3.1-flash-lite-preview"
                                        <?= $current_model === 'gemini-3.1-flash-lite-preview' ? 'selected' : '' ?>>Gemini
                                        3.1 Flash Lite（最速・軽量）</option>
                                    <option value="gemini-3-flash-preview" <?= $current_model === 'gemini-3-flash-preview' ? 'selected' : '' ?>>Gemini 3 Flash（バランス）</option>
                                    <option value="gemini-2.5-flash-lite-preview-06-17"
                                        <?= $current_model === 'gemini-2.5-flash-lite-preview-06-17' ? 'selected' : '' ?>>
                                        Gemini 2.5 Flash Lite（安定）</option>
                                </select>
                            </div>
                            <button class="btn btn-primary mt-md" onclick="saveGeminiSettings()">保存</button>
                        </div>
                    </div>

                    <!-- License -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon license">🔑</div>
                            <div>
                                <h4>ライセンス</h4>
                                <p class="text-xs text-muted">ライセンスキーの管理</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="form-group">
                                <label class="form-label">ライセンスキー</label>
                                <input type="password" id="settingsLicenseKey" class="form-input"
                                    placeholder="XXXX-XXXX-XXXX-XXXX"
                                    value="<?= htmlspecialchars(get_config('license_key')) ?>">
                            </div>
                            <div class="flex gap-sm">
                                <button class="btn btn-primary" onclick="saveLicenseKey()">保存</button>
                                <button class="btn btn-secondary" onclick="verifyLicense()">✓ 認証チェック</button>
                            </div>
                            <div id="licenseStatus" class="mt-md"></div>
                        </div>
                    </div>

                    <!-- Auto-Post Settings -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background:rgba(225,48,108,0.15);">📅</div>
                            <div>
                                <h4>予約投稿設定</h4>
                                <p class="text-xs text-muted">投稿スケジュール・ゆらぎ設定</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <div class="toggle-wrap mb-md" style="display:none;">
                                <label class="toggle">
                                    <input type="checkbox" id="settingsAutoPost" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>予約投稿を有効にする</span>
                            </div>
                            <div class="form-group">
                                <label class="form-label">投稿間隔のゆらぎ（分）</label>
                                <input type="number" id="settingsVariance" class="form-input" min="0" max="120"
                                    value="<?= htmlspecialchars(get_config('post_interval_variance', '30')) ?>">
                                <p class="form-help">Bot判定回避のため、予約時刻に対してランダムなゆらぎを加えます。</p>
                            </div>
                            <div class="toggle-wrap mb-md">
                                <label class="toggle">
                                    <input type="checkbox" id="settingsAiLabelDefault"
                                        <?= get_config('ai_label_default') === '1' ? 'checked' : '' ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span>AI生成投稿にデフォルトでAIタグを付与（管理用）</span>
                            </div>
                            <p class="form-help" style="margin-top:-8px; margin-bottom:var(--space-md); color:var(--color-text-muted); font-size:var(--font-size-xs);">※ AIタグはツール内での管理・識別用です。現時点ではThreads上に「AIラベル」として表示されません。</p>
                            <button class="btn btn-primary" onclick="saveAutoPostSettings()">保存</button>
                        </div>
                    </div>

                    <!-- Cron Settings -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background:rgba(41,121,255,0.15);">⏱️</div>
                            <div>
                                <h4>Cron（自動実行）設定</h4>
                                <p class="text-xs text-muted">予約投稿・インサイト取得の自動実行設定</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <p class="text-sm" style="line-height:1.8; margin-bottom:var(--space-md);">
                                以下のコマンドをサーバーのCron設定に<strong>1回だけ</strong>登録してください。<br>
                                以降は管理画面で予約投稿を設定するだけで自動実行されます。
                            </p>

                            <div class="form-group">
                                <label class="form-label">Cron設定用コマンド（1時間に1回実行を推奨）</label>
                                <div style="position:relative;">
                                    <input type="text" class="form-input" readonly id="cronCommand"
                                        value="<?= htmlspecialchars('cd ' . realpath(__DIR__) . '; /usr/bin/php8.3 cron_dispatcher.php > /dev/null 2>&1') ?>"
                                        style="padding-right:80px; font-family:'SF Mono','Fira Code',monospace; font-size:var(--font-size-xs);">
                                    <button class="btn btn-secondary btn-sm" onclick="copyCronCommand()"
                                        style="position:absolute; right:4px; top:50%; transform:translateY(-50%);">
                                        📋 コピー
                                    </button>
                                </div>
                                <p class="form-help">XSERVER等のCron設定画面にそのまま貼り付けてください</p>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Crontabの設定例</label>
                                <div
                                    style="background:var(--color-bg); border:1px solid var(--color-border); border-radius:var(--radius-md); padding:var(--space-md); font-family:'SF Mono','Fira Code',monospace; font-size:var(--font-size-xs); color:var(--color-text-secondary); line-height:1.8; overflow-x:auto;">
                                    <div style="color:var(--color-text-muted);"># 毎時0分に実行（1時間に1回）← 推奨</div>
                                    <div>0 * * * *
                                        <?= htmlspecialchars('cd ' . realpath(__DIR__) . '; /usr/bin/php8.3 cron_dispatcher.php > /dev/null 2>&1') ?>
                                    </div>
                                    <br>
                                    <div style="color:var(--color-text-muted);"># 15分ごとに実行（より高頻度）</div>
                                    <div>*/15 * * * *
                                        <?= htmlspecialchars('cd ' . realpath(__DIR__) . '; /usr/bin/php8.3 cron_dispatcher.php > /dev/null 2>&1') ?>
                                    </div>
                                </div>
                            </div>

                            <div
                                style="background:var(--color-surface-alt); border-radius:var(--radius-md); padding:var(--space-md); margin-top:var(--space-md);">
                                <div class="flex items-center gap-md mb-sm">
                                    <span style="font-size:18px;">ℹ️</span>
                                    <strong class="text-sm">仕組み</strong>
                                </div>
                                <ul class="text-xs text-muted"
                                    style="list-style:disc; padding-left:var(--space-xl); line-height:2;">
                                    <li>サーバーが定期的に <code>cron_dispatcher.php</code> を実行します</li>
                                    <li>ツールがSQLiteをチェックし「今、投稿すべきデータ」があれば予約投稿を実行</li>
                                    <li>なければ何もせず終了します（負荷ゼロ）</li>
                                    <li>トークンの自動更新・インサイト取得も同時に行います</li>
                                </ul>
                            </div>

                            <?php if ($cron_last_run): ?>
                                <div class="flex items-center gap-md mt-md"
                                    style="padding:var(--space-md); background:var(--color-success-bg); border-radius:var(--radius-md);">
                                    <span style="font-size:18px;">✅</span>
                                    <div>
                                        <div class="text-sm font-bold" style="color:var(--color-success);">Cronは正常に動作しています
                                        </div>
                                        <div class="text-xs text-muted">最終実行: <?= htmlspecialchars($cron_last_run) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-md mt-md"
                                    style="padding:var(--space-md); background:var(--color-warning-bg); border-radius:var(--radius-md);">
                                    <span style="font-size:18px;">⚠️</span>
                                    <div>
                                        <div class="text-sm font-bold" style="color:var(--color-warning);">Cronがまだ設定されていません
                                        </div>
                                        <div class="text-xs text-muted">上記のコマンドをサーバーに登録してください</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- System Update -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="settings-card-icon update">🔄</div>
                            <div>
                                <h4>システム更新</h4>
                                <p class="text-xs text-muted">ワンクリックアップデート</p>
                            </div>
                        </div>
                        <div class="settings-card-body">
                            <p class="text-sm text-muted mb-md">現在のバージョン:
                                <strong><?= htmlspecialchars($version) ?></strong>
                            </p>
                            <div class="flex gap-sm">
                                <button class="btn btn-secondary" onclick="checkForUpdate()" id="checkUpdateBtn">🔍
                                    更新を確認</button>
                                <button class="btn btn-primary hidden" onclick="executeUpdate()"
                                    id="executeUpdateBtn">⬆️ アップデート実行</button>
                            </div>
                            <div id="updateStatus" class="mt-md"></div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- ===== Toast Container ===== -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ===== Loading Overlay ===== -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">処理中<span class="loading-dots"></span></div>
    </div>

    <!-- ===== Edit Post Modal ===== -->
    <div class="modal-overlay" id="editPostModal">
        <div class="modal">
            <div class="modal-header">
                <h3>投稿を編集</h3>
                <button class="modal-close" onclick="closeModal('editPostModal')">✕</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editPostId">
                <!-- 複数画像プレビューエリア -->
                <div id="editMediaGallery" class="post-media-gallery" style="display:none;"></div>
                <!-- 一枚目の画像の場合は従来のプレビュー -->
                <div id="editMediaPreview" class="post-media-preview">
                    <img src="" alt="Preview">
                    <button class="remove-media-btn" onclick="removePostMedia('edit', 0)">✕</button>
                </div>
                <input type="hidden" id="editMediaUrl" value="">
                <div class="form-group">
                    <label class="form-label">内容</label>
                    <textarea id="editPostContent" class="form-textarea" rows="6"></textarea>
                </div>
                <!-- トピックタグ（編集時） -->
                <div class="topic-tag-area" style="margin-bottom: var(--space-md);">
                    <div class="topic-tag-input-row">
                        <span class="topic-tag-hash">#</span>
                        <input type="text" id="editPostTopicTag" class="topic-tag-input"
                            placeholder="トピックタグ（任意）"
                            maxlength="50"
                            oninput="sanitizeTopicTagInput(this)">
                        <button class="topic-popular-btn" onclick="togglePopularTopics('edit')" id="editPopularBtn" title="人気トピックを見る">📊 人気</button>
                    </div>
                    <div id="editPopularTopics" class="popular-topics-panel" style="display:none;"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">ステータス</label>
                        <select id="editPostStatus" class="form-select">
                            <option value="draft">下書き</option>
                            <option value="scheduled">予約</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">予約日時</label>
                        <input type="datetime-local" id="editPostSchedule" class="form-input">
                    </div>
                </div>
                <div class="flex items-center gap-md mt-md">
                    <div class="toggle-wrap">
                        <label class="toggle">
                            <input type="checkbox" id="editPostAiLabel">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-sm">AIラベル</span>
                    </div>
                    <button class="btn-upload" id="editUploadBtn" onclick="triggerImageUpload('edit')" title="画像を追加（最大10枚）">
                        🖼️ <span style="font-size:var(--font-size-xs); margin-left:2px;">画像を追加</span>
                        <span id="editImageCount" class="image-count-badge" style="display:none;"></span>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal('editPostModal')">キャンセル</button>
                <button class="btn btn-primary" onclick="updatePost()">保存</button>
            </div>
        </div>
    </div>

    <!-- Hidden File Input (multiple) -->
    <input type="file" id="globalImageInput" style="display:none;" accept="image/jpeg,image/png,image/webp" multiple>

    <script src="assets/js/app.js"></script>
</body>

</html>