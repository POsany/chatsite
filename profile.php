<?php
session_start();
require_once 'db_connect.php';

// 基盤：ゲスト対応（ログインしていなければnull）
$my_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$target_id = (int)$_GET['id'];

// 基盤：未読DMカウント（ログイン時のみ）
$unread_count = 0;
if ($my_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$my_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

// ターゲットユーザーの基本情報
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    header('Location: index.php');
    exit;
}

// 基盤：フォロー・フォロワー数の取得
$stmt_following_count = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
$stmt_following_count->execute([$target_id]);
$following_count = $stmt_following_count->fetchColumn();

$stmt_follower_count = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
$stmt_follower_count->execute([$target_id]);
$follower_count = $stmt_follower_count->fetchColumn();

// 基盤：フォロー状態確認（ログイン時のみ）
$is_following = '';
if ($my_id) {
    $stmt_follow = $pdo->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
    $stmt_follow->execute([$my_id, $target_id]);
    $is_following = $stmt_follow->fetch() ? 'active' : '';
}

// 基盤：そのユーザーの投稿一覧（掲示板タイトル付き）
$stmt_posts = $pdo->prepare("SELECT posts.*, boards.title as b_title 
                             FROM posts 
                             JOIN boards ON posts.board_id = boards.id 
                             WHERE posts.user_id = ? 
                             ORDER BY posts.created_at DESC");
$stmt_posts->execute([$target_id]);
$posts = $stmt_posts->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($target_user['nickname']) ?>さんのプロフィール</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        
        /* 統一グローバルヘッダー */
        .global-header { 
            background: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .global-header h2 { margin: 0; font-size: 22px; color: #222; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { text-decoration: none; color: #555; position: relative; display: flex; align-items: center; font-weight: bold; }
        .unread-badge { position: absolute; top: -5px; right: -8px; background: #dc3545; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px; }

        .container { max-width: 700px; margin: 20px auto; padding: 0 15px; }
        
        /* プロフィールカード（マイページと共通のデザイン） */
        .profile-card { background: white; padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .avatar-large { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        
        .stats-group { display: flex; justify-content: center; gap: 30px; margin: 15px 0; }
        .stat-item { text-align: center; }
        .stat-num { font-weight: bold; font-size: 20px; color: #222; display: block; }
        .stat-label { font-size: 12px; color: #777; }
        
        /* アクションボタン */
        .action-group { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
        .btn-action { padding: 10px 25px; border-radius: 25px; border: 1px solid #ddd; cursor: pointer; font-weight: bold; transition: 0.2s; text-decoration: none; font-size: 14px; outline: none; }
        .btn-follow { background: #f8f9fa; color: #333; }
        .btn-follow.active { background: #007bff; color: white; border-color: #007bff; }
        .btn-dm { background: #28a745; color: white; border-color: #28a745; }

        /* 投稿カード一覧（掲示板の基盤デザインを維持） */
        .post-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-decoration: none; color: inherit; display: block; transition: 0.2s; border-left: 5px solid #28a745; }
        .post-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .tag { font-size: 11px; background: #eef1f5; padding: 2px 8px; border-radius: 4px; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <div class="profile-card">
        <?php if($target_user['profile_image']): ?>
            <img src="<?= htmlspecialchars($target_user['profile_image']) ?>" class="avatar-large">
        <?php else: ?>
            <div style="width:100px; height:100px; border-radius:50%; background:#ccc; margin:0 auto 15px; display:flex; align-items:center; justify-content:center; color:white; font-size:40px;">👤</div>
        <?php endif; ?>
        
        <h2 style="margin:0;"><?= htmlspecialchars($target_user['nickname']) ?></h2>
        
        <div class="stats-group">
            <div class="stat-item">
                <span class="stat-num"><?= $following_count ?></span><span class="stat-label">フォロー</span>
            </div>
            <div class="stat-item">
                <span class="stat-num"><?= $follower_count ?></span><span class="stat-label">フォロワー</span>
            </div>
        </div>

        <?php if ($my_id): ?>
            <?php if($my_id !== $target_id): ?>
                <div class="action-group">
                    <button class="btn-action btn-follow <?= $is_following ?>" onclick="toggleFollow(<?= $target_id ?>, this)">
                        <?= $is_following ? 'フォロー中' : 'フォローする' ?>
                    </button>
                    <a href="dm.php?id=<?= $target_id ?>" class="btn-action btn-dm">メッセージを送る</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color:#888; font-size:13px; margin-top:15px;">
                <a href="login.php" style="color:#007bff; font-weight:bold; text-decoration:none;">ログイン</a> するとフォローやDMが可能です
            </p>
        <?php endif; ?>
    </div>

    <h3 style="color:#555; margin: 0 0 15px 10px;">最近の投稿 (<?= count($posts) ?>)</h3>

    <?php if(empty($posts)): ?>
        <p style="text-align:center; color:#888; padding:40px; background:white; border-radius:12px;">まだ投稿がありません。</p>
    <?php endif; ?>

    <?php foreach($posts as $p): ?>
        <a href="post_detail.php?id=<?= $p['id'] ?>" class="post-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <span class="tag">📍 <?= htmlspecialchars($p['b_title']) ?></span>
                <small style="color:#999;"><?= $p['created_at'] ?></small>
            </div>
            <p style="margin:10px 0; line-height:1.6;">
                <?= mb_strimwidth(htmlspecialchars($p['message']), 0, 160, "...") ?>
            </p>
        </a>
    <?php endforeach; ?>
</div>

<script>
function toggleFollow(id, btn) {
    if(!<?= $my_id ? 'true' : 'false' ?>) return;
    const fd = new FormData();
    fd.append('type', 'user_follow');
    fd.append('target_id', id);
    fetch('like_api.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.error) return alert(data.error);
        btn.classList.toggle('active', data.status === 'added');
        btn.innerText = (data.status === 'added' ? 'フォロー中' : 'フォローする');
    });
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>