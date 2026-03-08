<?php
session_start();
require_once 'db_connect.php';

// 基盤：マイページはログイン必須
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$unread_count = 0;

// データ取得ロジック
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
$stmt_unread->execute([$user_id]);
$unread_count = $stmt_unread->fetchColumn();

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();

// カウントデータ
$following_count = $pdo->query("SELECT COUNT(*) FROM user_follows WHERE follower_id = $user_id")->fetchColumn();
$follower_count = $pdo->query("SELECT COUNT(*) FROM user_follows WHERE following_id = $user_id")->fetchColumn();

// 各タブ用リスト
$my_posts = $pdo->query("SELECT posts.*, boards.title as b_title FROM posts JOIN boards ON posts.board_id = boards.id WHERE posts.user_id = $user_id ORDER BY posts.created_at DESC")->fetchAll();
$fav_posts = $pdo->query("SELECT posts.*, users.nickname, boards.title as b_title FROM post_favorites JOIN posts ON post_favorites.post_id = posts.id JOIN users ON posts.user_id = users.id JOIN boards ON posts.board_id = boards.id WHERE post_favorites.user_id = $user_id ORDER BY post_favorites.created_at DESC")->fetchAll();
$following_users = $pdo->query("SELECT users.* FROM user_follows JOIN users ON user_follows.following_id = users.id WHERE user_follows.follower_id = $user_id")->fetchAll();
$follower_users = $pdo->query("SELECT users.* FROM user_follows JOIN users ON user_follows.follower_id = users.id WHERE user_follows.following_id = $user_id")->fetchAll();
$fav_boards = $pdo->query("SELECT boards.* FROM board_favorites JOIN boards ON board_favorites.board_id = boards.id WHERE board_favorites.user_id = $user_id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイページ - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        .container { max-width: 700px; margin: 20px auto; padding: 0 15px; }
        .profile-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .avatar-large { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; background: #ddd; }
        .stats-group { display: flex; gap: 20px; margin-top: 10px; }
        .stat-num { font-weight: bold; font-size: 18px; color: #222; }
        .stat-label { font-size: 12px; color: #777; margin-left: 3px; }
        .tabs { display: flex; background: #e0e4e8; border-radius: 10px; margin-bottom: 20px; overflow-x: auto; padding: 4px; }
        .tab-btn { flex: 1; padding: 10px 15px; border: none; cursor: pointer; background: none; font-weight: bold; border-radius: 8px; white-space: nowrap; transition: 0.2s; }
        .tab-btn.active { background: white; color: #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .list-item { background: white; padding: 15px; border-radius: 10px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .item-info { flex-grow: 1; position: relative; }
        .delete-btn { color: #ff4757; text-decoration: none; font-size: 12px; font-weight: bold; display: flex; align-items: center; gap: 2px; }
        .delete-btn:hover { opacity: 0.7; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="profile-card">
        <img src="<?= htmlspecialchars($current_user['profile_image'] ?: 'https://via.placeholder.com/90') ?>" class="avatar-large">
        <div class="profile-info">
            <h2 style="margin:0;"><?= htmlspecialchars($current_user['nickname']) ?></h2>
            <div class="stats-group">
                <div onclick="openTab(null, 'tab-following')" style="cursor:pointer;"><span class="stat-num"><?= $following_count ?></span><span class="stat-label">フォロー</span></div>
                <div onclick="openTab(null, 'tab-followers')" style="cursor:pointer;"><span class="stat-num"><?= $follower_count ?></span><span class="stat-label">フォロワー</span></div>
            </div>
            <a href="edit_profile.php" style="font-size:12px; color:#007bff; text-decoration:none; display:inline-block; margin-top:10px; font-weight:bold;">プロフィール編集</a>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="openTab(event, 'tab-posts')">投稿</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-favs')">保存済み</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-following')">フォロー</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-followers')">フォロワー</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-games')">掲示板</button>
    </div>

    <div id="tab-posts" class="tab-content active">
        <?php if(empty($my_posts)): ?><p style="text-align:center; color:#999;">投稿はまだありません。</p><?php endif; ?>
        <?php foreach($my_posts as $p): ?>
            <div class="list-item" style="cursor: pointer; position: relative;" onclick="location.href='post_detail.php?id=<?= $p['id'] ?>'">
                <div class="item-info">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:11px; color:#007bff; font-weight:bold;">📍 <?= htmlspecialchars($p['b_title']) ?></span>
                        
                        <a href="delete_post.php?id=<?= $p['id'] ?>" 
                           onclick="event.stopPropagation(); return confirm('削除しますか？');" 
                           class="delete-btn">
                            <span class="material-symbols-outlined" style="font-size:16px;">delete</span>削除
                        </a>
                    </div>
                    <p style="margin:8px 0;"><?= mb_strimwidth(htmlspecialchars($p['message']), 0, 100, "...") ?></p>
                    <small style="color:#bbb; font-size:10px;"><?= $p['created_at'] ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-favs" class="tab-content">
        <?php if(empty($fav_posts)): ?><p style="text-align:center; color:#999;">保存した投稿はありません。</p><?php endif; ?>
        <?php foreach($fav_posts as $p): ?>
            <div class="list-item" style="cursor: pointer;" onclick="location.href='post_detail.php?id=<?= $p['id'] ?>'">
                <div class="item-info">
                    <div style="display:flex; justify-content:space-between;">
                        <strong><?= htmlspecialchars($p['nickname']) ?></strong>
                        <span style="font-size:11px; color:#888;">📍 <?= htmlspecialchars($p['b_title']) ?></span>
                    </div>
                    <p style="margin:8px 0; font-size:14px;"><?= mb_strimwidth(htmlspecialchars($p['message']), 0, 100, "...") ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-following" class="tab-content">
        <?php if(empty($following_users)): ?><p style="text-align:center; color:#999;">フォローしているユーザーはいません。</p><?php endif; ?>
        <?php foreach($following_users as $u): ?>
            <div class="list-item" style="cursor: pointer;" onclick="location.href='profile.php?id=<?= $u['id'] ?>'">
                <img src="<?= htmlspecialchars($u['profile_image'] ?: 'https://via.placeholder.com/40') ?>" style="width:40px; height:40px; border-radius:50%; object-fit: cover;">
                <div class="item-info">
                    <strong style="display:block;"><?= htmlspecialchars($u['nickname']) ?></strong>
                    <span style="font-size: 11px; color: #888;">@<?= htmlspecialchars($u['userid']) ?></span>
                </div>
                <span class="material-symbols-outlined" style="color: #ccc; font-size: 20px;">chevron_right</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-followers" class="tab-content">
        <?php if(empty($follower_users)): ?><p style="text-align:center; color:#999;">フォロワーはまだいません。</p><?php endif; ?>
        <?php foreach($follower_users as $u): ?>
            <div class="list-item" style="cursor: pointer;" onclick="location.href='profile.php?id=<?= $u['id'] ?>'">
                <img src="<?= htmlspecialchars($u['profile_image'] ?: 'https://via.placeholder.com/40') ?>" style="width:40px; height:40px; border-radius:50%; object-fit: cover;">
                <div class="item-info">
                    <strong style="display:block;"><?= htmlspecialchars($u['nickname']) ?></strong>
                    <span style="font-size: 11px; color: #888;">@<?= htmlspecialchars($u['userid']) ?></span>
                </div>
                <span class="material-symbols-outlined" style="color: #ccc; font-size: 20px;">chevron_right</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-games" class="tab-content">
        <?php foreach($fav_boards as $b): ?>
            <div class="list-item">
                <img src="<?= htmlspecialchars($b['board_icon'] ?: 'https://via.placeholder.com/40') ?>" style="width:40px; height:40px; border-radius:8px;">
                <a href="board.php?id=<?= $b['id'] ?>" style="text-decoration:none; color:#333; font-weight:bold;"><?= htmlspecialchars($b['title']) ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    let contents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < contents.length; i++) contents[i].style.display = "none";
    let btns = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < btns.length; i++) btns[i].classList.remove("active");
    document.getElementById(tabName).style.display = "block";
    if(evt) evt.currentTarget.classList.add("active");
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>