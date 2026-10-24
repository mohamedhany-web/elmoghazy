<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المغازي | محمد المغازي — مدرس فيزياء</title>
    <meta name="description" content="منصة المغازي للفيزياء مع مستر محمد المغازي — شهور إعدادي وثانوي.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Rubik:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/site.css?v=17">
    <link rel="stylesheet" href="/css/motion.css">
    @stack("styles")
    <script>
        (function () {
            var hour = new Date().getHours();
            var night = hour < 6 || hour >= 18;
            var part = night ? "night" : "day";
            var saved = localStorage.getItem("elmoghazy_theme");
            var savedPart = localStorage.getItem("elmoghazy_theme_part");
            var dark = savedPart === part && (saved === "dark" || saved === "light") ? saved === "dark" : night;
            if (dark) document.documentElement.classList.add("dark");
        })();
    </script>
</head>
<body>
    @yield("content")
    <div class="pop" id="site-pop" hidden>
        <button type="button" class="pop__backdrop" data-pop-close aria-label="إغلاق"></button>
        <div class="pop__panel" role="dialog" aria-modal="true" aria-labelledby="pop-title">
            <div class="pop__head">
                <div>
                    <h2 id="pop-title" class="display"></h2>
                    <p id="pop-copy" class="muted"></p>
                </div>
                <button type="button" class="pop__x" data-pop-close aria-label="إغلاق">×</button>
            </div>
            <div class="pop__body" id="pop-body"></div>
            <p id="pop-note" class="pop__note" hidden></p>
        </div>
    </div>
    <script src="/js/app.js?v=20"></script>
    @stack("scripts")
</body>
</html>
