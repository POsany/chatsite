<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];
$board_id = isset($_POST['board_id']) ? $_POST['board_id'] : '';
$is_admin_request = isset($_POST['admin_delete']) ? true : false;

try {
    // 管理者かどうかチェック
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();
    $is_admin = ($user && $user['is_admin'] == 1);

    // 削除権限の確認（本人、または管理者であること）
    if ($is_admin_request && $is_admin) {
        $sql = "SELECT image_path FROM posts WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $post_id]);
    } else {
        $sql = "SELECT image_path FROM posts WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $post_id, ':user_id' => $user_id]);
    }
    
    $post = $stmt->fetch();

    if ($post) {
        if (!empty($post['image_path']) && file_exists($post['image_path'])) {
            unlink($post['image_path']);
        }
        $sql_delete = "DELETE FROM posts WHERE id = :id";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':id' => $post_id]);
    }

} catch (PDOException $e) {}

// 管理画面からの削除なら管理画面に戻る
if ($is_admin_request) {
    header('Location: admin.php');
} elseif (!empty($board_id)) {
    header('Location: board.php?id=' . $board_id);
} else {
    header('Location: index.php');
}
exit;