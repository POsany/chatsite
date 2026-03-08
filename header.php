<style>
    /* サイト共通：ヘッダーのデザイン基盤 */
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

    /* ロゴアニメーションの基盤 */
    .gg-logo-parent { text-decoration: none; display: flex; align-items: center; height: 40px; }
    .icon-box { transform-origin: 20px 20px; transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .logo-text { transition: fill 0.3s ease, transform 0.3s ease; }
    .underline-rect { transition: width 0.5s ease-in-out; }
    
    /* Hover時の挙動 */
    .gg-logo-parent:hover .icon-box { transform: rotate(360deg); }
    .gg-logo-parent:hover .logo-text { fill: #007bff; transform: translateX(2px); }
    .gg-logo-parent:hover .underline-rect { width: 100px; }

    /* ナビゲーション周り */
    .nav-right { display: flex; align-items: center; gap: 20px; }
    .icon-btn { text-decoration: none; color: #555; position: relative; display: flex; align-items: center; font-weight: bold; }
    .unread-badge { 
        position: absolute; top: -5px; right: -5px; 
        background: #dc3545; color: white; 
        font-size: 10px; padding: 2px 5px; 
        border-radius: 10px; 
        animation: pulse 2s infinite; /* 通知がある時に少しだけ脈打たせる */
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>

<header class="global-header">
    <a href="index.php" class="gg-logo-parent">
        <svg width="180" height="40" viewBox="0 0 200 40" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <clipPath id="clip-underline">
              <rect x="45" y="32" width="0" height="2" class="underline-rect"/>
            </clipPath>
          </defs>
          <g class="icon-box">
            <rect x="5" y="5" width="30" height="30" rx="6" fill="#007bff"/>
            <text x="11" y="27" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="white">G</text>
          </g>
          <g class="logo-text">
            <text x="45" y="28" font-family="Verdana, sans-serif" font-weight="bold" font-size="20" letter-spacing="1" fill="#333">GG-SITE</text>
          </g>
          <rect x="45" y="32" width="100" height="2" fill="#007bff" opacity="0.1"/>
          <rect x="45" y="32" width="100" height="2" fill="#007bff" clip-path="url(#clip-underline)"/>
        </svg>
    </a>

    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dm_list.php" class="icon-btn">
                <span class="material-symbols-outlined" style="font-size:28px;">mail</span>
                <?php if(isset($unread_count) && $unread_count > 0): ?>
                    <span class="unread-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <a href="mypage.php" class="icon-btn" style="color:#007bff;">マイページ</a>
            <a href="logout.php" style="color:#dc3545; text-decoration:none; font-size:12px; margin-left:10px;">ログアウト</a>
        <?php else: ?>
            <a href="login.php" class="icon-btn" style="color:#007bff; font-size: 14px;">ログイン / 新規登録</a>
        <?php endif; ?>
    </div>
</header>