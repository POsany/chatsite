<?php
session_start();
require_once 'db_connect.php'; // 最初に読み込むことで $pdo が定義されます

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$my_id = $_SESSION['user_id'];
$unread_count = 0; // 初期化

// --- 1. ヘッダー用の未読DMカウント取得 ---
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
$stmt_unread->execute([$my_id]);
$unread_count = $stmt_unread->fetchColumn();

// --- 2. メッセージ一覧の取得（エラーの原因箇所） ---
// SQLの中に ? が 4つあるので、executeの配列にも 4つ 値を入れます
$sql = "
    SELECT 
        u.id as partner_id, 
        u.nickname, 
        u.profile_image, 
        dm.message as last_message, 
        dm.created_at,
        dm.is_read,
        dm.receiver_id
    FROM direct_messages dm
    JOIN users u ON (u.id = IF(dm.sender_id = ?, dm.receiver_id, dm.sender_id))
    WHERE (dm.sender_id = ? OR dm.receiver_id = ?)
    AND dm.id IN (
        SELECT MAX(id) FROM direct_messages 
        WHERE sender_id = ? OR receiver_id = ? 
        GROUP BY IF(sender_id = ?, receiver_id, sender_id)
    )
    ORDER BY dm.created_at DESC
";

$stmt = $pdo->prepare($sql);

// エラー回避のため、プレースホルダの数（今回は6箇所修正が必要な場合があります）に合わせて $my_id を渡します
// 以下の配列の数は、SQL内の ? の出現順序と一致させる必要があります。
$stmt->execute([$my_id, $my_id, $my_id, $my_id, $my_id, $my_id]);
$chats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メッセージ一覧</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        
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
        .chat-list-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .chat-item { display: flex; align-items: center; gap: 15px; padding: 15px 20px; text-decoration: none; color: inherit; border-bottom: 1px solid #f0f0f0; transition: 0.2s; }
        .chat-item:hover { background: #f8f9fa; }
        .avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .chat-info { flex-grow: 1; min-width: 0; }
        .nickname { font-weight: bold; font-size: 16px; }
        .last-msg { font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .unread-dot { width: 10px; height: 10px; background: #007bff; border-radius: 50%; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h3 style="margin-left: 10px;">📩 メッセージ一覧</h3>
    <div class="chat-list-card">
        <?php if(empty($chats)): ?>
            <p style="text-align:center; color:#888; padding:50px;">やり取りはありません。</p>
        <?php endif; ?>

        <?php foreach($chats as $c): ?>
            <a href="dm.php?id=<?= $c['partner_id'] ?>" class="chat-item">
                <?php if($c['profile_image']): ?>
                    <img src="<?= htmlspecialchars($c['profile_image']) ?>" class="avatar">
                <?php else: ?>
                    <div style="width:50px; height:50px; border-radius:50%; background:#ccc; display:flex; align-items:center; justify-content:center; color:white;">👤</div>
                <?php endif; ?>
                <div class="chat-info">
                    <div style="display:flex; justify-content:space-between;">
                        <span class="nickname"><?= htmlspecialchars($c['nickname']) ?></span>
                        <span style="font-size:11px; color:#999;"><?= date('m/d', strtotime($c['created_at'])) ?></span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span class="last-msg"><?= htmlspecialchars($c['last_message']) ?></span>
                        <?php if($c['is_read'] == 0 && $c['receiver_id'] == $my_id): ?>
                            <div class="unread-dot"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>