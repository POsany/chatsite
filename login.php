<?php
session_start();
require_once 'db_connect.php';

// ログイン済みならトップへ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 入力値を「識別子(User ID or Email)」として受け取る
    $login_input = $_POST['login_input'];
    $password = $_POST['password'];

    // userid または email が一致するユーザーを探す
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userid = ? OR email = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nickname'] = $user['nickname']; // 表示用にはニックネームを使用
        header('Location: index.php');
        exit;
    } else {
        $error = "ユーザーID・メールアドレス、またはパスワードが正しくありません。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .login-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        .login-card h1 { margin-top: 0; font-size: 24px; text-align: center; color: #222; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 16px; }
        
        .btn-login { background: #007bff; color: white; border: none; padding: 14px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; transition: 0.2s; }
        .btn-login:hover { background: #0056b3; }
        
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-content">
    <div class="login-card">
        <h1>ログイン</h1>
        <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ユーザーID または メールアドレス</label>
                <input type="text" name="login_input" placeholder="ユーザーIDかメールアドレス" required>
            </div>
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">ログイン</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:14px; color:#666;">
            アカウントをお持ちでない方は <a href="register.php" style="color:#007bff; font-weight:bold; text-decoration:none;">新規登録</a>
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>