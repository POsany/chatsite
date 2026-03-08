<?php
session_start();
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;

// 1. 未読DMカウント（header.php用）
$unread_count = 0;
if ($user_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$user_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

// 2. 最強トレンドロジック
$popular_posts = $pdo->query("
    SELECT posts.*, users.nickname, boards.title AS board_title,
    DATEDIFF(NOW(), posts.created_at) as days_ago,
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as like_count
    FROM posts 
    INNER JOIN users ON posts.user_id = users.id 
    INNER JOIN boards ON posts.board_id = boards.id
    WHERE posts.created_at >= (NOW() - INTERVAL 30 DAY)
    HAVING like_count >= 1 
    ORDER BY like_count DESC LIMIT 5
")->fetchAll();

// 3. プラットフォーム定義（カラー・グラデーションを追加）
$platforms = [
    'App'     => ['name' => 'スマホ',   'icon' => 'smartphone',    'color' => 'linear-gradient(135deg, #42a5f5, #1e88e5)'],
    'Switch'  => ['name' => 'Switch',   'icon' => 'videogame_asset', 'color' => 'linear-gradient(135deg, #ff4b2b, #ff416c)'],
    'PS5'     => ['name' => 'PS5',      'icon' => 'sports_esports', 'color' => 'linear-gradient(135deg, #003087, #0072ce)'],
    'PC'      => ['name' => 'PC',       'icon' => 'computer',      'color' => 'linear-gradient(135deg, #232526, #414345)'],
    'Switch2' => ['name' => 'Switch2',  'icon' => 'nest_display_max','color' => 'linear-gradient(135deg, #f093fb, #f5576c)'],
    'Multi'   => ['name' => 'マルチ',   'icon' => 'layers',        'color' => 'linear-gradient(135deg, #84fab0, #8fd3f4)']
];

// 4. 掲示板仕分け
$boards_by_platform = [];
foreach ($platforms as $key => $val) { $boards_by_platform[$key] = []; }
$all_boards = $pdo->query("SELECT * FROM boards ORDER BY id ASC")->fetchAll();
foreach ($all_boards as $b) {
    $pf = (isset($b['platform']) && array_key_exists($b['platform'], $platforms)) ? $b['platform'] : 'Multi';
    $boards_by_platform[$pf][] = $b;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>GG-SITE | 総合ゲーム掲示板ポータル</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { font-family: "Helvetica Neue", Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 15px; }

        /* --- トレンドセクション --- */
        .trend-section { margin-bottom: 40px; }
        .popular-container { display: flex; gap: 18px; overflow-x: auto; padding: 15px 5px; scrollbar-width: none; }
        .popular-container::-webkit-scrollbar { display: none; }
        
        .popular-card { 
            min-width: 300px; background: white; padding: 20px; border-radius: 16px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); text-decoration: none; color: inherit;
            position: relative; border-top: 5px solid #ff4757; transition: 0.3s;
            display: flex; flex-direction: column;
        }
        .popular-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.12); }
        .rank-badge { 
            position: absolute; top: -12px; left: -10px; background: #ff4757; color: white; 
            width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .days-badge { font-size: 10px; background: #f0f2f5; color: #666; padding: 2px 8px; border-radius: 10px; }
        .card-body { display: flex; gap: 12px; margin: 10px 0; }
        .card-text { flex: 1; font-size: 14px; line-height: 1.5; height: 4.5em; overflow: hidden; color: #333; }
        .card-thumb { width: 70px; height: 70px; border-radius: 10px; object-fit: cover; background: #eee; }

        /* --- プラットフォーム別タブ --- */
        .pf-tabs { display: flex; gap: 8px; margin-bottom: 25px; border-bottom: 2px solid #ddd; overflow-x: auto; }
        .pf-tab { 
            display: flex; align-items: center; gap: 8px; padding: 12px 24px; cursor: pointer;
            background: #e4e6eb; border-radius: 12px 12px 0 0; font-weight: bold; border: none; transition: 0.2s;
        }
        .pf-tab.active { background: #007bff; color: white; }
        
        /* --- ボードアイテム（タイル進化版） --- */
        .board-grid { display: none; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; }
        .board-grid.active { display: grid; }
        .board-item { 
            background: white; border-radius: 20px; padding: 20px; text-decoration: none; color: inherit; 
            border: 1px solid #eee; transition: 0.3s; text-align: center;
            display: flex; flex-direction: column; align-items: center;
        }
        .board-item:hover { border-color: #007bff; transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        /* 動的グラデーションアイコン */
        .board-icon-gen {
            width: 70px; height: 70px; border-radius: 20px; margin-bottom: 15px;
            display: flex; align-items: center; justify-content: center; 
            color: white; font-size: 30px; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .board-item h4 { margin: 0; font-size: 14px; color: #333; line-height: 1.4; font-weight: 600; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    
    <section class="trend-section">
        <h3 style="display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:#ff4757; font-variation-settings:'FILL' 1;">local_fire_department</span> 
            トレンド TOP5
        </h3>
        <div class="popular-container">
            <?php if(!empty($popular_posts)): ?>
                <?php $rank = 1; foreach ($popular_posts as $p): ?>
                    <a href="post_detail.php?id=<?= $p['id'] ?>" class="popular-card">
                        <div class="rank-badge"><?= $rank++ ?></div>
                        
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:11px; color:#ff4757; font-weight:bold;"># <?= htmlspecialchars($p['board_title']) ?></span>
                            <span class="days-badge"><?= $p['days_ago'] == 0 ? '今日' : $p['days_ago'].'日前' ?></span>
                        </div>

                        <div class="card-body">
                            <div class="card-text"><?= htmlspecialchars($p['message']) ?></div>
                            <?php if(!empty($p['image_path'])): ?>
                                <img src="<?= htmlspecialchars($p['image_path']) ?>" class="card-thumb" alt="thumb">
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#888; margin-top:auto;">
                            <span>👤 <?= htmlspecialchars($p['nickname']) ?></span>
                            <span style="color:#ff4757; font-weight:bold;">❤️ <?= $p['like_count'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999; padding:20px;">最近の注目の投稿はまだありません。</p>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h3 style="border-left: 6px solid #007bff; padding-left: 15px; margin-bottom: 25px;">ハードから探す</h3>
        
        <div class="pf-tabs">
            <?php $first = true; foreach ($platforms as $key => $val): ?>
                <button class="pf-tab <?= $first ? 'active' : '' ?>" onclick="showPlatform('<?= $key ?>', this)">
                    <span class="material-symbols-outlined"><?= $val['icon'] ?></span>
                    <?= $val['name'] ?>
                    <span style="font-size:11px; opacity:0.6; margin-left:4px;">(<?= count($boards_by_platform[$key]) ?>)</span>
                </button>
            <?php $first = false; endforeach; ?>
        </div>

        <?php $first = true; foreach ($platforms as $key => $val): ?>
            <div class="board-grid <?= $first ? 'active' : '' ?>" id="grid-<?= $key ?>">
                <?php foreach ($boards_by_platform[$key] as $board): ?>
                    <a href="board.php?id=<?= $board['id'] ?>" class="board-item">
                        <div class="board-icon-gen" style="background: <?= $platforms[$key]['color'] ?>;">
                            <?= mb_substr($board['title'], 0, 1) ?>
                        </div>
                        <h4><?= htmlspecialchars($board['title']) ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php $first = false; endforeach; ?>
    </section>

</div>

<?php include 'footer.php'; ?>

<script>
function showPlatform(key, btn) {
    document.querySelectorAll('.pf-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.board-grid').forEach(g => g.classList.remove('active'));
    const target = document.getElementById('grid-' + key);
    if(target) target.classList.add('active');
}
</script>

</body>
</html>