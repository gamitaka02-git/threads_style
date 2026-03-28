<?php
/**
 * ============================================================
 * スタイルガイド API エンドポイント - ThreadsStyle
 * ============================================================
 * AIスタイルガイドの取得・保存
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

    // ----- スタイルガイドの取得 -----
    case 'get':
        $stmt = $pdo->query("SELECT * FROM style_guides WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($guide) {
            echo json_encode([
                'success' => true,
                'content' => $guide['content_markdown'],
                'analysis_data' => $guide['analysis_data'],
                'updated_at' => $guide['updated_at'],
            ]);
        } else {
            echo json_encode(['success' => true, 'content' => '', 'analysis_data' => '']);
        }
        break;

    // ----- スタイルガイドの保存 -----
    case 'save':
        $content = $_POST['content'] ?? '';

        // 既存の有効なガイドがあるか確認
        $stmt = $pdo->query("SELECT id FROM style_guides WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 既存を更新
            $stmt = $pdo->prepare("UPDATE style_guides SET content_markdown = :content, updated_at = datetime('now') WHERE id = :id");
            $stmt->execute([':content' => $content, ':id' => $existing['id']]);
        } else {
            // 新規作成
            $stmt = $pdo->prepare("INSERT INTO style_guides (content_markdown, is_active) VALUES (:content, 1)");
            $stmt->execute([':content' => $content]);
        }

        echo json_encode(['success' => true, 'message' => 'スタイルガイドを保存しました']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '不正なアクションです']);
}
