<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int)($_POST['post_id'] ?? 0);

if (!$post_id) {
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

// 自分の投稿かチェック
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => '削除権限がありません']);
    exit;
}

// 削除実行（関連するいいねやコメントはDBのCASCADE設定で自動削除されます）
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
if ($stmt->execute([$post_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => '削除に失敗しました']);
}