<?php
session_start();
require_once 'db_connect.php';

// 基盤：ゲスト対応（ログインしていなければnull、していればID取得）
$user_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$board_id = (int)$_GET['id'];

// 基盤：未読DMカウント（ログイン時のみ実行）
$unread_count = 0;
if ($user_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$user_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

$categories = ['雑談', '日記', '質問', '攻略', '募集', 'バグ・エラー'];
$selected_category = $_GET['cat'] ?? '';
$search_word = $_GET['search'] ?? '';

// 掲示板情報の取得
$stmt_board = $pdo->prepare("SELECT * FROM boards WHERE id = :id");
$stmt_board->execute([':id' => $board_id]);
$current_board = $stmt_board->fetch();

if (!$current_board) {
    header('Location: index.php');
    exit;
}

// 基盤：お気に入り状態確認（ログイン時のみ）
$is_board_fav = '';
if ($user_id) {
    $stmt_fav = $pdo->prepare("SELECT id FROM board_favorites WHERE board_id = ? AND user_id = ?");
    $stmt_fav->execute([$board_id, $user_id]);
    $is_board_fav = $stmt_fav->fetch() ? 'active' : '';
}

// 基盤：投稿処理（ログイン時のみ許可）
if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = $upload_dir . $filename;
        }
    }
    $sql = "INSERT INTO posts (user_id, board_id, category, message, image_path) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$user_id, $board_id, $_POST['category'], $_POST['message'], $image_path]);
    header("Location: board.php?id=" . $board_id);
    exit;
}

// 検索・絞り込み
$where = "WHERE posts.board_id = :bid";
$params = [':bid' => $board_id];
if ($selected_category) { $where .= " AND posts.category = :cat"; $params[':cat'] = $selected_category; }
if ($search_word) { $where .= " AND posts.message LIKE :search"; $params[':search'] = "%$search_word%"; }

