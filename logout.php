<?php
// ① まずはセッションを開始（再開）して、現在のセッションを呼び出します
session_start();

// ② セッション変数（$_SESSIONの中身）をすべて空っぽの配列で上書きして消去します
$_SESSION = array();

// ③ サーバー側にあるセッションの仕組みそのものを完全に破壊します
session_destroy();

// ④ ログイン画面へ自動的にジャンプ（リダイレクト）させます
header('Location: login.php');
exit();
?>