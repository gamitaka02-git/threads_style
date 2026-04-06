/**
 * ============================================================
 * ThreadsStyle - Main Application JavaScript
 * ============================================================
 */

// ===== Global State =====
const API_BASE = 'api/';

// ===== DOM Ready =====
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initTabs();
    initPostComposer();
    loadPostList();
    loadStyleGuide();
    loadKeywords();
    loadEngagementTable();
    initCharts();
    
    // アカウント情報の初期表示
    getThreadsProfile(true);

    // 保存されたセクションの復元
    const savedSection = localStorage.getItem('activeSection') || 'dashboard';
    switchSection(savedSection, false); // 第2引数は保存処理をスキップするため

    // 画像アップロードのイベントリスナー
    const imgInput = document.getElementById('globalImageInput');
    if (imgInput) {
        imgInput.addEventListener('change', handleFileSelect);
    }
});

let activeUploadContext = 'create'; // 'create' or 'edit'

// ===== Multi-Image State =====
const mediaState = {
    create: [], // [{url: '...'}]
    edit: []    // [{url: '...'}]
};
const MAX_IMAGES = 10;

function triggerImageUpload(context) {
    const current = mediaState[context] || [];
    if (current.length >= MAX_IMAGES) {
        showToast(`画像は最大${MAX_IMAGES}枚まで追加できます`, 'warning');
        return;
    }
    activeUploadContext = context;
    const input = document.getElementById('globalImageInput');
    input.value = ''; // リセット
    input.click();
}

async function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    const context = activeUploadContext;
    const current = mediaState[context] || [];
    const remaining = MAX_IMAGES - current.length;
    const filesToUpload = files.slice(0, remaining);

    if (files.length > remaining) {
        showToast(`${files.length}枚選択されましたが、残り${remaining}枚分のみアップロードします`, 'warning');
    }

    showLoading(`画像をアップロード中 (0/${filesToUpload.length})`);

    let successCount = 0;
    for (let i = 0; i < filesToUpload.length; i++) {
        const file = filesToUpload[i];
        const formData = new FormData();
        formData.append('image', file);

        const loadingText = document.getElementById('loadingText');
        if (loadingText) loadingText.innerHTML = `画像をアップロード中 (${i + 1}/${filesToUpload.length})<span class="loading-dots"></span>`;

        try {
            const response = await fetch('api/upload.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                mediaState[context].push({ url: result.url });
                successCount++;
            } else {
                showToast(`${file.name}: ${result.message || 'アップロード失敗'}`, 'error');
            }
        } catch (err) {
            showToast(`${file.name}: 通信エラー`, 'error');
        }
    }

    hideLoading();
    renderMediaGallery(context);

    if (successCount > 0) {
        showToast(`${successCount}枚の画像をアップロードしました`, 'success');
    }
    e.target.value = '';
}

/**
 * 画像ギャラリー UI を再描画する
 */
function renderMediaGallery(context) {
    const prefix = context === 'edit' ? 'edit' : 'post';
    const urls = mediaState[context] || [];
    const count = urls.length;

    const galleryEl = document.getElementById(prefix + 'MediaGallery');
    const legacyPreview = document.getElementById(prefix + 'MediaPreview');
    const urlInput = document.getElementById(prefix + 'MediaUrl');
    const uploadBtn = document.getElementById(context + 'UploadBtn');
    const countBadge = document.getElementById(context + 'ImageCount');

    if (count === 0) {
        // 画像なし
        if (galleryEl) { galleryEl.innerHTML = ''; galleryEl.style.display = 'none'; }
        if (legacyPreview) legacyPreview.classList.remove('active');
        if (urlInput) urlInput.value = '';
        if (uploadBtn) uploadBtn.classList.remove('has-media');
        if (countBadge) countBadge.style.display = 'none';
    } else {
        // 1枚以上: すべてギャラリー表示（サムネイル統一）
        if (legacyPreview) legacyPreview.classList.remove('active');
        if (urlInput) urlInput.value = count === 1 ? urls[0].url : JSON.stringify(urls.map(u => u.url));
        if (uploadBtn) uploadBtn.classList.add('has-media');
        if (countBadge) {
            if (count >= 2) { countBadge.textContent = count; countBadge.style.display = 'inline-flex'; }
            else { countBadge.style.display = 'none'; }
        }

        if (galleryEl) {
            galleryEl.style.display = 'flex';
            galleryEl.innerHTML = urls.map((u, i) => `
                <div class="media-thumb" style="position:relative; flex-shrink:0;">
                    <img src="${u.url}" alt="画像${i + 1}" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid var(--color-border);">
                    <button onclick="removePostMedia('${context}', ${i})" title="削除" style="position:absolute; top:-6px; right:-6px; background:#ff4757; border:none; color:#fff; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:11px; line-height:1; display:flex; align-items:center; justify-content:center;">✕</button>
                    ${count >= 2 && i === 0 ? '<div style="position:absolute; bottom:2px; left:2px; background:rgba(0,0,0,0.6); color:#fff; font-size:9px; padding:1px 4px; border-radius:3px;">表紙</div>' : ''}
                </div>
            `).join('');
        }
    }
}

function removePostMedia(context, index = 0) {
    const urls = mediaState[context] || [];
    urls.splice(index, 1);
    mediaState[context] = urls;
    renderMediaGallery(context);
}

/**
 * 既存の media_url 値（文字列 or JSON）を mediaState に読み込む
 */
function loadMediaState(context, mediaUrlValue) {
    mediaState[context] = [];
    if (!mediaUrlValue) return;
    try {
        const parsed = JSON.parse(mediaUrlValue);
        if (Array.isArray(parsed)) {
            mediaState[context] = parsed.map(u => ({ url: u }));
            return;
        }
    } catch(e) {}
    // 単一URL
    if (mediaUrlValue.startsWith('http')) {
        mediaState[context] = [{ url: mediaUrlValue }];
    }
}

// ===== Sidebar Navigation =====
function initSidebar() {
    const navLinks = document.querySelectorAll('.sidebar-nav a[data-section]');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.dataset.section;
            switchSection(section);
        });
    });

    // Mobile toggle
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && e.target !== toggle) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }
}