// 基盤：投稿一覧（最新の返信1件を含む複雑なSQLを維持）
$sql = "SELECT posts.*, users.nickname, users.profile_image,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comment_count,
        (SELECT message FROM comments WHERE comments.post_id = posts.id ORDER BY created_at DESC LIMIT 1) as latest_comment
        FROM posts JOIN users ON posts.user_id = users.id 
        $where ORDER BY posts.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($current_board['title']) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* 基盤デザインの完全維持 */
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        .global-header { background: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .global-header h2 { margin: 0; font-size: 22px; color: #222; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { text-decoration: none; color: #555; position: relative; display: flex; align-items: center; transition: 0.2s; font-weight: bold; }
        .unread-badge { position: absolute; top: -5px; right: -8px; background: #dc3545; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px; }
        .container { max-width: 700px; margin: 20px auto; padding: 0 15px; }
        .header-card, .post-form-card, .post-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .post-card { position: relative; transition: 0.2s; cursor: pointer; border-left: 5px solid transparent; }
        .post-card:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-left: 5px solid #007bff; }
        .card-link { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        .btn-action { cursor: pointer; border: 1px solid #ddd; padding: 6px 15px; border-radius: 20px; font-size: 13px; background: #f8f9fa; position: relative; z-index: 5; transition: 0.2s; outline: none; }
        .btn-action.active { background: #ff4757; color: white; border-color: #ff4757; }
        .btn-fav-board { background: #fff; border: 2px solid #f1c40f; color: #f1c40f; font-weight: bold; }
        .btn-fav-board.active { background: #f1c40f; color: white; }
        .thumbnail { max-width: 200px; border-radius: 6px; margin-top: 10px; display: block; position: relative; z-index: 2; border: 1px solid #eee; }
        .latest-reply { background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 12px; margin-top: 15px; border-left: 4px solid #007bff; color: #555; position: relative; z-index: 2; }
        .cat-badge { font-size: 11px; background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .cat-badge-日記 { background: #9b59b6; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="header-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h2 style="margin:0; color:#222;"><?= htmlspecialchars($current_board['title']) ?></h2>
                <p style="margin:5px 0 0 0; font-size:13px; color:#666;"><?= htmlspecialchars($current_board['description']) ?></p>
            </div>
            <?php if($user_id): ?>
                <button class="btn-action btn-fav-board <?= $is_board_fav ?>" onclick="toggleAction('board_fav', <?= $board_id ?>, this)">
                    <?= $is_board_fav ? '★ お気に入り中' : '☆ お気に入り' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <form class="header-card" method="GET" style="display:flex; gap:10px; padding:15px;">
        <input type="hidden" name="id" value="<?= $board_id ?>">
        <select name="cat" style="padding:8px; border-radius:6px; border:1px solid #ddd;">
            <option value="">すべてのジャンル</option>
            <?php foreach($categories as $c): ?>
                <option value="<?= $c ?>" <?= $selected_category===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="キーワードで検索..." value="<?= htmlspecialchars($search_word) ?>" style="flex-grow:1; padding:8px; border-radius:6px; border:1px solid #ddd;">
        <button type="submit" style="padding:8px 15px; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">検索</button>
    </form>

    <?php if ($user_id): ?>
        <div class="post-form-card">
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom:10px;">
                    <select name="category" style="padding:5px; border-radius:4px; border:1px solid #ddd;">
                        <?php foreach($categories as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <textarea name="message" style="width:100%; height:80px; padding:12px; box-sizing:border-box; border:1px solid #ddd; border-radius:8px; resize:none;" placeholder="ここにメッセージを入力..." required></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    <input type="file" name="image" accept="image/*" style="font-size:12px;">
                    <button type="submit" style="background:#28a745; color:white; border:none; padding:10px 25px; border-radius:8px; cursor:pointer; font-weight:bold;">投稿する</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="post-form-card" style="text-align:center; padding:30px; color:#666;">
            投稿するには <a href="login.php" style="color:#007bff; font-weight:bold;">ログイン</a> または <a href="register.php" style="color:#007bff; font-weight:bold;">新規登録</a> が必要です。
        </div>
    <?php endif; ?>

    <?php foreach($posts as $p): ?>
        <div class="post-card">
            <a href="post_detail.php?id=<?= $p['id'] ?>" class="card-link"></a>
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="display:flex; align-items:center; gap:12px; position:relative; z-index:5;">
                    <a href="profile.php?id=<?= $p['user_id'] ?>">
                        <?php if($p['profile_image']): ?>
                            <img src="<?= htmlspecialchars($p['profile_image']) ?>" class="avatar">
                        <?php else: ?>
                            <div style="width:45px; height:45px; border-radius:50%; background:#ccc; display:flex; align-items:center; justify-content:center; color:white; font-size:20px;">👤</div>
                        <?php endif; ?>
                    </a>
                    <div>
                        <strong style="display:block;"><?= htmlspecialchars($p['nickname']) ?></strong>
                        <small style="color:#999;"><?= $p['created_at'] ?></small>
                    </div>
                </div>
                <span class="cat-badge cat-badge-<?= $p['category'] ?>"><?= $p['category'] ?></span>
            </div>

            <p style="position:relative; z-index:2; line-height:1.6; margin-top:10px;">
                <?= mb_strimwidth(htmlspecialchars($p['message']), 0, 160, "...") ?>
            </p>

            <?php if($p['image_path']): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>" class="thumbnail">
            <?php endif; ?>
            
            <div style="margin-top:15px; position:relative; z-index:5; display:flex; align-items:center; gap:15px;">
                <button class="btn-action" onclick="event.stopPropagation(); toggleAction('post_like', <?= $p['id'] ?>, this)">
                    ❤️ <span class="count"><?= $p['like_count'] ?></span>
                </button>
                <span style="font-size:13px; color:#666;">💬 <?= $p['comment_count'] ?> 件の返信</span>
            </div>

            <?php if($p['latest_comment']): ?>
                <div class="latest-reply">
                    <strong>最新の返信:</strong> <?= mb_strimwidth(htmlspecialchars($p['latest_comment']), 0, 80, "...") ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleAction(type, id, btn) {
    // ゲストの場合はログインへ誘導
    if(!<?= $user_id ? 'true' : 'false' ?>) {
        if(confirm('この操作にはログインが必要です。ログイン画面へ移動しますか？')) {
            window.location.href = 'login.php';
        }
        return;
    }
    const fd = new FormData();
    fd.append('type', type);
    fd.append('target_id', id);
    fetch('like_api.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if(data.error) return alert(data.error);
        btn.classList.toggle('active', data.status === 'liked' || data.status === 'added');
        if(type === 'board_fav') btn.innerText = (data.status === 'added' ? '★ お気に入り中' : '☆ お気に入り');
        const countSpan = btn.querySelector('.count');
        if(countSpan) countSpan.innerText = data.count;
    });
}
</script>
<?php include 'footer.php'; ?>
</body>
</html>