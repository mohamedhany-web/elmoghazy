<!DOCTYPE html>
<html lang="ar" dir="rtl" class="st-rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <title>المغازي | @yield("title", "تعلّمي")</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/student/shell.css?v=13">
    @stack("styles")
</head>
<body>
@php
    $path = request()->path();
    $onHome = $path === "learn";
    $onMonths = str_starts_with($path, "learn/months") && ! preg_match('#^learn/months/[^/]+/learn$#', $path);
    $onExams = str_starts_with($path, "learn/exams");
    $onWatch = (bool) preg_match('#^learn/months/[^/]+/learn$#', $path);
@endphp
<div class="st-dash{{ $onWatch ? ' st-dash--watch' : '' }}" id="stShell">
    <aside class="st-rail" data-nav>
        <div class="st-rail__head">
            <a class="st-rail__brand" href="/learn">
                <span class="st-rail__mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" width="20" height="20"><circle cx="16" cy="16" r="3" fill="currentColor"/><ellipse cx="16" cy="16" rx="12" ry="5" fill="none" stroke="currentColor" stroke-width="1.6"/><ellipse cx="16" cy="16" rx="12" ry="5" fill="none" stroke="currentColor" stroke-width="1.6" transform="rotate(60 16 16)"/><ellipse cx="16" cy="16" rx="12" ry="5" fill="none" stroke="currentColor" stroke-width="1.6" transform="rotate(120 16 16)"/></svg>
                </span>
                <span class="st-rail__logo">المغازي</span>
            </a>
            <button type="button" class="st-rail__toggle" id="stRailToggle" aria-label="الشريط الجانبي">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M8 6l6 6-6 6M14 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <a class="st-rail__profile" href="/learn/profile">
            <span class="st-rail__avatar" id="stAvatarSide">ط</span>
            <span class="st-rail__who">
                <strong class="st-rail__name" data-user>طالب</strong>
                <span class="st-rail__role" id="stRoleSide">طالب</span>
            </span>
        </a>
        <nav class="st-rail__nav">
            <a class="st-rail__link {{ $onHome ? "is-active" : "" }}" href="/learn">
                <span class="st-rail__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
                <span class="st-rail__label">الرئيسية</span>
            </a>
            <a class="st-rail__link {{ $onMonths ? "is-active" : "" }}" href="/learn/months">
                <span class="st-rail__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-3-7 3V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
                <span class="st-rail__label">شهور الشرح</span>
            </a>
            <a class="st-rail__link {{ $onExams ? "is-active" : "" }}" href="/learn/exams">
                <span class="st-rail__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M8 4h8a1 1 0 0 1 1 1v15l-5-2.5L7 20V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M9 9h6M9 12h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                <span class="st-rail__label">الامتحانات</span>
            </a>
            <a class="st-rail__link" href="/">
                <span class="st-rail__icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M4 12h16M12 4c2.2 2.4 3.3 5.1 3.3 8S14.2 17.6 12 20c-2.2-2.4-3.3-5.1-3.3-8S9.8 6.4 12 4z" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></span>
                <span class="st-rail__label">الموقع</span>
            </a>
        </nav>
    </aside>
    <div class="st-stage">
        <header class="st-bar" id="stBar" data-nav>
            <button type="button" class="st-bar__menu" id="stNavMenu" aria-label="القائمة">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
            <form class="st-search" id="stSearch" role="search">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="11" cy="11" r="6" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 16l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <input id="stSearchInput" type="search" placeholder="بحث في الشهور والامتحانات" aria-label="بحث" autocomplete="off">
                <div class="st-drop__panel st-search__drop" id="stSearchDrop" hidden></div>
            </form>
            <div class="st-bar__tools">
                <div class="st-drop" id="stNotes">
                    <button type="button" class="st-icon" id="stNotesBtn" aria-expanded="false" aria-controls="stNotesPanel" aria-label="الإشعارات">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2H4.5z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M10 19a2 2 0 0 0 4 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <i class="st-dot" id="stNotesDot" hidden></i>
                    </button>
                    <div class="st-drop__panel" id="stNotesPanel" hidden>
                        <strong class="st-drop__title">الإشعارات</strong>
                        <div id="stNotesList"></div>
                    </div>
                </div>
                <div class="st-drop" id="stAccount">
                    <button type="button" class="st-icon st-icon--avatar" id="stAccountBtn" aria-expanded="false" aria-controls="stAccountPanel" aria-label="حساب الطالب">
                        <span class="st-bar__avatar" id="stAvatar">ط</span>
                    </button>
                    <div class="st-drop__panel" id="stAccountPanel" hidden>
                        <p class="st-drop__who">
                            <strong data-user>طالب</strong>
                            <span id="stRole">طالب</span>
                        </p>
                        <a href="/learn/profile">الملف الشخصي</a>
                        <button type="button" data-logout hidden>تسجيل الخروج</button>
                    </div>
                </div>
            </div>
        </header>
        <main class="st-main">
            @yield("content")
        </main>
    </div>
    <button type="button" class="st-rail-backdrop" id="stRailBackdrop" hidden aria-label="إغلاق القائمة"></button>
</div>
<script src="/js/app.js?v=20"></script>
<script src="/js/student/shell.js?v=5"></script>
@stack("scripts")
</body>
</html>
