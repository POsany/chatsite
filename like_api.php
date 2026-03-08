<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_POST['type'] ?? ''; 
$target_id = (int)($_POST['target_id'] ?? 0);

$map = [
    'post_like'    => ['table' => 'likes', 'col' => 'post_id'],
    'comment_like' => ['table' => 'comment_likes', 'col' => 'comment_id'],
    'post_fav'     => ['table' => 'post_favorites', 'col' => 'post_id'],
    'board_fav'    => ['table' => 'board_favorites', 'col' => 'board_id'],
    'user_follow'  => ['table' => 'user_follows', 'col' => 'following_id']
];

if (!$target_id || !isset($map[$type])) {
    echo json_encode(['error' => '不正なリクエストです']);
    exit;
}

$table = $map[$type]['table'];
$column = $map[$type]['col'];
$user_col = ($type === 'user_follow') ? 'follower_id' : 'user_id';

$stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? AND $user_col = ?");
$stmt->execute([$target_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$existing['id']]);
    $status = 'removed';
} else {
    if ($type === 'user_follow' && $target_id === $user_id) {
        echo json_encode(['error' => '自分自身はフォローできません']);
        exit;
    }
    $pdo->prepare("INSERT INTO $table ($column, $user_col) VALUES (?, ?)")->execute([$target_id, $user_id]);
    $status = 'added';
}

$count = 0;
if (strpos($type, 'like') !== false) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->execute([$target_id]);
    $count = $stmt->fetchColumn();
}

echo json_encode(['status' => $status, 'count' => $count]);