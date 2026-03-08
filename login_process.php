<?php
session_start();
require_once 'db_connect.php';

$login_id = $_POST['login_id'];
$password = $_POST['password'];

try {
    // ▼ プレースホルダの名前を2つ別々に分けました！
    $sql = "SELECT * FROM users WHERE userid = :userid OR email = :email";
    $stmt = $pdo->prepare($sql);
    
    // ▼ 2つのプレースホルダに対して、それぞれ入力された文字（$login_id）をセットします
    $stmt->bindValue(':userid', $login_id, PDO::PARAM_STR);
    $stmt->bindValue(':email', $login_id, PDO::PARAM_STR);
    
    $stmt->execute();
    
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        // ログイン成功
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nickname'] = $user['nickname'];
        
        // 掲示板メイン画面へジャンプ
        header('Location: index.php');
        exit();
        
    } else {
        echo "ユーザーID、メールアドレス、またはパスワードが間違っています。";
        echo '<br><a href="login.php">戻る</a>';
    }

} catch (PDOException $e) {
    echo "エラーが発生しました: " . $e->getMessage();
}
?>