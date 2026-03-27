<!DOCTYPE html>
<!--
============================================================
決済完了ページ
============================================================
役割: Stripe Checkout での決済が成功した後にリダイレクトされるページ。
      購入者にライセンスキーがメール送信されることを案内する。

【カスタマイズ箇所】
- YOUR_APP_NAME: ツール名に書き換える
============================================================
-->
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ご購入ありがとうございます</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background-color: #f9f9f9; }
        .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h1 { color: #28a745; }
        p { line-height: 1.6; color: #333; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>ご購入ありがとうございます！</h1>
        <p>決済が正常に完了しました。<br>
        ご登録いただいたメールアドレス宛に、システムから<strong>「ライセンスキー」</strong>に関するご案内をお送りいたします。</p>
        <p>ツールのダウンロードおよび初期設定の手順については、メールの案内をご確認ください。</p>
        <a href="index.php" class="btn">トップページへ戻る</a>
    </div>
</body>
</html>
