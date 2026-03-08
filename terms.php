<?php
session_start();
require_once 'db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;
$unread_count = 0; // 初期化
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>利用規約 - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; line-height: 1.6; }
        .global-header { background: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .container { max-width: 800px; margin: 30px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        h1 { border-bottom: 2px solid #007bff; padding-bottom: 10px; font-size: 24px; }
        h2 { font-size: 18px; margin-top: 30px; color: #007bff; }
        section { margin-bottom: 20px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 13px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <h1>利用規約</h1>
    <p>この利用規約（以下，「本規約」といいます。）は，GG-SITE（以下，「当サイト」といいます。）が提供するサービス（以下，「本サービス」といいます。）の利用条件を定めるものです。</p>

    <section>
        <h2>第1条（適用）</h2>
        <p>本規約は，ユーザーと当サイトとの間の本サービスの利用に関わる一切の関係に適用されるものとします。</p>
    </section>

    <section>
        <h2>第2条（禁止事項）</h2>
        <p>ユーザーは，本サービスの利用にあたり，以下の行為をしてはなりません。</p>
        <ul>
            <li>法令または公序良俗に違反する行為</li>
            <li>犯罪行為に関連する行為</li>
            <li>当サイトのサーバーまたはネットワークの機能を破壊したり，妨害したりする行為</li>
            <li>当サイトのサービスの運営を妨害するおそれのある行為</li>
            <li>他のユーザーに関する個人情報等を収集または蓄積する行為</li>
            <li>他のユーザーに成りすます行為</li>
            <li>当サイトのサービスに関連して，反社会的勢力に対して直接または間接に利益を供与する行為</li>
            <li>その他，当サイトが不適切と判断する行為</li>
        </ul>
    </section>

    <section>
        <h2>第3条（本サービスの提供の停止等）</h2>
        <p>当サイトは，以下のいずれかの事由があると判断した場合，ユーザーに事前に通知することなく本サービスの全部または一部の提供を停止または中断することができるものとします。</p>
        <ul>
            <li>本サービスに係るコンピュータシステムの保守点検または更新を行う場合</li>
            <li>地震，落雷，火災，停電または天災などの不可抗力により，本サービスの提供が困難となった場合</li>
            <li>コンピュータまたは通信回線等が事故により停止した場合</li>
            <li>その他，当サイトが本サービスの提供が困難と判断した場合</li>
        </ul>
    </section>

    <section>
        <h2>第4条（免責事項）</h2>
        <p>当サイトは，本サービスに事実上または法律上の瑕疵（安全性，信頼性，正確性，完全性，有効性，特定の目的への適合性，セキュリティ等に関する欠陥，エラーやバグ，権利侵害等を含みます。）がないことを明示的にも黙示的にも保証しておりません。</p>
    </section>

    <section>
        <h2>第5条（利用規約の変更）</h2>
        <p>当サイトは，必要と判断した場合には，ユーザーに通知することなくいつでも本規約を変更することができるものとします。</p>
    </section>
</div>

<div class="footer">
    &copy; 2026 GG-SITE
</div>
<?php include 'footer.php'; ?>
</body>
</html>