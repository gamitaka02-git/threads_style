<?php
/**
 * ============================================================
 * Threads Style - Landing Page
 * ============================================================
 * 役割: 商品紹介およびStripe決済への誘導を行うトップページ
 * 構成: Hero / Empathy / Concept / Evidence / Features / Professional / Spec / Closing
 * ============================================================
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads_Style | AIに頼って、でも自分の言葉は渡さない。</title>
    <meta name="description" content="Threadsの投稿、8割はAIに任せていい。残りの2割にあなたらしさを込める。Threads運用・分析支援ツール Threads_Style。">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <meta name="robots" content="noindex">
</head>
<body>

    <!-- ======================================== -->
    <!-- Navigation -->
    <!-- ======================================== -->
    <nav>
        <div class="nav-wrap">
            <div class="site-logo">
                <a href="index.php">Threads_Style</a>
            </div>
            <ul class="menu globalMenuSp">
                <li><a href="#concept">コンセプト</a></li>
                <li><a href="#features">機能紹介</a></li>
                <li><a href="#professional">開発者</a></li>
                <li><a href="#closing">導入する</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- ======================================== -->
    <!-- 1. Hero Section（ヘッダー） -->
    <!-- ======================================== -->
    <header class="header lp-top">
        <div class="header-content">
            <p class="hero-label slide-in-left">Threads運用支援ツール ー<span> Threads_Style </span>ー</p>
            <h1 class="slide-in-left">AIに頼って、<br>でも自分の言葉は<br class="sp">渡さない。</h1>
            <p class="hero-sub slide-in-left">Threadsの投稿、8割はAIに任せていい。<br>
                残りの2割——そこだけに、あなたらしさを込める。<br>
                全部書かなくても、ちゃんと「あなたの投稿」になる。</p>
            <div class="hero-cta slide-in-left">
                <a href="#empathy" class="btn-primary" id="cta-hero">ツールを詳しく見る</a>
            </div>
        </div>
        <!-- <div class="hero-image slide-in-right">
            <img src="img/placeholder2.jpg" alt="Threads_Style ダッシュボード">
        </div> -->
        <div class="header-bg top"></div>
    </header>

    <!-- ======================================== -->
    <!-- 2. Empathy Section（共感） -->
    <!-- ======================================== -->
    <section id="empathy" class="empathy-section">
        <h2 class="section-h2 fade-up">発信に誠実なあなただからこそ、しんどい。</h2>
        <div class="empathy-grid">
            <div class="empathy-card fade-up">
                <p class="empathy-heading">「今日も投稿しなきゃ」——<br>自分でもよくわかってる、でも書けない。</p>
                <p class="empathy-text">毎日続けることへのプレッシャーが、いつの間にか発信そのものを億劫にしてる。</p>
            </div>
            <div class="empathy-card fade-up">
                <p class="empathy-heading">AIに書かせたら、なんか違う。違和感しかない。</p>
                <p class="empathy-text">どこか他人行儀で、自分の声じゃない。結局また書き直しをするから、余計に時間がかかってしまう。</p>
            </div>
            <div class="empathy-card fade-up">
                <p class="empathy-heading">数字は追えても、<br>何が正解かわからない。</p>
                <p class="empathy-text">インサイトを見るたびに疲弊して、「分析のための分析」になっていないか？</p>
            </div>
        </div>
        <p class="section-message fade-up">発信をやめたいわけじゃない。<br>ただ、もう少しだけ——楽にやっていきたいだけ。</p>
    </section>

    <!-- ======================================== -->
    <!-- 3. Concept Section（8:2の黄金比） -->
    <!-- ======================================== -->
    <section id="concept" class="service concept-ai">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2> 8割 — AIがやること</h2>
                </div>
                <p class="slide-in-left">あなたの過去の投稿を読み込み、言葉のクセ・視点・リズムを解析。<br>
                「あなたが考えたような下書き」を、3パターン用意する。</p>
            </div>
             <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2> 2割 — あなたがやること</h2>
                </div>
                <p class="slide-in-right">気に入った下書きに、AIでは汲み取れない感性の<br class="pc">ひと言を加える。その2割が、投稿に体温を宿す。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <p class="page-text concept-ai fade-up">Threadsで人の心を動かすのは、整った文章じゃなく、<br class="pc">あなた自身の「体温」です。<br>
        だからこそ、私はあえて「魂を乗せる」ための2割の余白を残しました。</p>

    <!-- ======================================== -->
    <!-- 4. Evidence Section（自己解析と生成） -->
    <!-- ======================================== -->
    <section id="evidence" class="service evidence-analysis">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2>自己解析機能</h2>
                </div>
                <p class="slide-in-left">AI生成テキストを学習から除外。AIが書いたものをまたAIに学ばせても、<br class="pc">あなたらしさは出てこない。</p>
                <p class="slide-in-left">Threads_Styleが分析するのは、あなた自身が書いた投稿だけです。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <section class="service evidence-guide">
        <div class="text-wrap">
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2>スタイルガイド</h2>
                </div>
                <p class="slide-in-right">「あなたらしさ」を言語化し、AIと共有。分析結果はスタイルガイドとして出力。</p>
                <p class="slide-in-right">あなたの性格、口癖、視点、温度感 ——それらをAIに渡すことで、下書きの精度が<br class="pc">上がり続けます。</p>
            </div>
        </div>
        <div class="service-bg-right"></div>
    </section>

    <!-- 3つの生成パターン -->
    <section class="examples">
        <div class="examples-area">
            <h2 class="fade-up">3つの生成パターン</h2>
            <p class="page-text fade-up">その日の気分に合った叩き台を、すぐに3つ。<br>投稿の目的に合わせて使い分け。</p>
            <div class="examples-wrap">
                <div class="examples-item fade-up">
                    <img src="img/placeholder2.jpg" alt="教育パターン">
                    <p class="pattern-label-text">教育</p>
                    <p>知見や学びを伝える投稿パターン。フォロワーに価値を届けます。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/placeholder.jpg" alt="独り言パターン">
                    <p class="pattern-label-text">独り言</p>
                    <p>日常のつぶやきや思考を投稿パターン。親しみやすさを演出します。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/placeholder2.jpg" alt="交流パターン">
                    <p class="pattern-label-text">交流</p>
                    <p>フォロワーとの対話を促す投稿パターン。エンゲージメントを高めます。</p>
                </div>
            </div>
            <p class="page-text fade-up">下書きを選んで、「あなたの言葉」2割を加えるだけ。それだけです。</p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 5. Feature Section（運用と分析） -->
    <!-- ======================================== -->
    <section id="features" class="service feature-post">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2>スマート投稿 ＆ 予約投稿</h2>
                </div>
                <p class="slide-in-left">画像20枚添付、スレッド投稿、トピック設定まで対応。生成パターンを利用することで、投稿作業そのものの手間を、限界まで削ります。</p>
                <p class="slide-in-left">最適なタイミングに、自動で投稿。Bot判定を防ぐ「ゆらぎ設定」で、自然な投稿リズムを維持します。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <section class="service feature-insight">
        <div class="text-wrap">
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2>インサイト分析 ＆ リパーパス</h2>
                </div>
                <p class="slide-in-right">「伸びた投稿」「よく見られている時間」を自動で検出。何が刺さったかを把握して、ワンタッチで過去の優秀な投稿を再利用できます。</p>
                <p class="slide-in-right">WordPressやnoteに書いたブログ記事を、Threads用に自動変換。すでに書いたコンテンツを、眠らせたままにしない。</p>
            </div>
        </div>
        <div class="service-bg-right"></div>
    </section>

    <!-- ======================================== -->
    <!-- 6. Professional Section（開発者の想い） -->
    <!-- ======================================== -->
    <section id="professional" class="professional-section">
        <div class="professional-wrap">
            <h2 class="fade-up">このツールは、効率化の道具じゃない。</h2>
            <div class="professional-content">
                <p class="fade-up">Web系フリーランス13年、職業訓練の講師として10期にわたって受講生にグラフィック/Web制作を教えてきました。</p>
                <p class="fade-up">教える仕事を続けてきてわかったのは、「速く伝える」より「ちゃんと伝える」ほうがずっと難しいということです。どんなに便利なツールも、使う人が本質を理解していなければ、ただの「操作の暗記」で終わってしまう。</p>
                <p class="fade-up">その経験の中でずっと感じていたのは、「ツールに使われる人」と「ツールを使いこなす人」の差です。</p>
                <p class="fade-up">AIが当たり前になった今、その差はさらに広がっています。</p>
                <p class="fade-up">Threads_Styleを作ったのは、あなたに「ツールを使いこなす側」でいてほしいのと同時に「あなたの言葉で伝えられる人」であってほしいからです。</p>
                <p class="fade-up">投稿の主導権は「あなた」にあり、AIはあくまで、あなたの発信を支える頼れる右腕。</p>
            </div>
            <p class="pro-message fade-up">このツールは、あなたの「発信者としてのスタイル」を守るための盾です。</p>
        </div>
        <div class="bg-filter"></div>
    </section>

    <!-- ======================================== -->
    <!-- 7. Spec Section（安心の仕様） -->
    <!-- ======================================== -->
    <section id="spec" class="examples">
        <div class="examples-area">
            <h2 class="fade-up">面倒なことは、全部こちらでやります。</h2>
            <p class="page-text fade-up">安心して使い続けるための機能を揃えました。</p>
            <div class="examples-wrap">
                <div class="examples-item fade-up">
                    <img src="img/placeholder2.jpg" alt="簡単アップデート">
                    <p class="pattern-label-text">🚀 簡単アップデート</p>
                    <p>ボタンひとつで最新版へ。難しい作業は不要。常に最新の状態を保てます。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/placeholder.jpg" alt="ライセンス管理">
                    <p class="pattern-label-text">🔐 ライセンス管理</p>
                    <p>Stripe連携による安心の認証。購入から利用開始まで、シームレスに。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/placeholder2.jpg" alt="トークン管理">
                    <p class="pattern-label-text">🔑 トークン管理</p>
                    <p>Meta APIのトークン更新を自動化。有効期限の管理も、残り日数の確認も、表示はしているけど気にしなくてもいいんです。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 8. Closing Section -->
    <!-- ======================================== -->
    <section id="closing" class="closing-section">
        <div class="closing-wrap">
            <h2 class="fade-up">白紙の画面に、悩まなくていい。</h2>
            <p class="fade-up">投稿のネタを絞り出す時間も、<br>
                AIの文章を読んで「なんか違う」と感じる時間も、<br>
                もう終わりにしていい。</p>
            <p class="fade-up">あなたがやることは、2割だけ。<br>
                でもその2割があなたの「体温」となり、<br>
                あなたの発信を「あなたのもの」にする。</p>
            <p class="closing-accent fade-up">「自分の言葉」を、最後の一筆に。</p>
            <div class="cta-card fade-up">
                <div class="price-tag">¥5,980 <span>(税込・買い切り)</span></div>
                <a href="checkout.php" class="btn-primary btn-buy" id="cta-closing">今すぐThreads_Styleを導入する</a>
                <p class="cta-note">Stripeによる安全な決済がご利用いただけます</p>
            </div>
        </div>
        <div class="bg-filter"></div>
    </section>

    <!-- ======================================== -->
    <!-- Footer -->
    <!-- ======================================== -->
    <footer>
        <div class="footer-links">
            <a href="terms.php">利用規約</a>
            <a href="privacy.php">プライバシーポリシー</a>
            <a href="law.php">特定商取引法に基づく表記</a>
        </div>
        <div class="copyright">
            <small>&copy; 2026 Threads_Style</small>
        </div>
    </footer>

    <div id="page_top">
        <a class="pc-pagetop" href="#">Top</a>
    </div>

</body>
</html>
