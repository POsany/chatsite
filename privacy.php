<?php
session_start();
require_once 'db_connect.php';
$unread_count = 0; // 初期化
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>プライバシーポリシー - GG-SITE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #eef1f5; margin: 0; padding: 0; color: #333; line-height: 1.6; }
        .global-header { background: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .container { max-width: 800px; margin: 30px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        h1 { border-bottom: 2px solid #28a745; padding-bottom: 10px; font-size: 24px; }
        h2 { font-size: 18px; margin-top: 30px; color: #28a745; border-left: 4px solid #28a745; padding-left: 10px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 13px; }
        ul { padding-left: 20px; }
        .important-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

<div class="container">
    <h1>プライバシーポリシー</h1>
    <p>GG-SITE（以下，「当サイト」といいます。）は，本ウェブサイト上で提供するサービス（以下，「本サービス」といいます。）における，ユーザーの個人情報の取扱いについて，以下のとおりプライバシーポリシー（以下，「本ポリシー」といいます。）を定めます。</p>

    <section>
        <h2>第1条（個人情報の収集方法）</h2>
        <p>当サイトは，ユーザーが利用登録をする際にメールアドレス等の個人情報をお尋ねすることがあります。また，ユーザーのIPアドレス，Cookie情報，閲覧したページなどの情報をユーザーのブラウザから自動的に受け取り，サーバーに記録します。</p>
    </section>

    <section>
        <h2>第2条（広告の配信について）</h2>
        <div class="important-box">
            <p>当サイトでは，第三者配信の広告サービス「Googleアドセンス」を利用しています。</p>
            <ul>
                <li>Googleなどの第三者配信事業者は，Cookieを使用して，ユーザーが当サイトや他のウェブサイトに過去にアクセスした際の情報に基づいて広告を配信します。</li>
                <li>Googleが広告Cookieを使用することにより，ユーザーが当サイトや他のサイトにアクセスした際の情報に基づいて，Googleやそのパートナーが適切な広告をユーザーに表示できます。</li>
                <li>ユーザーは，Googleアカウントの<a href="https://adssettings.google.com/authenticated" target="_blank">広告設定</a>で，パーソナライズ広告を無効にできます。</li>
            </ul>
        </div>
    </section>

    <section>
        <h2>第3条（アクセス解析ツールについて）</h2>
        <p>当サイトでは，Googleによるアクセス解析ツール「Googleアナリティクス」を利用しています。このGoogleアナリティクスはトラフィックデータの収集のためにCookieを使用しています。このトラフィックデータは匿名で収集されており，個人を特定するものではありません。</p>
    </section>

    <section>
        <h2>第4条（個人情報を利用する目的）</h2>
        <p>当サイトが個人情報を収集・利用する目的は，以下のとおりです。</p>
        <ul>
            <li>本サービスの提供・運営のため</li>
            <li>ユーザーからのお問い合わせに回答するため</li>
            <li>利用規約に違反したユーザーや，不正・不当な目的でサービスを利用しようとするユーザーを特定し，利用をお断りするため</li>
            <li>ユーザーにご自身の登録情報の閲覧や変更，利用状況の閲覧を行っていただくため</li>
        </ul>
    </section>

    <section>
        <h2>第5条（個人情報の第三者提供）</h2>
        <p>当サイトは，次に掲げる場合を除いて，あらかじめユーザーの同意を得ることなく，第三者に個人情報を提供することはありません。</p>
        <ul>
            <li>法令に基づき開示することが必要である場合</li>
            <li>人の生命，身体または財産の保護のために必要がある場合</li>
        </ul>
    </section>

    <section>
        <h2>第6条（免責事項）</h2>
        <p>当サイトからのリンクやバナーなどで移動したサイトで提供される情報，サービス等について一切の責任を負いません。また，当サイトのコンテンツ・情報について，できる限り正確な情報を提供するよう努めておりますが，正確性や安全性を保証するものではありません。</p>
    </section>
</div>

<div class="footer">
    &copy; 2026 GG-SITE
</div>
<?php include 'footer.php'; ?>
</body>
</html>