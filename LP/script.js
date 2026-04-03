/**
 * ============================================================
 * Threads_Style - Landing Page JavaScript
 * 参考: docs/script.js の構造を踏襲（jQuery不使用、Vanilla JS化）
 * ============================================================
 */

/* -----------------------------------------------
   ハンバーガーメニュー
   ----------------------------------------------- */
document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.querySelector('.hamburger');
    const menuSp = document.querySelector('.globalMenuSp');

    if (hamburger && menuSp) {
        hamburger.addEventListener('click', function () {
            hamburger.classList.toggle('active');
            menuSp.classList.toggle('active');
        });

        // メニューリンククリックで閉じる
        menuSp.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                hamburger.classList.remove('active');
                menuSp.classList.remove('active');
            });
        });
    }
});


/* -----------------------------------------------
   Topに戻るボタン
   ----------------------------------------------- */
(function () {
    var pagetop = document.getElementById('page_top');
    if (!pagetop) return;

    window.addEventListener('scroll', function () {
        if (window.scrollY > 100) {
            pagetop.style.display = 'block';
        } else {
            pagetop.style.display = 'none';
        }
    });

    pagetop.addEventListener('click', function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();


/* -----------------------------------------------
   スクロール＆フェードアップ / スライドイン
   参考: docs/script.js と同じロジック
   ----------------------------------------------- */
window.addEventListener('scroll', function () {
    var fadeUps = document.querySelectorAll('.fade-up');
    var slideInLefts = document.querySelectorAll('.slide-in-left');
    var slideInRights = document.querySelectorAll('.slide-in-right');

    for (var i = 0; i < fadeUps.length; i++) {
        var fadeUp = fadeUps[i];
        if (isElementPartiallyInViewport(fadeUp) && !fadeUp.classList.contains('fade-up-show')) {
            fadeUp.classList.add('fade-up-show');
        }
    }

    for (var j = 0; j < slideInLefts.length; j++) {
        var slideInLeft = slideInLefts[j];
        if (isElementPartiallyInViewport(slideInLeft) && !slideInLeft.classList.contains('slide-in-show')) {
            slideInLeft.classList.add('slide-in-show');
        }
    }

    for (var k = 0; k < slideInRights.length; k++) {
        var slideInRight = slideInRights[k];
        if (isElementPartiallyInViewport(slideInRight) && !slideInRight.classList.contains('slide-in-show')) {
            slideInRight.classList.add('slide-in-show');
        }
    }
});

function isElementPartiallyInViewport(element) {
    var rect = element.getBoundingClientRect();
    var windowHeight = window.innerHeight || document.documentElement.clientHeight;
    var windowWidth = window.innerWidth || document.documentElement.clientWidth;
    var verticalOffset = windowHeight * 0.3;
    var horizontalOffset = windowWidth * 0.3;

    return (
        rect.top < windowHeight - verticalOffset &&
        rect.bottom > verticalOffset &&
        rect.left < windowWidth - horizontalOffset &&
        rect.right > horizontalOffset
    );
}


/* -----------------------------------------------
   初回ロード時にも判定（スクロールなしで表示される要素対応）
   ----------------------------------------------- */
window.addEventListener('DOMContentLoaded', function () {
    // 少し遅延させてレイアウト確定後に実行
    setTimeout(function () {
        window.dispatchEvent(new Event('scroll'));
    }, 100);
});
