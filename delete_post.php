<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'post'; // post か comment かを判定

if ($type === 'comment') {
    // 返信（コメント）の所有者確認
    $stmt = $pdo->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch();

    if ($comment && $comment['user_id'] == $user_id) {
        $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
    }
} else {
    // 親投稿の所有者確認
    $stmt = $pdo->prepare("SELECT user_id, board_id FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if ($post && $post['user_id'] == $user_id) {
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
        // 親投稿を消した場合は掲示板トップへ戻る
        header("Location: board.php?id=" . $post['board_id']);
        exit;
    }
}

// 消した後は元のページに戻る
$return_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $return_url);
exit;