<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$unread_count = 0; // 初期化

// 現在の情報を取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = $_POST['nickname'];
    
    // 画像アップロード処理
    $image_path = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $filename = 'prof_' . uniqid() . '_' . basename($_FILES['profile_image']['name']);
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $filename)) {
            $image_path = $upload_dir . $filename;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET nickname = ?, profile_image = ? WHERE id = ?");
    $stmt->execute([$nickname, $image_path, $user_id]);
    
    $_SESSION['user_nickname'] = $nickname;
    header('Location: mypage.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プロフィール編集</title>
    <style>
        body { font-family: sans-serif; background: #eef1f5; padding: 20px; }
        .container { max-width: 500px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
<div class="container">
    <a href="mypage.php" style="text-decoration:none; color:#666;">← 戻る</a>
    <h2 style="text-align:center; margin: 20px 0;">👤 プロフィール編集</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ニックネーム</label>
            <input type="text" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>" required>
        </div>
        <div class="form-group">
            <label>プロフィール画像</label>
            <input type="file" name="profile_image" accept="image/*" style="font-size:13px;">
        </div>
        <button type="submit" class="btn">変更を保存する</button>
    </form>
</div>
<?php include 'footer.php'; ?>
</body>
</html>