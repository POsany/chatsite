<?php
session_start();
require_once 'db_connect.php';

// 1. 管理者チェック
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();
$unread_count = 0; // 初期化

if (!$user || $user['is_admin'] != 1) {
    die("アクセス権限がありません。");
}

// 2. お問い合わせの対応済み処理 (ここがエラーの箇所です)
if (isset($_GET['resolve_id'])) {
    // GETで送られてきた ID を変数 $resolve_id に代入します
    $resolve_id = (int)$_GET['resolve_id']; 
    
    // 変数 $resolve_id を使って SQL を実行します
    $stmt = $pdo->prepare("UPDATE contacts SET is_resolved = 1 WHERE id = ?");
    $stmt->execute([$resolve_id]); 
    
    header("Location: admin.php#contacts");
    exit;
}

// 3. 投稿の強制削除処理
if (isset($_GET['delete_post_id'])) {
    $delete_id = (int)$_GET['delete_post_id'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: admin.php#post_manage");
    exit;
}

// --- お問い合わせの対応済み処理 ---
if (isset($_GET['resolve_id'])) {
    $resolve_id = (int)$_GET['resolve_id']; 
    $stmt = $pdo->prepare("UPDATE contacts SET is_resolved = 1 WHERE id = ?");
    $stmt->execute([$resolve_id]);
    header("Location: admin.php#contacts");
    exit;
}

// --- データの取得 ---
$boards = $pdo->query("SELECT * FROM boards ORDER BY id DESC")->fetchAll();
$contacts = $pdo->query("SELECT contacts.*, users.nickname FROM contacts JOIN users ON contacts.user_id = users.id ORDER BY is_resolved ASC, created_at DESC")->fetchAll();

// 全ユーザーの最新投稿を50件取得
$all_posts = $pdo->query("
    SELECT posts.*, users.nickname, boards.title as board_title 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    JOIN boards ON posts.board_id = boards.id 
    ORDER BY posts.created_at DESC LIMIT 50
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者画面 - 統合管理</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section { margin-bottom: 40px; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; font-size: 13px; text-align: left; }
        th { background: #333; color: white; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; font-weight: bold; color: white; display: inline-block; }
        .btn-red { background: #dc3545; }
        .btn-blue { background: #007bff; }
        .post-msg { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .img-preview { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
<div class="container">
    <h2>🛠 サイト管理者パネル</h2>
    <p><a href="index.php">← 一般ページへ戻る</a></p>

    <div class="section" id="post_manage">
        <h3>🚨 全ユーザーの投稿管理 (最新50件)</h3>
        <table>
            <tr>
                <th>日時</th><th>掲示板</th><th>投稿者</th><th>内容</th><th>画像</th><th>操作</th>
            </tr>
            <?php foreach($all_posts as $p): ?>
            <tr>
                <td><?= date('m/d H:i', strtotime($p['created_at'])) ?></td>
                <td><small><?= htmlspecialchars($p['board_title']) ?></small></td>
                <td><strong><?= htmlspecialchars($p['nickname']) ?></strong></td>
                <td><div class="post-msg"><?= htmlspecialchars($p['message']) ?></div></td>
                <td>
                    <?php if($p['image_path']): ?>
                        <img src="<?= htmlspecialchars($p['image_path']) ?>" class="img-preview">
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td>
                    <a href="admin.php?delete_post_id=<?= $p['id'] ?>" class="btn btn-red" onclick="return confirm('この投稿を強制削除しますか？\n（この操作は取り消せません）')">強制削除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section" id="contacts">
        <h3>📬 届いたご要望</h3>
        <table>
            <tr><th>状態</th><th>送信者</th><th>件名</th><th>内容</th><th>操作</th></tr>
            <?php foreach($contacts as $c): ?>
            <tr>
                <td><?= $c['is_resolved'] ? '✅完了' : '🔴未対応' ?></td>
                <td><?= htmlspecialchars($c['nickname']) ?></td>
                <td><?= htmlspecialchars($c['subject']) ?></td>
                <td><?= nl2br(htmlspecialchars($c['message'])) ?></td>
                <td>
                    <?php if(!$c['is_resolved']): ?>
                        <a href="admin.php?resolve_id=<?= $c['id'] ?>" class="btn btn-blue">完了にする</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="section">
        <h3>📂 掲示板カテゴリ管理</h3>
        <table>
            <tr><th>アイコン</th><th>タイトル</th><th>説明</th><th>操作</th></tr>
            <?php foreach ($boards as $b): ?>
            <tr>
                <td><img src="<?= $b['board_icon'] ?? 'uploads/default_icon.png' ?>" style="width:40px; height:40px; object-fit:cover;"></td>
                <td><?= htmlspecialchars($b['title']) ?></td>
                <td><small><?= htmlspecialchars($b['description']) ?></small></td>
                <td><a href="admin.php?edit_id=<?= $b['id'] ?>" class="btn btn-blue" style="background:#666;">編集</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>