function switchSection(sectionName, save = true) {
    if (save) localStorage.setItem('activeSection', sectionName);

    // Hide all sections
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    // Show target section
    const target = document.getElementById('section-' + sectionName);
    if (target) target.classList.add('active');

    // Update nav active state
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    const activeLink = document.querySelector(`.sidebar-nav a[data-section="${sectionName}"]`);
    if (activeLink) activeLink.classList.add('active');

    // 復元されたタブがあれば適用
    const savedTab = localStorage.getItem('activeTab_' + sectionName);
    if (savedTab) {
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        if (tabBtn) tabBtn.click();
    }

    // Close mobile sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.remove('open');
}

// ===== Tabs =====
function initTabs() {
    document.querySelectorAll('.tab-btn[data-tab]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            const tabContainer = btn.closest('.section') || btn.parentElement.parentElement;

            // Deactivate all tabs in this container
            btn.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            tabContainer.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

            // Activate clicked tab
            btn.classList.add('active');
            const panel = document.getElementById(tabId);
            if (panel) panel.classList.add('active');

            // タブの状態を保存 (セクションごとに個別に保存)
            if (tabContainer.id) {
                const sectionId = tabContainer.id.replace('section-', '');
                localStorage.setItem('activeTab_' + sectionId, tabId);
            }
        });
    });
}

// ===== Post Composer =====
function initPostComposer() {
    const textarea = document.getElementById('postContent');
    const charCount = document.getElementById('charCount');

    if (textarea && charCount) {
        textarea.addEventListener('input', () => {
            const len = textarea.value.length;
            charCount.textContent = len;
            const countEl = textarea.closest('.post-composer').querySelector('.post-char-count');
            if (countEl) {
                countEl.classList.toggle('over-limit', len > 500);
            }
        });
    }
}

// ===== Toast Notifications =====
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(toast);

    // Auto-remove after 5s
    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// ===== Loading Overlay =====
function showLoading(text = '処理中') {
    const overlay = document.getElementById('loadingOverlay');
    const textEl = document.getElementById('loadingText');
    if (overlay) overlay.classList.add('active');
    if (textEl) textEl.innerHTML = text + '<span class="loading-dots"></span>';
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('active');
}

// ===== Modal Management =====
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// ===== API Helper =====
async function apiCall(endpoint, data = {}, method = 'POST') {
    try {
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        };

        if (method === 'POST') {
            options.body = new URLSearchParams(data).toString();
        } else if (method === 'GET') {
            const params = new URLSearchParams(data).toString();
            if (params) endpoint += '?' + params;
        }

        const response = await fetch(API_BASE + endpoint, options);
        const result = await response.json();
        return result;
    } catch (err) {
        console.error('API Error:', err);
        return { success: false, message: 'サーバーとの通信に失敗しました。' };
    }
}

