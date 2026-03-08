<?php
// ① データベース接続ファイルを読み込む
require_once 'db_connect.php';

// ② 登録画面から送られてきたデータを受け取る
$userid = $_POST['userid'];
$nickname = $_POST['nickname'];
$password = $_POST['password'];

// ③ 【超重要】パスワードをハッシュ化（暗号化）する
// PASSWORD_DEFAULT を指定すると、PHPが自動的に最も強力なアルゴリズムで暗号化してくれます
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // ④ データベースに新しいユーザーを追加（INSERT）する準備
    $sql = "INSERT INTO users (userid, password, nickname) VALUES (:userid, :password, :nickname)";
    $stmt = $pdo->prepare($sql);
    
    // ⑤ プレースホルダに実際の値をセットする（SQLインジェクション対策）
    $stmt->bindValue(':userid', $userid, PDO::PARAM_STR);
    $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR); // ★生のパスワードではなく、ハッシュ化したものを保存！
    $stmt->bindValue(':nickname', $nickname, PDO::PARAM_STR);
    
    // ⑥ 実行！
    $stmt->execute();
    
    // 登録成功時のメッセージと、ログイン画面へのリンクを表示
    echo "<h1>会員登録が完了しました！</h1>";
    echo "<p>ようこそ、" . htmlspecialchars($nickname) . "さん。さっそくログインしてみましょう。</p>";
    echo '<a href="login.php">ログイン画面へ進む</a>';

} catch (PDOException $e) {
    // ⑦ エラー処理：ユーザーIDがすでに使われている場合（UNIQUE制約違反）
    // MySQLでデータ重複が起きたときのエラーコードは「23000」になります
    if ($e->getCode() == 23000) {
        echo "<h1>登録エラー</h1>";
        echo "<p>申し訳ありませんが、そのユーザーIDはすでに使われています。別のIDをお試しください。</p>";
    } else {
        echo "<h1>システムエラー</h1>";
        echo "<p>エラーが発生しました: " . $e->getMessage() . "</p>";
    }
    echo '<a href="register.php">登録画面に戻る</a>';
}
?>