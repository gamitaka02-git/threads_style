<?php
/**
 * ============================================================
 * Threads Style - Landing Page
 * ============================================================
 * 役割: 商品紹介およびStripe決済への誘導を行うトップページ
 * 構成: Hero / Empathy / Concept / CTA① / Evidence / BrandProtect / Features / Liberation / Growth / Professional / Spec / EasyStart / Closing
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
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;700&family=Shippori+Mincho:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <link rel="icon" href="img/favicon.png" type="image/png">
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
                <li class="menu-cta"><a href="checkout.php" class="btn-primary" id="cta-menu">今すぐ導入する</a></li>
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
            <p class="hero-label slide-in-left">Threads運用支援ツール<br class="sp"> ー<span> Threads_Style </span>ー</p>
            <h1 class="slide-in-left">AIに頼って、<br>でも自分の言葉は<br class="sp">渡さない。</h1>
            <p class="hero-sub slide-in-left">Threadsの投稿、8割はAIに任せていい。<br>
                残りの2割——<br class="sp">そこだけに、あなたらしさを込める。<br>
                全部書かなくても、<br class="sp">ちゃんと「あなたの投稿」になる。</p>
            <div class="hero-cta slide-in-left">
                <a href="checkout.php" class="btn-primary btn-buy" id="cta-hero">今すぐThreads_Styleを導入する</a>
                <p class="hero-cta-note">¥5,980（税込・買い切り）</p>
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
        <h2 class="section-h2 fade-up">発信に誠実なあなただから<br class="sp">こそ、しんどい。</h2>
        <div class="empathy-grid">
            <div class="empathy-card fade-up">
                <p class="empathy-heading">「今日も投稿しなきゃ」——<br>自分でもよくわかってる、<br>…でも書けない。</p>
                <p class="empathy-text">毎日続けることへのプレッシャーが、<br class="pc">いつの間にか発信そのものを億劫にしていませんか？</p>
            </div>
            <div class="empathy-card fade-up">
                <p class="empathy-heading">AIに書かせたら、なんか違う。<br>違和感しかない。</p>
                <p class="empathy-text">どこか他人行儀で、自分の声じゃない。結局また書き直しをするから、余計に時間がかかっていませんか？</p>
            </div>
            <div class="empathy-card fade-up">
                <p class="empathy-heading">数字は追えても、<br>何が正解かわからない。</p>
                <p class="empathy-text">インサイトを見るたびに疲弊して、「分析のための分析」になっていませんか？</p>
            </div>
        </div>
        <p class="section-message fade-up">発信をやめたいわけじゃない。<br>ただ、もう少しだけ——<br class="sp">楽にやっていきたいだけ。</p>
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
                <p class="slide-in-left">あなたの過去の投稿を読み込み、<br class="sp">言葉のクセ・視点・リズムを解析。<br>
                    「あなたが考えたような下書き」を、<br class="sp">3パターン用意する。</p>
            </div>
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2> 2割 — あなたがやること</h2>
                </div>
                <p class="slide-in-right">気に入った下書きに、AIでは汲み取れない感性の<br class="pc">ひと言を加える。<br class="sp">その2割が、投稿に体温を宿す。
                </p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <p class="page-text concept-ai fade-up">Threadsで人の心を動かすのは、整った文章じゃなく、<br class="pc">あなた自身の「体温」です。<br>
        だからこそ、私はあえて「魂を乗せる」ための<br class="sp">2割の余白を残しました。</p>

    <div class="concept-detail fade-up">
        <p>「8:2の法則」——パレートの法則とも呼ばれるこの原則は、<br class="pc">「全体の成果の8割は、2割の要素が生み出している」という考え方です。</p>
        <p>発信においても同じことが言えます。<br>
            構成、リサーチ、下書き——これらは全体の8割を占める作業ですが、<br class="pc">読み手の心に残るのは、あなた自身の視点や感情が宿った「最後の2割」です。</p>
        <p class="concept-detail-accent">だからこそ、<br class="sp"><span>その2割に全力を注いでください。</span><br>
            8割の土台は<br class="sp">Threads_Styleが引き受けます。</p>
        <p class="concept-detail-accent">あなたは、<br class="sp"><span>あなたにしか書けない言葉</span><br class="sp">だけに集中してください。</p>
    </div>

    <!-- CTA Section（中間） -->
    <div class="mid-cta-section">
        <div class="mid-cta-wrap fade-up">
            <p class="mid-cta-lead">全部書かなくていい。<br>あなたらしさは、<br class="sp">2割で十分伝わる。</p>
            <a href="checkout.php" class="btn-primary btn-buy" id="cta-mid">今すぐThreads_Styleを導入する</a>
            <p class="mid-cta-note">¥5,980（税込・買い切り）<br class="sp">/ Stripeによる安全な決済</p>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- 4. Evidence Section（自己解析と生成） -->
    <!-- ======================================== -->
    <section id="evidence" class="service evidence-analysis">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2>自己解析機能</h2>
                </div>
                <p class="slide-in-left">過去の投稿から自己解析する際、AI生成テキストを学習対象から除外。<br>AIが書いたものをまたAIに学ばせても、あなたらしさは出てこない。</p>
                <p class="slide-in-left">Threads_Styleが分析するのは、<br class="sp">あなた自身が書いた投稿だけです。</p>
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
                <p class="slide-in-right">「あなたらしさ」を言語化し、AIと共有。分析結果はスタイルガイドとして<br class="sp">出力。</p>
                <p class="slide-in-right">あなたの性格、口癖、視点、温度感 ——それらをAIに渡すことで、<br class="sp">下書きの精度が<br class="pc">上がり続けます。
                </p>
            </div>
        </div>
        <div class="service-bg-right"></div>
    </section>

    <!-- 3つの生成パターン -->
    <section class="examples">
        <div class="examples-area">
            <h2 class="fade-up">3つの生成パターン</h2>
            <p class="page-text fade-up">その日の気分に合った<br class="sp">叩き台を、すぐに3つ。<br>投稿の目的に合わせて使い分け。</p>
            <div class="examples-wrap">
                <div class="examples-item fade-up">
                    <img src="img/img-01.jpg" alt="教育パターン">
                    <p class="pattern-label-text">教育</p>
                    <p>知見や学びを伝える投稿パターン。<br>フォロワーに価値を届けます。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/img-02.jpg" alt="独り言パターン">
                    <p class="pattern-label-text">独り言</p>
                    <p>日常のつぶやきや思考を投稿パターン。<br>親しみやすさを演出します。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/img-03.jpg" alt="交流パターン">
                    <p class="pattern-label-text">交流</p>
                    <p>フォロワーとの対話を促す投稿パターン。<br>エンゲージメントを高めます。</p>
                </div>
            </div>
            <p class="page-text fade-up">下書きを選んで、<br class="sp">「あなたの言葉」2割を加えるだけ。<br class="sp">それだけです。</p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 4.5 Brand Protection Section（ブランド保護）【追加①】 -->
    <!-- ======================================== -->
    <section id="brand-protect" class="brand-protect-section">
        <div class="brand-protect-wrap">
            <div><h2 class="section-h2 fade-up">あなたのスタイルは、<br class="sp">誰とも混ざらない。</h2></div>
            <p class="brand-protect-lead fade-up">「AIで書いたら、<br class="sp">みんな同じ文章になるんじゃ？」<br><br>
                ——その不安は、もっともです。</p>
            <div class="brand-protect-grid">
                <div class="brand-protect-card fade-up">
                    <p class="brand-protect-heading">学習データは、あなただけのもの</p>
                    <p class="brand-protect-text">Threads_Styleが分析するのは、あなた自身が書いた投稿だけ。<br>AIの書いたデータや他人のデータが学習に混ざることはありません。</p>
                </div>
                <div class="brand-protect-card fade-up">
                    <p class="brand-protect-heading">スタイルガイドが、個性の盾になる</p>
                    <p class="brand-protect-text">
                        あなたの口癖、語尾のクセ、思考のリズム——それを言語化した「スタイルガイド」が、AIの出力を常に<strong>あなたらしさ</strong>へ引き戻します。</p>
                </div>
                <div class="brand-protect-card fade-up">
                    <p class="brand-protect-heading">同じツールでも、同じ文章にはならない</p>
                    <p class="brand-protect-text">
                        同じツールでも10人が使えば、10通りの文体が生まれる。<br>テンプレートではなく、<strong>あなた専用のAIライター</strong>が育つ仕組みです。</p>
                </div>
            </div>
            <p class="brand-protect-bottom fade-up">便利さと引き換えに、個性を手放す必要はありません。<br
                    class="pc">Threads_Styleは、<strong>「あなたらしさ」を守るためのツール</strong>です。</p>
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
                <p class="slide-in-left">ツールから即時投稿、画像20枚添付、トピック設定まで対応。生成パターンを利用することで、投稿作業そのものの手間を、限界まで削ります。</p>
                <p class="slide-in-left">予め自分で設定した日時に自動で投稿。Bot判定を防ぐ「ゆらぎ設定」で、自然な投稿リズムを維持します。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <section class="service feature-insight">
        <div class="text-wrap">
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2>インサイト分析 <br class="sp">＆ リパーパス</h2>
                </div>
                <p class="slide-in-right">「伸びた投稿」「反応が多かった時間」を自動で検出。何が刺さったかを把握して、ワンタッチで過去の優秀な投稿を再利用できます。</p>
                <p class="slide-in-right">WordPressやnoteに書いたブログ記事を、Threads用に自動変換。<br class="sp">すでに書いたコンテンツを、眠らせたままにしない。
                </p>
            </div>
        </div>
        <div class="service-bg-right"></div>
    </section>

    <!-- ======================================== -->
    <!-- 5.5 Liberation Section（解放訴求）【更新②】 -->
    <!-- ======================================== -->
    <section id="liberation" class="liberation-section">
        <div class="liberation-wrap">
            <h2 class="section-h2 fade-up">今月分、もう終わった。</h2>
            <p class="liberation-lead fade-up">「1投稿を○分短縮」なんて話じゃない。<br>
                <strong>30分あれば、20投稿分いや、<br class="sp">もっと多くの予約が完了する。</strong><br>
                今週の発信も、来週の発信も——<br class="sp">もう、終わっている。
            </p>
            <div class="liberation-visual fade-up">
                <div class="liberation-before">
                    <p class="liberation-label">Before</p>
                    <p class="liberation-time">毎日30分 × 30日</p>
                    <p class="liberation-desc">ネタ探し、下書き、推敲、投稿…<br>毎日「書かなきゃ」に追われる日々</p>
                </div>
                <div class="liberation-arrow">→</div>
                <div class="liberation-after">
                    <p class="liberation-label">After</p>
                    <p class="liberation-time">月2回 × 30分</p>
                    <p class="liberation-desc">月の前半に予約完了。<br>あとは自動で投稿されるのを見届けるだけ</p>
                </div>
            </div>
            <p class="liberation-message fade-up">これは時短ではなく、<strong>解放</strong>です。</p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 5.6 Growth Section（継続と成果）【追加③】 -->
    <!-- ======================================== -->
    <section id="growth" class="growth-section">
        <div class="growth-wrap">
            <h2 class="section-h2 fade-up">続けていたら、<br class="sp">届くようになった。</h2>
            <p class="growth-lead fade-up">「楽になる」だけじゃ、意味がない。<br>
                大切なのは、楽になった先に何が起きるか。</p>
            <div class="growth-chain">
                <div class="growth-step fade-up">
                    <div class="growth-step-number">1</div>
                    <div class="growth-step-content">
                        <p class="growth-step-title">解放感</p>
                        <p class="growth-step-quote">「今週、Threadsのことを一度も考えなかった。でも毎日投稿できていた。」</p>
                    </div>
                </div>
                <div class="growth-step fade-up">
                    <div class="growth-step-number">2</div>
                    <div class="growth-step-content">
                        <p class="growth-step-title">生活との両立</p>
                        <p class="growth-step-quote">「本業の締め切り前でも、子どもと過ごす時間も、発信が止まらなかった。」</p>
                    </div>
                </div>
                <div class="growth-step fade-up">
                    <div class="growth-step-number">3</div>
                    <div class="growth-step-content">
                        <p class="growth-step-title">発信の質の向上</p>
                        <p class="growth-step-quote">「余裕ができたら、2割の体温を<br class="sp">ちゃんと込められるようになった。」</p>
                    </div>
                </div>
                <div class="growth-step fade-up">
                    <div class="growth-step-number">4</div>
                    <div class="growth-step-content">
                        <p class="growth-step-title">継続による成果</p>
                        <p class="growth-step-quote">「3ヶ月続けたら、フォロワーから<br class="sp">声をかけてもらえた。」</p>
                    </div>
                </div>
            </div>
            <p class="growth-bottom fade-up">「楽になる」は、ゴールじゃない。<br>
                <strong>「変わる」ための、スタート地点です。</strong>
            </p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 6. Professional Section（開発者の想い） -->
    <!-- ======================================== -->
    <section id="professional" class="professional-section">
        <div class="professional-wrap">
            <h2 class="fade-up">このツールは、効率化の道具じゃない。</h2>
            <div class="professional-content">
                <p class="fade-up">Web系フリーランス13年、職業訓練の講師として10期にわたって受講生にグラフィック / Web制作を教えてきました。</p>
                <p class="fade-up">
                    教える仕事を続けてきてわかったのは、「速く伝える」より「ちゃんと伝える」ほうがずっと難しいということです。どんなに便利なツールも、使う人が本質を理解していなければ、ただの「操作の暗記」で終わってしまう。
                </p>
                <p class="fade-up">その経験の中でずっと感じていたのは、「ツールに使われる人」と「ツールを使いこなす人」の差です。</p>
                <p class="fade-up">AIが当たり前になった今、その差はさらに広がっています。</p>
                <p class="fade-up">Threads_Styleを作ったのは、あなたに「ツールを使いこなす側」でいてほしいのと同時に「あなたの言葉で伝えられる人」であってほしいからです。</p>
                <p class="fade-up">投稿の主導権は「あなた」にあり、AIはあくまで、あなたの発信を支える頼れる右腕です。</p>
            </div>
            <p class="pro-message fade-up">『このツールは、あなたの<br class="sp">「発信者としてのスタイル」<br class="sp">を守るための盾です。』</p>
            <p class="proffessional-name">ブログサポーターGamitaka<br>石上 貴哉</p>
            <div class="pro-cta fade-up">
                <p class="pro-cta-lead">あなたの言葉を、<br class="sp">もっとラクに届けよう。</p>
                <a href="checkout.php" class="btn-primary btn-buy" id="cta-professional">今すぐThreads_Styleを導入する</a>
                <p class="pro-cta-note">¥5,980（税込・買い切り）</p>
            </div>
        </div>
        <div class="bg-filter"></div>
    </section>

    <!-- ======================================== -->
    <!-- 7. Spec Section（安心の仕様） -->
    <!-- ======================================== -->
    <section id="spec" class="examples">
        <div class="examples-area">
            <h2 class="fade-up">面倒なことは、<br class="sp">全部こちらでやります。</h2>
            <p class="page-text fade-up">安心して使い続けるための機能を<br class="sp">揃えました。</p>
            <div class="examples-wrap">
                <div class="examples-item fade-up">
                    <img src="img/img-04.png" alt="簡単アップデート">
                    <p class="pattern-label-text">簡単アップデート</p>
                    <p>ボタンひとつで最新版へ。難しい作業は不要。<br class="sp">常に最新の状態を保てます。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/img-05.png" alt="ライセンス管理">
                    <p class="pattern-label-text">ライセンス管理</p>
                    <p>Stripe連携による安心の認証。<br class="sp">購入から利用開始まで、シームレスに。</p>
                </div>
                <div class="examples-item fade-up">
                    <img src="img/img-06.png" alt="トークン管理">
                    <p class="pattern-label-text">トークン管理</p>
                    <p>Meta APIのトークン更新を自動化。<br class="sp">有効期限の管理も、残り日数の確認も、表示はしているけど気にしなくてもいいんです。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 7.5 Easy Start Section（導入ハードル払拭）【追加④】 -->
    <!-- ======================================== -->
    <section id="easy-start" class="easy-start-section">
        <div class="easy-start-wrap">
            <h3 class="easy-start-heading fade-up">レンタルサーバーは必要ですが、<br class="sp">難しい操作はありません。</h3>
            <div class="easy-start-grid">
                <div class="easy-start-card fade-up">
                    <p class="easy-start-title">セットアップは約10分</p>
                    <p class="easy-start-text">ZIPファイルをサーバーにアップロードして、初期設定を済ませるだけ。マニュアルに沿って進めれば、すぐに使い始められます。</p>
                </div>
                <div class="easy-start-card fade-up">
                    <p class="easy-start-title">導入マニュアル付き</p>
                    <p class="easy-start-text">Threads APIの取得方法から設置まで、画像付きでわかりやすく解説。一人でも安心して導入できます。</p>
                </div>
                <div class="easy-start-card fade-up">
                    <p class="easy-start-title">一般的なブログが使えれば大丈夫</p>
                    <p class="easy-start-text">専門的な知識は不要です。一般的なブログを使ったことがある方なら、迷わず操作できるシンプルな管理画面です。</p>
                </div>
            </div>
            <p class="easy-start-bottom fade-up">「自分に使えるかな？」——その迷いが、<br class="sp">一番もったいない。<br class="pc">
                <strong>あなたに必要なのは、始めてみること</strong>だけです。
            </p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 8. Closing Section -->
    <!-- ======================================== -->
    <section id="closing" class="closing-section">
        <div class="closing-wrap">
            <h2 class="fade-up">白紙の画面に、<br class="sp">悩まなくていい。</h2>
            <p class="fade-up">投稿のネタを絞り出す時間も、<br>
                AIの文章を読んで「なんか違う」と<br class="sp">感じる時間も、<br class="pc">
                もう終わりにしていい。</p>
            <p class="fade-up">あなたがやることは、2割だけ。<br>
                でもその2割があなたの「体温」となり、<br>
                あなたの発信を「あなたのもの」にする。</p>
            <p class="closing-accent fade-up">「自分の言葉」を、<br class="sp">最後の一筆に。</p>
            <div class="cta-card fade-up">
                <div class="price-tag">¥5,980 <span>(税込・買い切り)</span></div>
                <a href="checkout.php" class="btn-primary btn-buy" id="cta-closing">今すぐThreads_Styleを導入する</a>
                <p class="cta-note">Stripeによる安全な決済が<br class="sp">ご利用いただけます</p>
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