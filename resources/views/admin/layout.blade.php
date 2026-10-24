<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المغازي | @yield("title", "لوحة المدرس")</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/admin/coinspace.css?v=28">
    @stack("styles")
</head>
<body class="cs-body">
    @if (!empty($bare))
        @yield("content")
    @else
        <div class="cs-app" id="cs-app" hidden>
            <button type="button" class="cs-side-backdrop" data-side-backdrop aria-label="إغلاق القائمة"></button>
            <aside class="cs-side">
                <div class="cs-side__scroll">
                    <a class="cs-brand" href="/admin">
                        <span class="cs-brand__mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="2"/><ellipse cx="12" cy="12" rx="9" ry="3.6"/><ellipse cx="12" cy="12" rx="9" ry="3.6" transform="rotate(60 12 12)"/></svg>
                        </span>
                        <span class="cs-brand__text">
                            <p class="cs-brand__name">المغازي</p>
                            <p class="cs-brand__sub">فيزياء · لوحة المدرس</p>
                        </span>
                    </a>
                    <p class="cs-nav-label">وصول سريع</p>
                    <a class="cs-nav-link {{ ($page ?? '') === 'dashboard' ? 'is-active' : '' }}" href="/admin" data-nav-link title="لوحة التحكم"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20H4z"/></svg><span>لوحة التحكم</span></a>
                    <p class="cs-nav-label">الخدمات</p>
                    <a class="cs-nav-link {{ ($page ?? '') === 'students' ? 'is-active' : '' }}" href="/admin/students" data-nav-link title="الطلبة"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3"/><path d="M5 19a7 7 0 0 1 14 0"/></svg><span>الطلبة</span></a>
                    <p class="cs-nav-label">المحتوى</p>
                    <a class="cs-nav-link {{ ($page ?? '') === 'years' ? 'is-active' : '' }}" href="/admin/years" data-nav-link title="السنوات"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg><span>السنوات</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'months' ? 'is-active' : '' }}" href="/admin/months" data-nav-link title="الشهور"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg><span>الشهور</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'banks' ? 'is-active' : '' }}" href="/admin/banks" data-nav-link title="بنوك الأسئلة"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19h16M6 16V8l6-4 6 4v8"/><path d="M9 16v-4h6v4"/></svg><span>بنوك الأسئلة</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'exams' ? 'is-active' : '' }}" href="/admin/exams" data-nav-link title="الامتحانات"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3h8v4H8z"/><path d="M7 7H5v14h14V7h-2"/><path d="M8 12h8M8 16h5"/></svg><span>الامتحانات</span></a>
                    <p class="cs-nav-label">التشغيل</p>
                    <a class="cs-nav-link {{ ($page ?? '') === 'requests' ? 'is-active' : '' }}" href="/admin/requests" data-nav-link title="الطلبات"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg><span>الطلبات</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'courses' ? 'is-active' : '' }}" href="/admin/courses" data-nav-link title="الكورسات"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg><span>الكورسات</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'codes' ? 'is-active' : '' }}" href="/admin/codes" data-nav-link title="الأكواد"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg><span>الأكواد</span></a>
                    <a class="cs-nav-link {{ ($page ?? '') === 'activations' ? 'is-active' : '' }}" href="/admin/activations" data-nav-link title="التفعيلات"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg><span>التفعيلات</span></a>
                </div>
                <div class="cs-side__foot">
                    <a class="cs-nav-link" href="/" data-nav-link title="الموقع"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg><span>الموقع</span></a>
                    <button type="button" class="cs-nav-link" data-logout title="خروج"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6H5v12h4"/><path d="M10 12h10M16 8l4 4-4 4"/></svg><span>خروج</span></button>
                </div>
            </aside>
            <div class="cs-main">
                <header class="cs-top">
                    <button type="button" class="cs-ico" data-side-toggle aria-label="القائمة"><span class="cs-burger"><span></span><span></span><span></span><span></span></span></button>
                    <label class="cs-search">
                        <svg class="cs-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                        <input id="cs-search" type="search" placeholder="بحث في الصفحة" autocomplete="off">
                    </label>
                    <div class="cs-top__end">
                        <div class="cs-apps" data-apps>
                            <button type="button" class="cs-apps__btn" data-apps-btn>التطبيقات</button>
                            <div class="cs-apps__menu" data-menu hidden>
                                <a href="/admin">اللوحة</a>
                                <a href="/admin/students">الطلبة</a>
                                <a href="/admin/years">السنوات</a>
                                <a href="/admin/months">الشهور</a>
                                <a href="/admin/banks">بنوك الأسئلة</a>
                                <a href="/admin/exams">الامتحانات</a>
                                <a href="/admin/requests">الطلبات</a>
                                <a href="/admin/courses">الكورسات</a>
                                <a href="/admin/codes">الأكواد</a>
                                <a href="/admin/activations">التفعيلات</a>
                            </div>
                        </div>
                        <div class="cs-account">
                            <span class="cs-account__avatar" id="cs-avatar">م</span>
                            <span class="cs-account__meta"><strong id="cs-name">المدرس</strong><small>مدرس</small></span>
                        </div>
                    </div>
                </header>
                <div class="cs-content">
                    <div class="cs-page" id="desk">
                        <p id="desk-note" class="cs-note" hidden></p>
                        @yield("content")
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script src="/js/app.js?v=20"></script>
    <script src="/js/admin/shell.js?v=3"></script>
    <script src="/js/admin/common.js?v=7"></script>
    @stack("scripts")
</body>
</html>