// ===== Posts: CRUD =====
async function savePostDraft() {
    const content = document.getElementById('postContent').value.trim();
    if (!content) {
        showToast('投稿内容を入力してください', 'warning');
        return;
    }

    const urls = mediaState['create'].map(u => u.url);
    const params = {
        action: 'create',
        content: content,
        status: 'draft',
        ai_label: document.getElementById('aiLabelToggle').checked ? 1 : 0,
        topic_tag: (document.getElementById('postTopicTag')?.value || '').trim()
    };
    if (urls.length > 0) params.media_urls = JSON.stringify(urls);

    const result = await apiCall('posts.php', params);

    if (result.success) {
        showToast('下書きを保存しました', 'success');
        clearComposer();
        loadPostList();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

async function schedulePost() {
    const content = document.getElementById('postContent').value.trim();
    const scheduledAt = document.getElementById('scheduleAt').value;

    if (!content) {
        showToast('投稿内容を入力してください', 'warning');
        return;
    }
    if (!scheduledAt) {
        showToast('予約日時を設定してください', 'warning');
        return;
    }

    const urls = mediaState['create'].map(u => u.url);
    const params = {
        action: 'create',
        content: content,
        status: 'scheduled',
        scheduled_at: scheduledAt,
        ai_label: document.getElementById('aiLabelToggle').checked ? 1 : 0,
        topic_tag: (document.getElementById('postTopicTag')?.value || '').trim()
    };
    if (urls.length > 0) params.media_urls = JSON.stringify(urls);

    const result = await apiCall('posts.php', params);

    if (result.success) {
        showToast('予約投稿を設定しました', 'success');
        clearComposer();
        loadPostList();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

async function publishPostNow() {
    const content = document.getElementById('postContent').value.trim();
    if (!content) {
        showToast('投稿内容を入力してください', 'warning');
        return;
    }

    const urls = mediaState['create'].map(u => u.url);
    const carouselWarn = urls.length >= 2 ? `\n\n🖼️ ${urls.length}枚の画像をカルーセル投稿します` : '';
    const topicTag = (document.getElementById('postTopicTag')?.value || '').trim();
    const topicWarn = topicTag ? `\n🏷️ トピック: #${topicTag}` : '';
    if (!confirm(`この投稿を今すぐThreadsに公開しますか？${carouselWarn}${topicWarn}`)) return;

    const params = {
        action: 'publish_now',
        content: content,
        ai_label: document.getElementById('aiLabelToggle').checked ? 1 : 0,
        topic_tag: topicTag
    };
    if (urls.length > 0) params.media_urls = JSON.stringify(urls);

    showLoading(urls.length >= 2 ? 'カルーセル投稿中（数秒かかる場合があります）' : '投稿中');
    const result = await apiCall('posts.php', params);
    hideLoading();

    if (result.success) {
        showToast(result.message || '投稿しました！', 'success');
        clearComposer();
        loadPostList();
    } else {
        showToast(result.message || '投稿に失敗しました', 'error');
    }
}

// コンポーザーリセット
function clearComposer() {
    const el = document.getElementById('postContent');
    if (el) el.value = '';
    const cc = document.getElementById('charCount');
    if (cc) cc.textContent = '0';
    const sa = document.getElementById('scheduleAt');
    if (sa) sa.value = '';
    const tt = document.getElementById('postTopicTag');
    if (tt) tt.value = '';
    // 人気トピックパネルを閉じる
    const panel = document.getElementById('createPopularTopics');
    if (panel) panel.style.display = 'none';
    mediaState['create'] = [];
    renderMediaGallery('create');
}

async function loadPostList(filter = null) {
    if (!filter) {
        filter = document.querySelector('[data-filter].active')?.dataset.filter || 'all';
    }
    const container = document.getElementById('postListContainer');
    if (!container) return;

    const result = await apiCall('posts.php', { action: 'list', filter: filter }, 'POST');

    if (result.success && result.posts && result.posts.length > 0) {
        let html = '<table class="data-table"><thead><tr>';
        html += '<th>内容</th><th>ステータス</th><th>予約日時</th><th>作成日</th><th>操作</th>';
        html += '</tr></thead><tbody>';

        result.posts.forEach(post => {
            const badgeMap = {
                draft: '<span class="badge badge-draft">下書き</span>',
                scheduled: '<span class="badge badge-info">予約中</span>',
                posted: '<span class="badge badge-success">投稿済</span>',
                failed: '<span class="badge badge-error">失敗</span>'
            };
            const badge = badgeMap[post.status] || badgeMap.draft;
            const aiTag = post.ai_label == 1 ? ' <span class="badge badge-warning">AI</span>' : '';
            const contentStr = post.content || '';
            // 複数画像判定
            let hasMedia = '';
            if (post.media_url) {
                try {
                    const parsed = JSON.parse(post.media_url);
                    if (Array.isArray(parsed) && parsed.length >= 2) {
                        hasMedia = ` <span class="carousel-indicator" title="カルーセル投稿">🖼️ ${parsed.length}枚</span>`;
                    } else {
                        hasMedia = ' <span class="media-indicator" title="画像あり">🖼️ 画像あり</span>';
                    }
                } catch(e) {
                    hasMedia = ' <span class="media-indicator" title="画像あり">🖼️ 画像あり</span>';
                }
            }
            const preview = escapeHtml(contentStr).substring(0, 60) + (contentStr.length > 60 ? '...' : '');

            html += `<tr>
                <td class="post-preview">${preview}${hasMedia}</td>
                <td>${badge}${aiTag}</td>
                <td style="white-space:nowrap; color:var(--color-text-secondary); font-size:var(--font-size-xs);">${post.status === 'scheduled' ? (post.scheduled_at || '-') : '-'}</td>
                <td style="white-space:nowrap; color:var(--color-text-secondary); font-size:var(--font-size-xs);">${post.created_at}</td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-ghost btn-sm" onclick="editPost(${post.id})">編集</button>
                    <button class="btn btn-ghost btn-sm" onclick="deletePost(${post.id})" style="color:var(--color-error);">削除</button>
                </td>
            </tr>`;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } else {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">✏️</div><p>投稿がありません</p></div>';
    }

    // Update filter button active states
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
        btn.onclick = () => loadPostList(btn.dataset.filter);
    });
}

async function editPost(postId) {
    const result = await apiCall('posts.php', { action: 'get', id: postId });
    if (result.success && result.post) {
        const post = result.post;
        document.getElementById('editPostId').value = post.id;
        document.getElementById('editPostContent').value = post.content;
        document.getElementById('editPostStatus').value = post.status === 'posted' ? 'draft' : post.status;
        document.getElementById('editPostSchedule').value = post.scheduled_at ? post.scheduled_at.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('editPostAiLabel').checked = post.ai_label == 1;
        // topic_tagを読み込む
        const topicEl = document.getElementById('editPostTopicTag');
        if (topicEl) topicEl.value = post.topic_tag || '';
        // 画像変数を初期化して読み込み
        loadMediaState('edit', post.media_url || '');
        renderMediaGallery('edit');
        openModal('editPostModal');
    } else {
        showToast('投稿データの取得に失敗しました', 'error');
    }
}

async function updatePost() {
    const id = document.getElementById('editPostId').value;
    const urls = mediaState['edit'].map(u => u.url);
    const params = {
        action: 'update',
        id: id,
        content: document.getElementById('editPostContent').value,
        status: document.getElementById('editPostStatus').value,
        scheduled_at: document.getElementById('editPostSchedule').value,
        ai_label: document.getElementById('editPostAiLabel').checked ? 1 : 0,
        topic_tag: (document.getElementById('editPostTopicTag')?.value || '').trim()
    };
    if (urls.length > 0) {
        params.media_urls = JSON.stringify(urls);
    } else {
        params.media_url = ''; // 画像を削除
    }

    const result = await apiCall('posts.php', params);

    if (result.success) {
        showToast('投稿を更新しました', 'success');
        closeModal('editPostModal');
        loadPostList();
    } else {
        showToast(result.message || '更新に失敗しました', 'error');
    }
}

// ===== Topic Tag Helpers =====

/**
 * トピックタグ入力欄のサニタイズ（入力中リアルタイム）
 */
function sanitizeTopicTagInput(input) {
    let v = input.value;
    v = v.replace(/^#+/, '');          // 先頭 # を除去
    v = v.replace(/[.&]/g, '');        // . と & を除去
    input.value = v;
}

/**
 * 人気トピックパネルの開閉
 */
async function togglePopularTopics(context) {
    const panelId = context === 'edit' ? 'editPopularTopics'
                  : context === 'thread' ? 'threadPopularTopics'
                  : 'createPopularTopics';
    const panel = document.getElementById(panelId);
    if (!panel) return;

    if (panel.style.display !== 'none') {
        panel.style.display = 'none';
        return;
    }

    if (panel.dataset.loaded !== '1') {
        await loadPopularTopics(panelId, context);
    }
    panel.style.display = 'grid';
}

/**
 * 人気トピックを読み込んでパネルに表示
 */
async function loadPopularTopics(panelId, context) {
    const panel = document.getElementById(panelId);
    if (!panel) return;

    panel.innerHTML = '<span style="color:var(--color-text-muted); font-size:var(--font-size-xs)">読み込み中...</span>';

    const result = await apiCall('posts.php', { action: 'popular_topics' });
    const dbTags = (result.success && result.tags) ? result.tags : [];

    if (dbTags.length === 0) {
        panel.innerHTML = '<span style="color:var(--color-text-muted); font-size:var(--font-size-xs)">まだトピックの履歴がありません</span>';
        panel.dataset.loaded = '1';
        return;
    }

    panel.innerHTML = dbTags.map(t => `
        <button class="topic-chip"
            onclick="selectTopicTag('${escapeHtml(t.topic_tag)}', '${context}')"
            title="${escapeHtml(t.topic_tag)} (${t.cnt}回使用)">
            #${escapeHtml(t.topic_tag)}
            <span class="topic-chip-count">${t.cnt}</span>
        </button>
    `).join('');
    panel.dataset.loaded = '1';
}

/**
 * チップをクリックしてそのタグを入力欄にセット
 */
function selectTopicTag(tag, context) {
    const inputId = context === 'edit' ? 'editPostTopicTag'
                  : context === 'thread' ? 'threadTopicTag'
                  : 'postTopicTag';
    const input = document.getElementById(inputId);
    if (input) {
        input.value = tag;
        input.focus();
    }
    // パネルを閉じる
    const panelId = context === 'edit' ? 'editPopularTopics'
                  : context === 'thread' ? 'threadPopularTopics'
                  : 'createPopularTopics';
    const panel = document.getElementById(panelId);
    if (panel) panel.style.display = 'none';
}

async function deletePost(postId) {
    if (!confirm('この投稿を削除しますか？')) return;

    const result = await apiCall('posts.php', { action: 'delete', id: postId });
    if (result.success) {
        showToast('投稿を削除しました', 'success');
        loadPostList();
    } else {
        showToast(result.message || '削除に失敗しました', 'error');
    }
}

// ===== Thread Creation =====
function addThreadItem() {
    const container = document.getElementById('threadPosts');
    const count = container.querySelectorAll('.thread-item').length + 1;
    const item = document.createElement('div');
    item.className = 'thread-item';
    item.innerHTML = `
        <textarea class="form-textarea thread-textarea" placeholder="スレッド ${count}つ目の投稿..." rows="3"></textarea>
        <button class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()" style="position:absolute; top:4px; right:0; color:var(--color-error);">✕</button>
    `;
    container.appendChild(item);
}

async function saveThreadDraft() {
    const textareas = document.querySelectorAll('.thread-textarea');
    const posts = [];
    textareas.forEach(ta => {
        const val = ta.value.trim();
        if (val) posts.push(val);
    });

    if (posts.length < 2) {
        showToast('スレッドには2つ以上の投稿が必要です', 'warning');
        return;
    }

    const result = await apiCall('posts.php', {
        action: 'create_thread',
        posts: JSON.stringify(posts),
        status: 'draft',
        topic_tag: (document.getElementById('threadTopicTag')?.value || '').trim()
    });

    if (result.success) {
        showToast('スレッドの下書きを保存しました', 'success');
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

async function scheduleThread() {
    const textareas = document.querySelectorAll('.thread-textarea');
    const scheduledAt = document.getElementById('threadScheduleAt').value;
    const posts = [];
    textareas.forEach(ta => {
        const val = ta.value.trim();
        if (val) posts.push(val);
    });

    if (posts.length < 2) {
        showToast('スレッドには2つ以上の投稿が必要です', 'warning');
        return;
    }
    if (!scheduledAt) {
        showToast('予約日時を設定してください', 'warning');
        return;
    }

    const result = await apiCall('posts.php', {
        action: 'create_thread',
        posts: JSON.stringify(posts),
        status: 'scheduled',
        scheduled_at: scheduledAt,
        topic_tag: (document.getElementById('threadTopicTag')?.value || '').trim()
    });

    if (result.success) {
        showToast('スレッドの予約を設定しました', 'success');
        loadPostList();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

// ===== Repurpose =====
async function repurposeContent() {
    const url = document.getElementById('repurposeUrl').value.trim();
    if (!url) {
        showToast('URLを入力してください', 'warning');
        return;
    }

    const btn = document.getElementById('repurposeBtn');
    btn.disabled = true;
    btn.textContent = '変換中...';

    const result = await apiCall('gemini_api.php', {
        action: 'repurpose',
        url: url
    });

    btn.disabled = false;
    btn.textContent = '🔄 Threads形式に変換';

    if (result.success && result.suggestions) {
        const container = document.getElementById('repurposeSuggestions');
        container.innerHTML = '';
        result.suggestions.forEach((s, i) => {
            container.innerHTML += `
                <div class="ai-suggestion-card">
                    <div class="ai-suggestion-type">案 ${i + 1}</div>
                    <div class="ai-suggestion-content">${escapeHtml(s)}</div>
                    <button class="btn btn-secondary btn-sm" onclick="useAsSuggestion(this)">📋 投稿に使う</button>
                </div>
            `;
        });
        document.getElementById('repurposeResult').classList.remove('hidden');
    } else {
        showToast(result.message || '変換に失敗しました', 'error');
    }
}

function useAsSuggestion(btn) {
    const content = btn.closest('.ai-suggestion-card').querySelector('.ai-suggestion-content').textContent;
    document.getElementById('postContent').value = content;
    document.getElementById('charCount').textContent = content.length;
    switchSection('posts');
    // Switch to create tab
    document.querySelector('[data-tab="tab-post-create"]').click();
    showToast('投稿フォームにコピーしました', 'success');
}

function copySuggestion(btn) {
    const content = btn.closest('.ai-suggestion-card').querySelector('.ai-suggestion-content').textContent;
    copyToClipboard(content);
}

// ===== AI Analysis =====
async function startAnalysis() {
    document.getElementById('startAnalysisBtn').classList.add('hidden');
    document.getElementById('analysisAnimation').classList.remove('hidden');

    // Step 1: Fetch posts
    updateAnalysisStep('step-fetch', 'active');
    await delay(800);

    const result = await apiCall('gemini_api.php', { action: 'analyze' });

    if (!result.success) {
        document.getElementById('analysisAnimation').classList.add('hidden');
        document.getElementById('startAnalysisBtn').classList.remove('hidden');
        showToast(result.message || '分析に失敗しました', 'error');
        return;
    }

    // Animate steps
    updateAnalysisStep('step-fetch', 'done');
    updateAnalysisStep('step-analyze', 'active');
    await delay(1200);
    updateAnalysisStep('step-analyze', 'done');
    updateAnalysisStep('step-generate', 'active');
    await delay(1000);
    updateAnalysisStep('step-generate', 'done');
    updateAnalysisStep('step-save', 'active');
    await delay(600);
    updateAnalysisStep('step-save', 'done');

    // Show result
    await delay(500);
    document.getElementById('analysisAnimation').classList.add('hidden');
    document.getElementById('analysisResult').classList.remove('hidden');
    document.getElementById('analysisResultContent').textContent = result.style_guide || '分析結果を取得できませんでした。';

    // 再分析できるようにボタンを復活させる
    const startBtn = document.getElementById('startAnalysisBtn');
    if (startBtn) {
        startBtn.innerHTML = '🔄 再度、自己解析を実行';
        startBtn.classList.remove('hidden');
    }

    showToast('自己解析が完了しました！', 'success');
    loadStyleGuide();
}

function updateAnalysisStep(stepId, state) {
    const step = document.getElementById(stepId);
    if (!step) return;
    step.className = 'analysis-step ' + state;
    const icon = step.querySelector('span');
    if (state === 'active') icon.textContent = '⏳';
    if (state === 'done') icon.textContent = '✅';
}

// ===== Style Guide =====
async function loadStyleGuide() {
    const result = await apiCall('style_guide.php', { action: 'get' }, 'POST');
    if (result.success && result.content) {
        document.getElementById('styleGuideEditor').value = result.content;
        updateStyleGuidePreview();

        // 分析結果（AIトーン学習）タブにも同期表示して再分析可能にする
        const resultDiv = document.getElementById('analysisResult');
        const contentDiv = document.getElementById('analysisResultContent');
        const startBtn = document.getElementById('startAnalysisBtn');
        
        if (resultDiv && contentDiv && result.content.trim() !== '') {
            contentDiv.textContent = result.content;
            resultDiv.classList.remove('hidden');
            
            if (startBtn) {
                startBtn.innerHTML = '🔄 再度、自己解析を実行';
                startBtn.classList.remove('hidden');
            }
        }
    }
}

function updateStyleGuidePreview() {
    const editor = document.getElementById('styleGuideEditor');
    const preview = document.getElementById('styleGuidePreview');
    if (editor && preview) {
        // Simple markdown-like rendering
        let text = escapeHtml(editor.value);
        text = text.replace(/^# (.+)$/gm, '<h2>$1</h2>');
        text = text.replace(/^## (.+)$/gm, '<h3>$1</h3>');
        text = text.replace(/^### (.+)$/gm, '<h4>$1</h4>');
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/^- (.+)$/gm, '• $1');
        text = text.replace(/\n/g, '<br>');
        preview.innerHTML = text || '<p class="text-muted">プレビューがここに表示されます</p>';
    }
}

// Add live preview
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('styleGuideEditor');
    if (editor) {
        editor.addEventListener('input', updateStyleGuidePreview);
    }
});

async function saveStyleGuide() {
    const content = document.getElementById('styleGuideEditor').value;
    const result = await apiCall('style_guide.php', {
        action: 'save',
        content: content
    });

    if (result.success) {
        showToast('スタイルガイドを保存しました', 'success');
    } else {
        showToast(result.message || '保存に失敗しました', 'error');
    }
}

// ===== AI Post Generation =====
async function generatePosts() {
    const topic = document.getElementById('generateTopic').value.trim();
    if (!topic) {
        showToast('トピックを入力してください', 'warning');
        return;
    }

    const btn = document.getElementById('generateBtn');
    btn.disabled = true;
    btn.textContent = '生成中...';

    const result = await apiCall('gemini_api.php', {
        action: 'generate',
        topic: topic,
        instructions: document.getElementById('generateInstructions').value
    });

    btn.disabled = false;
    btn.textContent = '✨ 3案を生成';

    if (result.success && result.suggestions) {
        const types = ['教育', '独り言', '交流'];
        const container = document.getElementById('generateSuggestions');
        container.innerHTML = '';

        result.suggestions.forEach((s, i) => {
            container.innerHTML += `
                <div class="ai-suggestion-card">
                    <div class="ai-suggestion-type">${types[i] || '案 ' + (i + 1)}</div>
                    <div class="ai-suggestion-content">${escapeHtml(s)}</div>
                    <div class="flex gap-sm mt-md">
                        <button class="btn btn-secondary btn-sm" onclick="useAsSuggestion(this)">📋 投稿に使う</button>
                        <button class="btn btn-ghost btn-sm" onclick="copySuggestion(this)">コピー</button>
                    </div>
                </div>
            `;
        });
        document.getElementById('generateResults').classList.remove('hidden');
    } else {
        showToast(result.message || '生成に失敗しました', 'error');
    }
}

// ===== Analytics / Insights =====
async function fetchInsights() {
    showLoading('インサイトデータを取得中');
    const result = await apiCall('insights.php', { action: 'fetch' });
    hideLoading();

    if (result.success) {
        showToast('インサイトデータを更新しました', 'success');
        loadEngagementTable();
        initCharts();
    } else {
        showToast(result.message || 'データ取得に失敗しました', 'error');
    }
}

let currentInsightsData = [];
let currentInsightSort = { key: 'posted_at', dir: 'desc' };

async function loadEngagementTable() {
    const container = document.getElementById('engagementTable');
    if (!container) return;

    const result = await apiCall('insights.php', { action: 'list' });
    if (result.success && result.insights && result.insights.length > 0) {
        currentInsightsData = result.insights;
        renderEngagementTable();
    }
}

function sortInsights(key) {
    if (currentInsightSort.key === key) {
        currentInsightSort.dir = currentInsightSort.dir === 'asc' ? 'desc' : 'asc';
    } else {
        currentInsightSort.key = key;
        // 数字項目（閲覧数、いいね等）はデフォルト降順がおすすめ
        currentInsightSort.dir = key === 'content' ? 'asc' : 'desc';
    }
    renderEngagementTable();
}

function renderEngagementTable() {
    const container = document.getElementById('engagementTable');
    if (!container || !currentInsightsData.length) return;

    // ソート処理
    currentInsightsData.sort((a, b) => {
        let valA = a[currentInsightSort.key];
        let valB = b[currentInsightSort.key];
        
        if (!isNaN(valA) && !isNaN(valB)) {
            valA = Number(valA);
            valB = Number(valB);
        }

        if (valA < valB) return currentInsightSort.dir === 'asc' ? -1 : 1;
        if (valA > valB) return currentInsightSort.dir === 'asc' ? 1 : -1;
        return 0;
    });

    const getSortIcon = (key) => {
        if (currentInsightSort.key !== key) return '<span style="opacity:0.3">↕</span>';
        return currentInsightSort.dir === 'asc' ? '⬆' : '⬇';
    };

    let html = '<div style="overflow-x:auto;"><table class="data-table" style="min-width:600px;"><thead><tr>';
    html += `<th onclick="sortInsights('content')" style="cursor:pointer; user-select:none;">投稿 ${getSortIcon('content')}</th>`;
    html += `<th onclick="sortInsights('likes')" style="cursor:pointer; user-select:none; white-space:nowrap;">👍 いいね ${getSortIcon('likes')}</th>`;
    html += `<th onclick="sortInsights('replies')" style="cursor:pointer; user-select:none; white-space:nowrap;">💬 返信 ${getSortIcon('replies')}</th>`;
    html += `<th onclick="sortInsights('reposts')" style="cursor:pointer; user-select:none; white-space:nowrap;">🔄 リポスト ${getSortIcon('reposts')}</th>`;
    html += `<th onclick="sortInsights('views')" style="cursor:pointer; user-select:none; white-space:nowrap;">👁 閲覧 ${getSortIcon('views')}</th>`;
    html += `<th onclick="sortInsights('posted_at')" style="cursor:pointer; user-select:none; white-space:nowrap;">投稿日 ${getSortIcon('posted_at')}</th>`;
    html += '</tr></thead><tbody>';

    currentInsightsData.forEach(ins => {
        const preview = escapeHtml(ins.content || '').substring(0, 40) + '...';
        html += `<tr>
            <td class="post-preview" style="max-width:300px;">${preview}</td>
            <td>${ins.likes}</td>
            <td>${ins.replies}</td>
            <td>${ins.reposts}</td>
            <td>${ins.views}</td>
            <td style="font-size:var(--font-size-xs); color:var(--color-text-secondary); white-space:nowrap;">${ins.posted_at || ins.fetched_at}</td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    
    container.innerHTML = html;
}

function downloadInsightsCSV() {
    if (!currentInsightsData || currentInsightsData.length === 0) {
        showToast('ダウンロードするデータがありません', 'warning');
        return;
    }

    // CSVヘッダー
    let csvContent = "投稿内容,いいね,返信,リポスト,閲覧数,投稿日時\n";

    // データ行
    currentInsightsData.forEach(ins => {
        // Excel等で崩れないように内容をエスケープして囲む
        let content = (ins.content || '').replace(/"/g, '""');
        csvContent += `"${content}",${ins.likes},${ins.replies},${ins.reposts},${ins.views},"${ins.posted_at || ins.fetched_at}"\n`;
    });

    // BOMを追加してExcelで文字化けしないようにする（UTF-8 BOM）
    const bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
    const blob = new Blob([bom, csvContent], { type: 'text/csv;charset=utf-8;' });
    
    // ダウンロードトリガー
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    const dateStr = new Date().toISOString().slice(0, 10);
    
    link.setAttribute("href", url);
    link.setAttribute("download", `threads_insights_${dateStr}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

async function detectTopPosts() {
    showLoading('優秀投稿を検出中');
    const result = await apiCall('insights.php', { action: 'detect_top' });
    hideLoading();

    if (result.success) {
        showToast(`${result.count || 0}件の優秀投稿を検出しました`, 'success');
        loadTopPosts();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

async function loadTopPosts() {
    const container = document.getElementById('topPostsList');
    if (!container) return;

    const result = await apiCall('insights.php', { action: 'top_list' });
    if (result.success && result.top_posts && result.top_posts.length > 0) {
        let html = '';
        result.top_posts.forEach(tp => {
            html += `
                <div class="card" style="margin-bottom:var(--space-md);">
                    <div class="flex justify-between items-center">
                        <span class="badge badge-warning">🏆 スコア: ${tp.engagement_score}</span>
                        <button class="btn btn-secondary btn-sm" onclick="recyclePost(${tp.post_id})">♻️ リサイクル</button>
                    </div>
                    <p class="text-sm mt-md" style="line-height:1.7;">${escapeHtml(tp.content)}</p>
                    <p class="text-xs text-muted mt-md">${tp.reason}</p>
                </div>
            `;
        });
        container.innerHTML = html;
    }
}

async function recyclePost(postId) {
    const result = await apiCall('posts.php', { action: 'recycle', id: postId });
    if (result.success) {
        showToast('投稿をリサイクルしました（下書きとして複製）', 'success');
        loadPostList();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

// ===== Keywords =====
async function addKeyword() {
    const input = document.getElementById('newKeyword');
    const keyword = input.value.trim();
    if (!keyword) {
        showToast('キーワードを入力してください', 'warning');
        return;
    }

    const result = await apiCall('insights.php', {
        action: 'add_keyword',
        keyword: keyword
    });

    if (result.success) {
        showToast('キーワードを追加しました', 'success');
        input.value = '';
        loadKeywords();
    } else {
        showToast(result.message || 'エラーが発生しました', 'error');
    }
}

async function loadKeywords() {
    const container = document.getElementById('keywordList');
    if (!container) return;

    const result = await apiCall('insights.php', { action: 'list_keywords' });
    if (result.success && result.keywords && result.keywords.length > 0) {
        let html = '';
        result.keywords.forEach(kw => {
            html += `
                <div class="flex items-center gap-md mb-sm" style="padding:var(--space-sm) var(--space-md); background:var(--color-surface-alt); border-radius:var(--radius-md);">
                    <span class="badge badge-info">${escapeHtml(kw.keyword)}</span>
                    <span class="text-xs text-muted" style="margin-left:auto;">${kw.is_active == 1 ? '監視中' : '停止中'}</span>
                    <button class="btn btn-ghost btn-sm" onclick="removeKeyword(${kw.id})" style="color:var(--color-error);">✕</button>
                </div>
            `;
        });
        container.innerHTML = html;
    }
}

async function removeKeyword(id) {
    const result = await apiCall('insights.php', {
        action: 'remove_keyword',
        id: id
    });
    if (result.success) {
        showToast('キーワードを削除しました', 'success');
        loadKeywords();
    }
}

// ===== Settings =====

async function getThreadsProfile(isInitial = false) {
    const profileContainer = document.getElementById('threadsProfileSidebar');
    if (!profileContainer) return;

    if (!isInitial) {
        showLoading('アカウント情報を取得中');
    }

    try {
        const result = await apiCall('settings.php', { action: 'get_threads_profile' });
        if (!isInitial) hideLoading();

        if (result.success && result.profile) {
            const p = result.profile;
            profileContainer.innerHTML = `
                <a href="https://www.threads.net/@${encodeURIComponent(p.username)}" target="_blank" class="sidebar-account mt-md" title="Threads プロフィールを開く">
                    <img src="${p.threads_profile_picture_url || 'assets/img/default-avatar.png'}" class="sidebar-avatar" alt="Avatar">
                    <div class="account-info">
                        <div class="account-name">@${escapeHtml(p.username)}</div>
                        <div class="account-stats">
                            <span class="text-xs text-muted">${p.follower_count.toLocaleString()} follower</span>
                        </div>
                    </div>
                </a>
            `;
            if (!isInitial) showToast('アカウント情報を同期しました', 'success');
        } else if (!isInitial) {
            profileContainer.innerHTML = ``;
        }
    } catch (err) {
        if (!isInitial) hideLoading();
        console.error('Profile fetch error:', err);
    }
}

async function syncPastPosts() {
    if (!confirm('Threads アカウントの直近の投稿データを取得してツール内へ同期します。よろしいですか？')) {
        return;
    }

    showLoading('過去投稿を同期中');
    try {
        const result = await apiCall('settings.php', { action: 'sync_posts' });
        hideLoading();

        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || '同期に失敗しました', 'error');
        }
    } catch (err) {
        hideLoading();
        console.error('Post sync error:', err);
        showToast('通信エラーが発生しました', 'error');
    }
}

async function saveThreadsSettings() {
    const params = {
        action: 'save',
        threads_access_token: document.getElementById('settingsThreadsToken').value,
        threads_app_id: document.getElementById('settingsThreadsAppId').value,
        threads_user_id: document.getElementById('settingsThreadsUserId').value,
        threads_token_expires_at: document.getElementById('settingsTokenExpires').value
    };

    // マスクされている場合は送信しない（上書き防止）
    const appSecret = document.getElementById('settingsThreadsAppSecret').value;
    if (appSecret !== '***設定済み***') {
        params.threads_app_secret = appSecret;
    }

    const result = await apiCall('settings.php', params);

    if (result.success) {
        showToast('Threads API 設定を保存しました', 'success');
    } else {
        showToast(result.message || '保存に失敗しました', 'error');
    }
}

async function exchangeShortToken() {
    console.log('exchangeShortToken called');
    const tokenInput = document.getElementById('settingsThreadsToken');
    const shortToken = tokenInput.value.trim();

    if (!shortToken) {
        showToast('交換する短期トークンを入力してください', 'warning');
        return;
    }

    if (!confirm('入力されたトークンをAPI経由で長期トークン（60日間）に交換します。よろしいですか？')) {
        return;
    }

    showLoading('トークンを交換中');
    try {
        const result = await apiCall('settings.php', {
            action: 'exchange_token',
            short_token: shortToken
        });
        hideLoading();

        if (result.success) {
            showToast('長期トークンへの交換に成功しました', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message || '交換に失敗しました', 'error');
            console.error('Exchange error:', result);
        }
    } catch (err) {
        hideLoading();
        showToast('通信エラーが発生しました。コンソールを確認してください。', 'error');
        console.error('Exchange fatal error:', err);
    }
}

async function saveGeminiSettings() {
    const result = await apiCall('settings.php', {
        action: 'save',
        gemini_api_key: document.getElementById('settingsGeminiKey').value,
        gemini_model: document.getElementById('settingsGeminiModel').value
    });

    if (result.success) {
        showToast('Gemini API 設定を保存しました', 'success');
    } else {
        showToast(result.message || '保存に失敗しました', 'error');
    }
}

async function saveLicenseKey() {
    const result = await apiCall('settings.php', {
        action: 'save',
        license_key: document.getElementById('settingsLicenseKey').value
    });

    if (result.success) {
        showToast('ライセンスキーを保存しました', 'success');
    } else {
        showToast(result.message || '保存に失敗しました', 'error');
    }
}

async function verifyLicense() {
    showLoading('ライセンスを認証中');
    const result = await apiCall('settings.php', { action: 'verify_license' });
    hideLoading();

    const statusEl = document.getElementById('licenseStatus');
    if (result.success && result.valid) {
        statusEl.innerHTML = '<div class="auth-alert success">✅ ライセンスは有効です</div>';
    } else {
        let debugInfo = '';
        if (result.debug) {
            debugInfo = `
                <div style="margin-top:var(--space-sm); padding:var(--space-md); background:var(--color-bg); border-radius:var(--radius-sm); font-family:monospace; font-size:var(--font-size-xs); line-height:1.8; word-break:break-all;">
                    <div><strong>エラー:</strong> ${escapeHtml(result.message || '不明')}</div>
                    <div><strong>HTTP Code:</strong> ${result.http_code || 'N/A'}</div>
                    <div><strong>認証URL:</strong> ${escapeHtml(result.debug.url || 'N/A')}</div>
                    <div><strong>送信トークン:</strong> ${escapeHtml(result.debug.sent_token || 'N/A')}</div>
                    <div><strong>ライセンスキー:</strong> ${escapeHtml(result.debug.license_key || 'N/A')}</div>
                    <div><strong>レスポンス:</strong> ${escapeHtml(result.debug.response || '(空)')}</div>
                </div>`;
        } else if (result.message) {
            debugInfo = `<div style="margin-top:var(--space-sm); font-size:var(--font-size-xs); color:var(--color-text-muted);">${escapeHtml(result.message)}</div>`;
        }
        statusEl.innerHTML = '<div class="auth-alert error">❌ ライセンス認証に失敗しました' + debugInfo + '</div>';
    }
}

async function saveAutoPostSettings() {
    const result = await apiCall('settings.php', {
        action: 'save',
        auto_post_enabled: document.getElementById('settingsAutoPost').checked ? '1' : '0',
        post_interval_variance: document.getElementById('settingsVariance').value,
        ai_label_default: document.getElementById('settingsAiLabelDefault').checked ? '1' : '0'
    });

    if (result.success) {
        showToast('自動投稿設定を保存しました', 'success');
    } else {
        showToast(result.message || '保存に失敗しました', 'error');
    }
}

async function refreshToken() {
    showLoading('トークンを更新中');
    const result = await apiCall('settings.php', { action: 'refresh_token' });
    hideLoading();

    if (result.success) {
        showToast('トークンを更新しました', 'success');
        setTimeout(() => location.reload(), 1500);
    } else {
        showToast(result.message || 'トークンの更新に失敗しました', 'error');
    }
}

// ===== System Update =====
async function checkForUpdate() {
    const btn = document.getElementById('checkUpdateBtn');
    btn.disabled = true;
    btn.textContent = '確認中...';

    const result = await apiCall('../update.php', { action: 'check' });
    btn.disabled = false;
    btn.textContent = '🔍 更新を確認';

    const statusEl = document.getElementById('updateStatus');
    if (result.success) {
        if (result.has_update) {
            statusEl.innerHTML = `
                <div class="auth-alert" style="background:var(--color-info-bg); color:var(--color-info); border:1px solid rgba(41,121,255,0.2);">
                    <strong>新しいバージョンが利用可能です: v${result.latest_version}</strong><br>
                    <span class="text-xs">${escapeHtml(result.release_notes || '').substring(0, 200)}</span>
                </div>
            `;
            document.getElementById('executeUpdateBtn').classList.remove('hidden');
        } else {
            statusEl.innerHTML = '<div class="auth-alert success">✅ 最新バージョンです（v' + result.current_version + '）</div>';
        }
    } else {
        statusEl.innerHTML = '<div class="auth-alert error">' + escapeHtml(result.message || 'エラー') + '</div>';
    }
}

async function executeUpdate() {
    if (!confirm('アップデートを実行しますか？\n（config.php と database.sqlite は保護されます）')) return;

    showLoading('アップデート中');
    const result = await apiCall('../update.php', { action: 'execute' });
    hideLoading();

    if (result.success) {
        showToast('アップデートが完了しました！', 'success');
        setTimeout(() => location.reload(), 2000);
    } else {
        showToast(result.message || 'アップデートに失敗しました', 'error');
    }
}

// ===== Charts =====
let followerChart, hourlyChart, engagementTrendChart;

function initCharts() {
    initFollowerChart();
    initHourlyEngagementChart();
    initEngagementTrendChart();
}

async function initFollowerChart() {
    const ctx = document.getElementById('followerChart');
    if (!ctx) return;

    const result = await apiCall('insights.php', { action: 'follower_history' });
    const data = (result.success && result.data) ? result.data : [];

    if (followerChart) followerChart.destroy();
    followerChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'フォロワー数',
                data: data.map(d => d.count),
                borderColor: '#E1306C',
                backgroundColor: 'rgba(225, 48, 108, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#E1306C'
            }]
        },
        options: chartOptions('フォロワー数')
    });
}

async function initHourlyEngagementChart() {
    const ctx = document.getElementById('hourlyEngagementChart');
    if (!ctx) return;

    const result = await apiCall('insights.php', { action: 'hourly_engagement' });
    const data = (result.success && result.data) ? result.data : [];

    if (hourlyChart) hourlyChart.destroy();
    hourlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.hour + '時'),
            datasets: [{
                label: 'エンゲージメント',
                data: data.map(d => d.engagement),
                backgroundColor: 'rgba(131, 58, 180, 0.6)',
                borderColor: '#833AB4',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: chartOptions('エンゲージメント')
    });
}

async function initEngagementTrendChart() {
    const ctx = document.getElementById('engagementTrendChart');
    if (!ctx) return;

    const result = await apiCall('insights.php', { action: 'engagement_trend' });
    const data = (result.success && result.data) ? result.data : [];

    if (engagementTrendChart) engagementTrendChart.destroy();
    engagementTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [
                {
                    label: 'いいね',
                    data: data.map(d => d.likes),
                    borderColor: '#E1306C',
                    backgroundColor: 'rgba(225, 48, 108, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: '返信',
                    data: data.map(d => d.replies),
                    borderColor: '#833AB4',
                    backgroundColor: 'rgba(131, 58, 180, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'リポスト',
                    data: data.map(d => d.reposts),
                    borderColor: '#F77737',
                    backgroundColor: 'rgba(247, 119, 55, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: chartOptions('エンゲージメント推移')
    });
}

function refreshFollowerChart() {
    initFollowerChart();
    showToast('フォロワーチャートを更新しました', 'info');
}

function chartOptions(title) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#999', font: { family: 'Inter', size: 11 } }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#666', font: { family: 'Inter', size: 10 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#666', font: { family: 'Inter', size: 10 } },
                beginAtZero: true
            }
        }
    };
}

// ===== Utility Functions =====
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('クリップボードにコピーしました', 'success');
    }).catch(() => {
        showToast('コピーに失敗しました', 'error');
    });
}

function copyCronCommand() {
    const input = document.getElementById('cronCommand');
    if (input) {
        navigator.clipboard.writeText(input.value).then(() => {
            showToast('Cronコマンドをコピーしました', 'success');
        }).catch(() => {
            // フォールバック
            input.select();
            document.execCommand('copy');
            showToast('Cronコマンドをコピーしました', 'success');
        });
    }
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
