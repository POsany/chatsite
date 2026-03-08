<?php
session_start();
require_once 'db_connect.php';

// 基盤：ゲスト対応
$user_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$post_id = (int)$_GET['id'];

// 基盤：未読DMカウント（ログイン時のみ）
if ($user_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$user_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

// 基盤：親投稿の取得（いいね数・お気に入り状態を含む）
$stmt = $pdo->prepare("SELECT posts.*, users.nickname, users.profile_image, boards.title as b_title,
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
    (SELECT id FROM post_favorites WHERE post_id = posts.id AND user_id = :uid) as is_fav
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    JOIN boards ON posts.board_id = boards.id
    WHERE posts.id = :id");
$stmt->execute([':id' => $post_id, ':uid' => $user_id ?: 0]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

// 基盤：返信一覧の取得
$stmt_comments = $pdo->prepare("SELECT comments.*, users.nickname, users.profile_image,
    (SELECT COUNT(*) FROM comment_likes WHERE comment_id = comments.id) as like_count
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE post_id = ? ORDER BY created_at ASC");
$stmt_comments->execute([$post_id]);
$comments = $stmt_comments->fetchAll();

// 返信投稿処理（ログイン時のみ）
if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $_POST['message']]);
    header("Location: post_detail.php?id=" . $post_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿詳細 - <?= mb_strimwidth(htmlspecialchars($post['message']), 0, 20, "...") ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        .container { max-width: 700px; margin: 20px auto; padding: 0 15px; }
        .main-post, .comment-card, .reply-form-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .main-post { border-left: 6px solid #007bff; position: relative; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .btn-action { cursor: pointer; border: 1px solid #ddd; padding: 6px 15px; border-radius: 20px; font-size: 13px; background: #f8f9fa; transition: 0.2s; display: flex; align-items: center; gap: 5px; }
        .btn-action.active { background: #ff4757; color: white; border-color: #ff4757; }
        .btn-fav.active { background: #3498db; color: white; border-color: #3498db; }
        .btn-delete { color: #dc3545; border-color: #f8d7da; text-decoration: none; }
        .btn-delete:hover { background: #f8d7da; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <a href="board.php?id=<?= $post['board_id'] ?>" style="text-decoration:none; font-size:14px; color:#007bff; display:inline-block; margin-bottom:15px; font-weight:bold;">← <?= htmlspecialchars($post['b_title']) ?> に戻る</a>
    
    <div class="main-post">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img src="<?= htmlspecialchars($post['profile_image'] ?: 'https://via.placeholder.com/45') ?>" class="avatar">
                <div>
                    <strong style="display:block;"><?= htmlspecialchars($post['nickname']) ?></strong>
                    <small style="color:#999;"><?= $post['created_at'] ?></small>
                </div>
            </div>
            <?php if ($user_id && $post['user_id'] == $user_id): ?>
                <a href="delete_post.php?id=<?= $post['id'] ?>&type=post" class="btn-action btn-delete" onclick="return confirm('投稿を削除しますか？掲示板から完全に消去されます。');">
                    <span class="material-symbols-outlined" style="font-size:18px;">delete</span>削除
                </a>
            <?php endif; ?>
        </div>

        <p style="font-size:18px; line-height:1.7; word-wrap: break-word;"><?= nl2br(htmlspecialchars($post['message'])) ?></p>
        
        <?php if($post['image_path']): ?>
            <img src="<?= htmlspecialchars($post['image_path']) ?>" style="max-width:100%; border-radius:8px; margin-top:15px;">
        <?php endif; ?>

        <div style="margin-top:20px; display:flex; gap:10px;">
            <button class="btn-action <?= $user_id && $post['like_count']>0 ? 'active':'' ?>" onclick="toggleAction('post_like', <?= $post['id'] ?>, this)">
                ❤️ <span class="count"><?= $post['like_count'] ?></span>
            </button>
            <button class="btn-action btn-fav <?= $post['is_fav']?'active':'' ?>" onclick="toggleAction('post_fav', <?= $post['id'] ?>, this)">
                🔖 <?= $post['is_fav']?'保存済み':'保存' ?>
            </button>
        </div>
    </div>

    <h3 style="margin-left:10px; color:#555;">返信一覧 (<?= count($comments) ?>)</h3>
    <?php foreach($comments as $c): ?>
        <div class="comment-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <img src="<?= htmlspecialchars($c['profile_image'] ?: 'https://via.placeholder.com/35') ?>" style="width:35px; height:35px; border-radius:50%;">
                    <strong><?= htmlspecialchars($c['nickname']) ?></strong>
                    <small style="color:#999;"><?= $c['created_at'] ?></small>
                </div>
                <?php if ($user_id && $c['user_id'] == $user_id): ?>
                    <a href="delete_post.php?id=<?= $c['id'] ?>&type=comment" style="color:#ff4757; text-decoration:none; font-size:12px;" onclick="return confirm('返信を削除しますか？');">
                        削除
                    </a>
                <?php endif; ?>
            </div>
            <p><?= nl2br(htmlspecialchars($c['message'])) ?></p>
        </div>
    <?php endforeach; ?>

    <?php if ($user_id): ?>
        <div class="reply-form-card">
            <form method="POST">
                <textarea name="message" style="width:100%; height:100px; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;" placeholder="返信を入力..." required></textarea>
                <button type="submit" style="background:#007bff; color:white; border:none; padding:10px 25px; border-radius:8px; cursor:pointer; font-weight:bold; margin-top:10px;">返信を投稿</button>
            </form>
        </div>
    <?php else: ?>
        <div class="reply-form-card" style="text-align:center; color:#666;">
            返信するには <a href="login.php" style="color:#007bff; font-weight:bold;">ログイン</a> してください。
        </div>
    <?php endif; ?>
</div>

<script>
function toggleAction(type, id, btn) {
    if(!<?= $user_id ? 'true' : 'false' ?>) {
        if(confirm('ログインが必要です。移動しますか？')) window.location.href = 'login.php';
        return;
    }
    const fd = new FormData();
    fd.append('type', type);
    fd.append('target_id', id);
    fetch('like_api.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        btn.classList.toggle('active', data.status === 'liked' || data.status === 'added');
        if(type === 'post_fav') btn.innerText = (data.status === 'added' ? '🔖 保存済み' : '🔖 保存');
        const countSpan = btn.querySelector('.count');
        if(countSpan) countSpan.innerText = data.count;
    });
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>