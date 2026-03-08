<?php
// ① データベースの接続情報（XAMPPの初期設定）
$host   = 'localhost'; // データベースがある場所
$dbname = 'bbs_db';    // 先ほど作ったデータベース名
$user   = 'root';      // XAMPPの初期ユーザー名
$pass   = '';          // XAMPPの初期パスワード（空欄でOKです）

// ② 接続するための文字列（DSN）を組み立てる
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

try {
    // ③ PDOを使って実際に接続する
    $pdo = new PDO($dsn, $user, $pass, [
        // エラーが起きたら詳細を表示する設定
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // データを扱いやすい形式（連想配列）で取得する設定
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // SQLインジェクション（サイバー攻撃）をより強固に防ぐ設定
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // ▼ テスト用：接続に成功したらメッセージを表示（確認が終わったら // をつけて無効化します）
    echo "データベースの接続に成功しました！";

} catch (PDOException $e) {
    // ④ もし接続に失敗した場合は、エラーメッセージを表示して処理を止める
    echo "データベース接続失敗: " . $e->getMessage();
    exit;
}
?>