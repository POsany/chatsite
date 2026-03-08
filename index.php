<?php
session_start();
require_once 'db_connect.php';

// 基盤：ゲスト対応
$my_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化

// 未読DMカウント
$unread_count = 0;
if ($my_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$my_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

// カテゴリーリスト
$categories = ['雑談', '日記', '質問', '攻略', '募集', 'バグ・エラー'];
$selected_category = $_GET['cat'] ?? '';
$search_word = $_GET['search'] ?? '';

$is_searching = ($selected_category !== '' || $search_word !== '');

if ($is_searching) {
    // 【横断検索モード】
    $where_clauses = [];
    $params = [];
    if ($selected_category !== '') {
        $where_clauses[] = "posts.category = :category";
        $params[':category'] = $selected_category;
    }
    if ($search_word !== '') {
        $where_clauses[] = "posts.message LIKE :search";
        $params[':search'] = '%' . $search_word . '%';
    }
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    
    $sql = "SELECT posts.*, users.nickname, users.profile_image, boards.title as board_title,
            (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count
            FROM posts 
            JOIN users ON posts.user_id = users.id 
            JOIN boards ON posts.board_id = boards.id
            $where_sql 
            ORDER BY posts.created_at DESC LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll();
} else {
    // 【通常モード：トレンド30日間 ＆ 経過日数表示】
    $popular_posts = $pdo->query("
        SELECT posts.*, users.nickname, boards.title AS board_title,
        DATEDIFF(NOW(), posts.created_at) as days_ago,
        (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        JOIN boards ON posts.board_id = boards.id
        WHERE posts.created_at >= (NOW() - INTERVAL 30 DAY)
        HAVING like_count >= 5
        ORDER BY like_count DESC LIMIT 5
    ")->fetchAll();

    if (count($popular_posts) < 3) {
        $popular_posts = $pdo->query("
            SELECT posts.*, users.nickname, boards.title AS board_title,
            DATEDIFF(NOW(), posts.created_at) as days_ago,
            (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count
            FROM posts 
            JOIN users ON posts.user_id = users.id 
            JOIN boards ON posts.board_id = boards.id
            ORDER BY like_count DESC LIMIT 5
        ")->fetchAll();
    }

    $boards = $pdo->query("SELECT * FROM boards ORDER BY id ASC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>GG-SITE | ゲーマーのための総合掲示板</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; color: #333; }
        .container { max-width: 800px; margin: 20px auto; padding: 0 15px; }
        
        /* 統一グローバルヘッダー */
        .global-header { 
            background: white; 
            padding: 12px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }

        /* ロゴアニメーション用CSS */
        .gg-logo-parent { text-decoration: none; display: flex; align-items: center; height: 40px; }
        .icon-box { transform-origin: 20px 20px; transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .logo-text { transition: fill 0.3s ease, transform 0.3s ease; }
        .underline-rect { transition: width 0.5s ease-in-out; }
        .gg-logo-parent:hover .icon-box { transform: rotate(360deg); }
        .gg-logo-parent:hover .logo-text { fill: #007bff; transform: translateX(2px); }
        .gg-logo-parent:hover .underline-rect { width: 100px; }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { text-decoration: none; color: #555; position: relative; display: flex; align-items: center; font-weight: bold; }
        .unread-badge { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px; }

        /* 検索・トレンド・掲示板スタイル（以前の基盤を維持） */
        .search-area { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .search-form { display: flex; gap: 10px; }
        .search-form select, .search-form input { padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .search-form input { flex-grow: 1; }
        .btn-search { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
        .post-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-decoration: none; color: inherit; display: block; transition: 0.2s; border-left: 5px solid #28a745; }
        .board-tag { background: #007bff; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        
        .popular-container { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 15px; margin-bottom: 30px; }
        .popular-card { min-width: 280px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-decoration: none; color: inherit; border-top: 5px solid #ff4757; position: relative; }
        .rank-badge { position: absolute; top: -10px; left: -10px; background: #ff4757; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; z-index: 10; }
        .popular-content { display: flex; gap: 10px; margin-top: 5px; }
        .popular-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; background: #eee; }
        .days-badge { font-size: 10px; background: #eee; color: #666; padding: 1px 6px; border-radius: 10px; }

        .board-list { display: grid; gap: 15px; }
        .board-card { background: white; padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 20px; text-decoration: none; color: inherit; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 6px solid #007bff; transition: 0.2s; }
        .board-card:hover { background: #f8f9fa; transform: translateX(5px); }
        .board-img-icon { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="search-area">
        <form class="search-form" method="GET" action="index.php">
            <select name="cat">
                <option value="">すべてのジャンル</option>
                <?php foreach($categories as $c): ?><option value="<?= $c ?>" <?= $selected_category===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
            </select>
            <input type="text" name="search" placeholder="全掲示板からキーワード検索..." value="<?= htmlspecialchars($search_word) ?>">
            <button type="submit" class="btn-search">検索</button>
        </form>
    </div>

    <?php if ($is_searching): ?>
        <h3 style="margin-left:5px;">🔍 検索結果: <?= count($search_results) ?> 件</h3>
        <?php foreach($search_results as $p): ?>
            <a href="post_detail.php?id=<?= $p['id'] ?>" class="post-card">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <div><span class="board-tag">📍 <?= htmlspecialchars($p['board_title']) ?></span></div>
                    <small style="color:#999;"><?= $p['created_at'] ?></small>
                </div>
                <p style="line-height:1.6;"><?= mb_strimwidth(htmlspecialchars($p['message']), 0, 160, "...") ?></p>
                <strong>👤 <?= htmlspecialchars($p['nickname']) ?></strong> <span style="color:#ff4757; margin-left:10px;">❤️ <?= $p['like_count'] ?></span>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <h3 style="color:#333; margin-left:5px;">🔥 トレンド（過去30日間）</h3>
        <div class="popular-container">
            <?php $rank = 1; foreach ($popular_posts as $p): ?>
                <a href="post_detail.php?id=<?= $p['id'] ?>" class="popular-card">
                    <div class="rank-badge"><?= $rank++ ?></div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <div style="font-size:11px; color:#ff4757; font-weight:bold;"># <?= htmlspecialchars($p['board_title']) ?></div>
                        <span class="days-badge"><?= $p['days_ago'] == 0 ? '今日' : $p['days_ago'] . '日前' ?></span>
                    </div>

                    <div class="popular-content">
                        <div style="flex:1;">
                            <p style="margin:0; font-size:13px; height:3.9em; overflow:hidden; line-height:1.3; color:#444;">
                                <?= htmlspecialchars($p['message']) ?>
                            </p>
                        </div>
                        <?php if (!empty($p['image_path'])): ?>
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" class="popular-thumb">
                        <?php endif; ?>
                    </div>

                    <div style="margin-top:10px; font-size:12px; color:#888;">❤️ <?= $p['like_count'] ?> | <?= htmlspecialchars($p['nickname']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>

        <h3 style="color:#333; margin-left:5px;">📚 カテゴリ一覧</h3>
        <div class="board-list">
            <?php foreach ($boards as $board): ?>
                <a href="board.php?id=<?= $board['id'] ?>" class="board-card">
                    <?php if ($board['board_icon']): ?><img src="<?= htmlspecialchars($board['board_icon']) ?>" class="board-img-icon"><?php else: ?>
                    <div style="width:60px; height:60px; background:#eee; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:30px;">🎮</div><?php endif; ?>
                    <div>
                        <h3 style="margin:0 0 5px 0; font-size:18px; color:#222;"><?= htmlspecialchars($board['title']) ?></h3>
                        <p style="margin:0; font-size:13px; color:#666;"><?= htmlspecialchars($board['description']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
</body>
</html>