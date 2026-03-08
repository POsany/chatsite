<?php
session_start();
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化
$message_sent = false;

// 未読DMカウント（ヘッダー用基盤）
$unread_count = 0;
if ($user_id) {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM direct_messages WHERE receiver_id = ? AND is_read = 0");
    $stmt_unread->execute([$user_id]);
    $unread_count = $stmt_unread->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_type = $_POST['subject_type'];
    $content = $_POST['content'];

    // データベース保存（emailなし）
    $stmt = $pdo->prepare("INSERT INTO contacts (user_id, subject_type, content) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $subject_type, $content]);

    // 管理者へのメール通知
    $to = "your-email@example.com"; 
    $subject = "【GG-SITE】新しいお問い合わせ（" . $subject_type . "）";
    
    $body = "GG-SITE 管理者様\n\nお問い合わせが届きました。\n\n";
    $body .= "【種別】: $subject_type\n【内容】:\n$content\n\n";
    $body .= "管理画面: https://gg-site.jp/admin.php";

    $headers = "From: info@gg-site.jp";
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    if (mb_send_mail($to, $subject, $body, $headers)) {
        $message_sent = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>お問い合わせ - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        /* 基盤デザインの完全適用 */
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; }
        
        .global-header { 
            background: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
        }
        .global-header h2 { margin: 0; font-size: 22px; color: #222; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .icon-btn { text-decoration: none; color: #555; position: relative; display: flex; align-items: center; font-weight: bold; }
        .unread-badge { position: absolute; top: -5px; right: -8px; background: #dc3545; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px; }

        .container { max-width: 600px; margin: 40px auto; padding: 0 15px; }
        
        /* フォームカード */
        .contact-card { 
            background: white; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            text-align: left;
        }

        .form-group { margin-bottom: 25px; }
        label { display: flex; align-items: center; gap: 8px; font-weight: bold; margin-bottom: 10px; color: #444; }
        
        select, textarea { 
            width: 100%; 
            padding: 15px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            background: #f9f9f9; 
            font-size: 16px; 
            box-sizing: border-box; 
            transition: 0.2s;
        }
        select:focus, textarea:focus { 
            outline: none; 
            border-color: #007bff; 
            background: white; 
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1); 
        }

        textarea { height: 180px; resize: none; line-height: 1.6; }

        .btn-submit { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 16px; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 16px; 
            width: 100%; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 10px; 
            transition: 0.3s;
        }
        .btn-submit:hover { background: #0056b3; transform: translateY(-2px); }

        /* 送信完了画面 */
        .success-area { text-align: center; padding: 40px 0; }
        .success-icon { font-size: 64px; color: #28a745; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <div class="contact-card">
        <?php if ($message_sent): ?>
            <div class="success-area">
                <span class="material-symbols-outlined success-icon">task_alt</span>
                <h2 style="margin:0 0 10px 0;">報告を受け付けました</h2>
                <p style="color:#666; margin-bottom:30px;">ご協力ありがとうございます。<br>運営の参考にさせていただきます。</p>
                <a href="index.php" class="btn-submit" style="text-decoration:none;">トップへ戻る</a>
            </div>
        <?php else: ?>
            <h1 style="margin:0 0 10px 0; font-size:24px;">お問い合わせ・要望</h1>
            <p style="color:#777; margin-bottom:30px; font-size:14px;">不具合の報告や、掲示板作成のリクエストなどをお気軽にお寄せください。</p>

            <form method="POST">
                <div class="form-group">
                    <label><span class="material-symbols-outlined" style="font-size:20px;">label</span> お問い合わせ種別</label>
                    <select name="subject_type">
                        <option value="不具合報告">不具合報告</option>
                        <option value="掲示板作成のリクエスト">掲示板作成のリクエスト</option>
                        <option value="機能改善の要望">機能改善の要望</option>
                        <option value="規約違反の通報">規約違反の通報</option>
                        <option value="その他">その他</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><span class="material-symbols-outlined" style="font-size:20px;">edit_note</span> 内容</label>
                    <textarea name="content" placeholder="こちらに詳細をご記入ください..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <span class="material-symbols-outlined">send</span> 報告を送信する
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>