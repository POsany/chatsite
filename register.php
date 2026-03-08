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
    $userid = $_POST['userid']; // ログイン用ID
    $nickname = $_POST['nickname']; // 表示用名
    $email = !empty($_POST['email']) ? $_POST['email'] : null; // 任意
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // 1. ユーザーIDの重複チェック（必須）
    $check_id = $pdo->prepare("SELECT id FROM users WHERE userid = ?");
    $check_id->execute([$userid]);
    
    // 2. メールアドレスの重複チェック（入力されている場合のみ）
    $email_exists = false;
    if ($email) {
        $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->execute([$email]);
        if ($check_email->fetch()) { $email_exists = true; }
    }

    if ($check_id->fetch()) {
        $error = "このユーザーIDは既に使われています。";
    } elseif ($email_exists) {
        $error = "このメールアドレスは既に登録されています。";
    } else {
        // 登録実行
        $stmt = $pdo->prepare("INSERT INTO users (userid, nickname, email, password) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$userid, $nickname, $email, $password])) {
            header('Location: login.php?msg=register_success');
            exit;
        } else {
            $error = "登録に失敗しました。システム管理者にお問い合わせください。";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規会員登録 - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        /* 基盤デザインの完全維持 */
        body { font-family: sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        
        .register-box {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
        }
        .register-box h2 { text-align: center; margin: 0 0 25px 0; color: #28a745; font-size: 24px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: bold; color: #444; }
        .form-group input { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; 
            box-sizing: border-box; font-size: 16px; background: #f9f9f9;
        }
        .form-group input:focus { outline: none; border-color: #28a745; background: #fff; }

        .submit-btn {
            width: 100%; padding: 14px; background-color: #28a745; color: white;
            border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; margin-top: 10px; transition: 0.2s;
        }
        .submit-btn:hover { background-color: #218838; transform: translateY(-1px); }
        
        .login-link { display: block; text-align: center; margin-top: 20px; font-size: 14px; color: #007bff; text-decoration: none; font-weight: bold; }
        .error-msg { color: #dc3545; background: #f8d7da; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .info-text { font-size: 12px; color: #d9534f; margin-top: 8px; line-height: 1.5; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="register-box">
            <h2>新規会員登録</h2>
            
            <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="userid">ユーザーID (半角英数字)</label>
                    <input type="text" id="userid" name="userid" placeholder="ログインに使用します" pattern="^[a-zA-Z0-9]+$" title="半角英数字で入力してください" required>
                </div>

                <div class="form-group">
                    <label for="nickname">ニックネーム (表示名)</label>
                    <input type="text" id="nickname" name="nickname" placeholder="サイト内で表示される名前" required>
                </div>

                <div class="form-group">
                    <label for="email">メールアドレス（任意）</label>
                    <input type="email" id="email" name="email" placeholder="sample@example.com">
                    <p class="info-text">
                        ※必須ではありませんが、未登録の場合、パスワードを忘れた際の再設定ができなくなります。
                    </p>
                </div>

                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" placeholder="8文字以上推奨" required>
                </div>

                <button type="submit" class="submit-btn">アカウントを作成する</button>
            </form>
            
            <a href="login.php" class="login-link">すでにアカウントをお持ちの方はこちら</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>