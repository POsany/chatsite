<?php
session_start();
require_once 'db_connect.php';

// ログインチェック
$my_id = $_SESSION['user_id'] ?? null;
if (!$my_id) { header('Location: login.php'); exit; }
$unread_count = 0; // 初期化

$target_id = (int)$_GET['id'];

// --- 【ここが重要】既読更新処理 ---
// 「相手(sender_id)から自分(receiver_id)宛」で「未読(is_read=0)」のものをすべて「既読(is_read=1)」にする
$stmt_read = $pdo->prepare("UPDATE direct_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt_read->execute([$target_id, $my_id]);

// その後、通常通りメッセージ履歴などを取得する
// ... (既存のコード)

// 相手の情報取得
$stmt = $pdo->prepare("SELECT nickname, profile_image FROM users WHERE id = ?");
$stmt->execute([$target_id]);
$target_user = $stmt->fetch();

// メッセージ送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $stmt = $pdo->prepare("INSERT INTO direct_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$my_id, $target_id, $_POST['message']]);
    header("Location: dm.php?id=" . $target_id);
    exit;
}

// メッセージ履歴取得
$stmt = $pdo->prepare("SELECT * FROM direct_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->execute([$my_id, $target_id, $target_id, $my_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>DM: <?= htmlspecialchars($target_user['nickname']) ?>さん</title>
    <style>
        body { font-family: sans-serif; background: #eef1f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; display: flex; flex-direction: column; height: 85vh; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .chat-header { padding: 15px; border-bottom: 1px solid #eee; background: #f8f9fa; font-weight: bold; }
        .message-area { flex-grow: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .bubble { max-width: 70%; padding: 10px 15px; border-radius: 18px; font-size: 14px; line-height: 1.4; }
        .mine { align-self: flex-end; background: #007bff; color: white; border-bottom-right-radius: 2px; }
        .theirs { align-self: flex-start; background: #e9ecef; color: #333; border-bottom-left-radius: 2px; }
        .input-area { padding: 15px; border-top: 1px solid #eee; display: flex; gap: 10px; }
        textarea { flex-grow: 1; border: 1px solid #ddd; border-radius: 20px; padding: 10px 15px; resize: none; height: 40px; }
        .send-btn { background: #007bff; color: white; border: none; padding: 0 20px; border-radius: 20px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
<div class="container">
    <div class="chat-header">
        <a href="profile.php?id=<?= $target_id ?>" style="text-decoration:none; color:#333;">← <?= htmlspecialchars($target_user['nickname']) ?>さん</a>
    </div>
    <div class="message-area" id="msgArea">
        <?php foreach($messages as $m): ?>
            <div class="bubble <?= $m['sender_id'] == $my_id ? 'mine' : 'theirs' ?>">
                <?= nl2br(htmlspecialchars($m['message'])) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <form class="input-area" method="POST">
        <textarea name="message" placeholder="メッセージを入力..." required></textarea>
        <button type="submit" class="send-btn">送信</button>
    </form>
</div>
<script>
    // メッセージエリアを最下部にスクロール
    const area = document.getElementById('msgArea');
    area.scrollTop = area.scrollHeight;
</script>
<?php include 'footer.php'; ?>
</body>
</html>