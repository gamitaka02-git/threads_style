<?php
/**
 * ============================================================
 * Threads Style - Landing Page
 * ============================================================
 * 役割: 商品紹介およびStripe決済への誘導を行うトップページ
 * 構成: Hero / Empathy / Concept / CTA① / Evidence / BrandProtect / Features / Liberation / Growth / Professional / Spec / EasyStart / Closing
 * ============================================================
 */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$baseUrl = $host ? ($protocol . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\')) : '';
$ogpImageUrl = $baseUrl ? ($baseUrl . '/img/ogp.png') : 'img/ogp.png';
$currentUrl = $host ? ($protocol . $host . $requestUri) : '';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threads_Style | AIに頼って、でも自分の言葉は渡さない。</title>
    <meta name="description" content="Threadsの投稿、8割はAIに任せていい。残りの2割にあなたらしさを込める。Threads運用・分析支援ツール Threads_Style。">

    <!-- Open Graph / OGP (Facebook, Threads, LINEなど) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Threads_Style | 発信はツールに預けて、言葉と時間は、自分で使う。">
    <meta property="og:description" content="自動投稿・定期投稿・AIエージェント連携。Threads運用は、まかせる時代へ。Threads運用・分析支援ツール Threads_Style。">
    <meta property="og:image" content="<?= htmlspecialchars($ogpImageUrl) ?>">
    <?php if ($currentUrl): ?>
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="Threads_Style">

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Threads_Style | 発信はツールに預けて、言葉と時間は、自分で使う。">
    <meta name="twitter:description" content="自動投稿・定期投稿・AIエージェント連携。Threads運用は、まかせる時代へ。Threads運用・分析支援ツール Threads_Style。">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogpImageUrl) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;700&family=Shippori+Mincho:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <link rel="icon" href="img/favicon.png" type="image/png">
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
            <p class="hero-label slide-in-left">進化し続けるThreads運用支援ツール<br class="sp"> ー<span> Threads_Style </span>ー</p>
            <h1 class="slide-in-left">発信はツールに預けて、<br>言葉と時間は、<br class="sp">自分で使う。</h1>
            <p class="hero-sub slide-in-left"><strong>自動投稿・定期投稿・AIエージェント連携。Threads運用は、まかせる時代へ。</strong><br>
                テーマ選びから下書き作成、予約投稿まで、AIがあなたの文体で毎日自動に回します。<br>
                伸びた投稿は定期投稿に入れて、休みの日も届け続けます。<br>
                分析や投稿の追加は、ClaudeやCursorなどのAIエージェントに話しかけるだけ。<br>
                <strong class="hero-sub-accent">浮いた時間は、コメントへの返信や、気になるあの人への交流に。</strong><br>
                発信をツールが支える分、あなたは「人との会話」に集中できます。</p>
            <ul class="hero-badges slide-in-left">
                <li><span class="hero-badge-new">NEW</span>MCP対応（AIエージェント操作）</li>
                <li><span class="hero-badge-new">NEW</span>定期投稿 最大1,000件ストック</li>
            </ul>
            <div class="hero-cta slide-in-left">
                <a href="checkout.php" class="btn-primary btn-buy" id="cta-hero">今すぐThreads_Styleを導入する</a>
                <p class="hero-cta-note">¥7,980（税込・買い切り）</p>
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
        <p class="section-message fade-up">発信をやめたいわけじゃない。<br>ただ、投稿づくりに追われて、<br class="sp"><strong>コメントに返信する余裕がなくなっているだけ。</strong></p>
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

    <!-- ======================================== -->
    <!-- 3.5 Selected Modes Section（選べる2つのモード） -->
    <!-- ======================================== -->
    <section id="modes" class="modes-section">
        <div class="modes-wrap">
            <h2 class="section-h2 fade-up">あなたのペースで選べる、<br class="sp">2つの運用スタイル</h2>
            <p class="section-sub fade-up">Threads_Styleは、AIに完全に任せる「自動モード」と、<br class="pc">こだわりを表現する「こだわりモード」を自由に行き来できます。</p>
            
            <div class="modes-grid">
                <!-- 自動モード -->
                <div class="mode-card auto-mode fade-up">
                    <div class="mode-badge">新機能</div>
                    <h3 class="mode-title">🤖 自動モード <span class="mode-title-sub">（おまかせ運用）</span></h3>
                    <p class="mode-lead">朝の5分で、1日の投稿が完了</p>
                    <p class="mode-desc">「今日、何を書こう？」と白紙の画面で悩む必要はもうありません。</p>
                    <ul class="mode-features">
                        <li><strong>夜間自動起動</strong>：あなたが寝ている間に、AIが過去の投稿から「最適なテーマ」を自動で選定。</li>
                        <li><strong>4件の下書き自動生成</strong>：選んだテーマに沿って、あなたらしい文体の下書きを4つ作成。</li>
                        <li><strong>ランダム予約投稿</strong>：朝8時〜夜24時の最適な時間帯に、自動で配信スケジュールをセット。</li>
                        <li><strong>朝起きて確認＆微調整するだけ</strong>：明らかにズレているものは削除し、あとは数秒の微調整で自動投稿がスタート！</li>
                    </ul>
                </div>
                
                <!-- こだわりモード -->
                <div class="mode-card custom-mode fade-up">
                    <h3 class="mode-title">✍️ こだわりモード <span class="mode-title-sub">（8:2運用）</span></h3>
                    <p class="mode-lead">勝負投稿や、想いを届けたい時に</p>
                    <p class="mode-desc">「これだけは、自分の言葉でじっくり伝えたい」という時に最適です。</p>
                    <ul class="mode-features">
                        <li><strong>8:2の黄金比</strong>：AIがあなたのスタイルで作成した3パターンの下書き（教育・独り言・交流）をベースに、最後の2割であなただけの「熱量」や「体験」を込めて発信します。</li>
                        <li><strong>100%自身での執筆も可能</strong>：もちろん、100％あなた自身の言葉でツール上で直接書いてそのまま投稿（予約投稿）することもできます。</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section（中間） -->
    <div class="mid-cta-section">
        <div class="mid-cta-wrap fade-up">
            <p class="mid-cta-lead">全部書かなくていい。<br>あなたらしさは、<br class="sp">2割で十分伝わる。</p>
            <a href="checkout.php" class="btn-primary btn-buy" id="cta-mid">今すぐThreads_Styleを導入する</a>
            <p class="mid-cta-note">¥7,980（税込・買い切り）<br class="sp">/ Stripeによる安全な決済</p>
        </div>
    </div>

    <!-- ======================================== -->
    <!-- 4. Feature Section（スタイルガイド／インサイト分析／自動投稿・定期投稿／MCP） -->
    <!-- ======================================== -->
    <section id="evidence" class="service evidence-analysis">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2>スタイルガイド</h2>
                </div>
                <p class="slide-in-left">過去のThreads投稿を自己解析し、「あなたらしさ」を言語化。分析結果はスタイルガイドとして出力され、AIと共有できます。</p>
                <p class="slide-in-left">AIが書いた文章は学習対象から除外されるため、分析するのは、あなた自身が書いた文章だけです。<br>性格、口癖、視点、温度感をAIに渡すことで、下書きの精度が上がり続けます。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <section class="service evidence-guide">
        <div class="text-wrap">
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2>投稿生成 ＆ <br class="sp">インサイト分析</h2>
                </div>
                <p class="slide-in-right">「伸びた投稿」「反応が多かった時間」を自動で検出。いいね数や閲覧数などの条件で絞り込んで、何が刺さったかを把握できます。</p>
                <p class="slide-in-right">思いついたテーマを入力するだけで、AIがあなたらしい切り口の投稿を自動生成。さらに、WordPressやnoteに書いたブログ記事のURL・テキストからThreads用に変換する「リパーパス」にも対応し、過去の資産を何度でも活かせます。</p>
            </div>
        </div>
        <div class="service-bg-right"></div>
    </section>

    <section id="features" class="service feature-post">
        <div class="text-wrap">
            <div class="text-left slide-in-left">
                <div class="slide-in-left">
                    <h2>自動投稿・定期投稿</h2>
                </div>
                <p class="slide-in-left">スタイルガイドを使ったAIが、テーマ選びから下書き、予約投稿のセットまで自動で実行。朝は確認と微調整だけ。Bot判定を防ぐ「ゆらぎ設定」で、自然な投稿リズムを保ちます。</p>
                <p class="slide-in-left">反応の良かった投稿は「定期投稿」に最大1,000件までストック。決めた時間に自動で届け続けるので、忙しい日も休みの日も、発信が止まりません。</p>
                <p class="slide-in-left">画像20枚の添付やスレッド（連投）にも対応しています。</p>
            </div>
        </div>
        <div class="service-bg-left"></div>
    </section>

    <section class="service feature-insight">
        <div class="text-wrap">
            <div class="text-right slide-in-right">
                <div class="slide-in-right">
                    <h2>AIエージェントの接続<br class="sp">（MCP）</h2>
                </div>
                <p class="slide-in-right">ClaudeやCursor、AntigravityなどのAIエージェントと接続。「一番伸びた投稿はどれ？」と話しかけるだけで、インサイトを分析してくれます。</p>
                <p class="slide-in-right">反応の良かった投稿を定期投稿へセットしたり、予約投稿をまとめて登録したり。分析も投稿の追加も、チャットで完結します。</p>
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
    <!-- 4.55 Growth Cycle Section（4ステップ成長サイクル） -->
    <!-- ======================================== -->
    <section id="cycle" class="cycle-section">
        <div class="cycle-wrap">
            <p class="cycle-eyebrow fade-up">Threads_Styleの活用事例</p>
            <h2 class="section-h2 fade-up">ツールを回すほど、<br class="sp">アカウントが育っていく。</h2>
            <p class="section-sub fade-up">ただ自動で投稿するだけではありません。データを集め、AIと磨き、資産に変える。<br class="pc">開発者自身が実際に回している、4ステップの成長サイクルです。</p>
            <div class="cycle-steps">
                <div class="cycle-step fade-up">
                    <div class="cycle-step-number">1</div>
                    <div class="cycle-step-body">
                        <h3 class="cycle-step-title">「スタイルガイド」×「自動投稿」で、打席とデータを集める</h3>
                        <p class="cycle-step-text">あなたの文体を学んだAIが、毎日の投稿を自動で発信。白紙の画面で悩まずに、まずは打席に立ち続け、読者のリアルな反応データを貯めていきます。</p>
                    </div>
                </div>
                <div class="cycle-step fade-up">
                    <div class="cycle-step-number">2</div>
                    <div class="cycle-step-body">
                        <h3 class="cycle-step-title">「MCP」接続のAIエージェントで分析し、反響投稿を「定期投稿」へ</h3>
                        <p class="cycle-step-text">ClaudeやCursorなどに「一番伸びた投稿は？」と聞くだけ。閲覧数やエンゲージメントの良かった投稿を見つけ、定期投稿にセットします。</p>
                    </div>
                </div>
                <div class="cycle-step fade-up">
                    <div class="cycle-step-number">3</div>
                    <div class="cycle-step-body">
                        <h3 class="cycle-step-title">データから「伸びる要素」を足して、定期投稿に再セット</h3>
                        <p class="cycle-step-text">なぜ伸びたのかを踏まえて少しリライトし、もう一度ストックへ。回すたびに、届く投稿の精度が上がっていきます。</p>
                    </div>
                </div>
                <div class="cycle-step fade-up">
                    <div class="cycle-step-number">4</div>
                    <div class="cycle-step-body">
                        <h3 class="cycle-step-title">反応が集まる循環の完成。あなたは「交流」に時間を使う</h3>
                        <p class="cycle-step-text">発信はツールが回し続けます。浮いた時間は、コメントへの返信や他の人への交流へ。Threadsで評価されやすいと言われる「会話」に集中できます。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 4.6 Real Benefits Section（自動モードのリアルな変化） -->
    <!-- ======================================== -->
    <section id="benefits" class="benefits-section">
        <div class="benefits-wrap">
            <h2 class="section-h2 fade-up">実際にこのサイクルを動かして分かった、<br class="sp">リアルな変化</h2>
            <p class="section-sub fade-up">「投稿を増やすと、質が下がって反応が薄まるのでは？」<br class="pc">開発者自身のアカウントで、運用10日間を比較しました。</p>

            <div class="benefits-data fade-up">
                <p class="benefits-data-caption">9/1〜9/10 → 10/1〜10/10（各10日間）</p>
                <div class="benefits-data-grid">
                    <div class="benefits-data-item">
                        <p class="benefits-data-label">投稿数</p>
                        <p class="benefits-data-value">33件 → <strong>88件</strong></p>
                        <p class="benefits-data-sub">約2.7倍</p>
                    </div>
                    <div class="benefits-data-item">
                        <p class="benefits-data-label">総閲覧数</p>
                        <p class="benefits-data-value">1,548 → <strong>5,437</strong></p>
                        <p class="benefits-data-sub">約3.5倍</p>
                    </div>
                    <div class="benefits-data-item">
                        <p class="benefits-data-label">総いいね数</p>
                        <p class="benefits-data-value">18 → <strong>91</strong></p>
                        <p class="benefits-data-sub">約5倍</p>
                    </div>
                    <div class="benefits-data-item">
                        <p class="benefits-data-label">1投稿あたり平均閲覧数</p>
                        <p class="benefits-data-value">46.9 → <strong>61.8</strong></p>
                        <p class="benefits-data-sub">約1.3倍</p>
                    </div>
                </div>
                <p class="benefits-data-lead">投稿数を約2.7倍に増やしても、1投稿あたりの閲覧数もいいね率（1.16% → 1.67%）も、薄まるどころか上がりました。</p>
            </div>
            
            <div class="benefits-grid">
                <!-- ベネフィット1 -->
                <div class="benefit-card fade-up">
                    <div class="benefit-icon-wrap"><span class="benefit-icon">💡</span></div>
                    <h3 class="benefit-card-title">1. 「テーマ選び」のしんどさから、完全に解放された</h3>
                    <p class="benefit-text">毎日投稿で最もエネルギーを使うのは、実は「何を書くか（テーマ決め）」です。Threads_Styleは、AIがテーマ選びから下書き作成までワンストップで引き受けるため、発信の心理的ハードルがほぼゼロになります。</p>
                </div>
                
                <!-- ベネフィット2 -->
                <div class="benefit-card fade-up">
                    <div class="benefit-icon-wrap"><span class="benefit-icon">❤️</span></div>
                    <h3 class="benefit-card-title">2. フォロワーが少なくても、「温かい反応」が届く</h3>
                    <p class="benefit-text">世の中の自動ツールのような「いかにもAIが書いたコピペ文」は、Threadsではすぐに見破られてスルーされます。Threads_Styleはあなたの文体（スタイルガイド）を学習しているため、完全自動でも「あなたの体温」が宿り、いいねやコメントなどの「生きた反応」がしっかり届くようになります。</p>
                </div>
                
                <!-- ベネフィット3 -->
                <div class="benefit-card fade-up">
                    <div class="benefit-icon-wrap"><span class="benefit-icon">📈</span></div>
                    <h3 class="benefit-card-title">3. 「共感あるある」がホームランを生み、資産として残る</h3>
                    <p class="benefit-text">手数を増やした中から、「WordPress導入相談のリアルな本音（636閲覧・18いいね）」や「初心者がやりがちなファイル名のミス（307閲覧）」といった反応の大きい投稿が次々と生まれました。それらを「定期投稿」にストックすることで、一度の努力が使い捨てにならない運用になります。</p>
                </div>

                <!-- ベネフィット4 -->
                <div class="benefit-card fade-up">
                    <div class="benefit-icon-wrap"><span class="benefit-icon">💬</span></div>
                    <h3 class="benefit-card-title">4. 投稿づくりの時間が、「人との交流」の時間になった</h3>
                    <p class="benefit-text">発信をツールが回してくれる分、コメントへの返信や、他のアカウントの投稿への反応に時間を使えるようになります。Threadsでは、投稿するだけでなく「会話」が評価されやすいと言われています。反応する側に回ることで、自分の投稿にも反応が返ってきやすくなります。</p>
                </div>
            </div>
            <p class="benefits-note fade-up">※数値は開発者自身のアカウントでの運用結果であり、成果を保証するものではありません。</p>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 5.2 Reminder Section（進化し続ける機能のおさらい） -->
    <!-- ======================================== -->
    <section id="new-features" class="new-features-section">
        <div class="new-features-wrap">
            <h2 class="section-h2 fade-up">進化し続ける、<br class="sp">Threads_Style</h2>
            <p class="section-sub fade-up">自動で投稿するだけで終わらない。「届け続ける」「AIに任せる」「反応を見える化する」まで、<br class="pc">ひとつのツールで回せます。</p>
            <div class="new-features-grid">
                <div class="new-feature-card fade-up">
                    <h3 class="new-feature-title">定期投稿ストック<br><span class="new-feature-title-sub">最大1,000件・CSV一括登録対応</span></h3>
                    <p class="new-feature-text">反応の良かった投稿や、定番のノウハウ、告知を最大1,000件までストック。決めた時間に自動で届け続けます。</p>
                    <p class="new-feature-text">一度書いた投稿が使い捨てにならず、忙しい日も休みの日も、発信が止まりません。</p>
                </div>
                <div class="new-feature-card fade-up">
                    <h3 class="new-feature-title">MCP対応<br><span class="new-feature-title-sub">AIエージェントから操作できる</span></h3>
                    <p class="new-feature-text">ClaudeやCursor、AntigravityなどのAIエージェントと接続。「一番伸びた投稿はどれ？」と聞くだけでインサイトを分析できます。</p>
                    <p class="new-feature-text">反応の良かった投稿を定期投稿へセットしたり、予約投稿をまとめて登録したり。操作もチャットで完結します。</p>
                </div>
                <div class="new-feature-card fade-up">
                    <h3 class="new-feature-title">スレッド（連投）投稿<br><span class="new-feature-title-sub">ツリーで届ける</span></h3>
                    <p class="new-feature-text">長めのノウハウや、1投稿目の共感から2投稿目の案内へつなぐ導線も、ツリー形式で即時・予約どちらでも投稿できます。</p>
                </div>
                <div class="new-feature-card fade-up">
                    <h3 class="new-feature-title">クリック計測 ＆ CTAテンプレート<br><span class="new-feature-title-sub">反応を見える化して、導線を一瞬で</span></h3>
                    <p class="new-feature-text">どの投稿から何回リンクがクリックされたかを、ツール内で確認できます。どの投稿が行動につながったのかが分かります。</p>
                    <p class="new-feature-text">note・LP・LINEへの案内文などの定型文は「CTAテンプレート」に登録しておけば、投稿作成中にワンクリックで挿入できます。</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================== -->
    <!-- 5.5 Liberation Section（解放訴求）【更新②】 -->
    <!-- ======================================== -->
    <section id="liberation" class="liberation-section">
        <div class="liberation-wrap">
            <h2 class="section-h2 fade-up">毎日の発信、もう終わってた。</h2>
            <p class="liberation-lead fade-up">「1投稿を○分短縮」なんて話じゃない。<br>
                <strong>朝の5分、スマホでポチッと確認するだけ。</strong><br>
                今日の発信も、明日の発信も——<br class="sp">眠っている間に、もう終わっている。
            </p>
            <div class="liberation-visual fade-up">
                <div class="liberation-before">
                    <p class="liberation-label">Before</p>
                    <p class="liberation-time">毎日30分 × 30日</p>
                    <p class="liberation-desc">ネタ探し、下書き、推敲、投稿…<br>毎日「何を書こう」と追われる日々</p>
                </div>
                <div class="liberation-arrow">→</div>
                <div class="liberation-after">
                    <p class="liberation-label">After</p>
                    <p class="liberation-time">毎朝 5分 × スマホ確認だけ</p>
                    <p class="liberation-desc">夜間にテーマ選定〜予約投稿までAIが完了。<br>布団の中でポチポチ微調整するだけ</p>
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
                <p class="pro-cta-note">¥7,980（税込・買い切り）</p>
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
                <div class="price-tag">¥7,980 <span>(税込・買い切り)</span></div>
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