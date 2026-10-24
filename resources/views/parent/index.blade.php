@extends("site.layout")

@push("styles")
    <link rel="stylesheet" href="/css/reading.css?v=1">
    <link rel="stylesheet" href="/css/report.css?v=4">
@endpush

@section("content")
    <div class="desk-page">
        <header class="desk-top wrap">
            <a href="/" class="brand">
                <span class="brand__mark" data-icon="atom"></span>
                <div>
                    <p class="brand__name display">المغازي</p>
                    <p class="brand__tag">تقرير ولي الأمر</p>
                </div>
            </a>
            <div class="desk-top__actions">
                <button type="button" class="theme-toggle" data-theme aria-label="تبديل الوضع"></button>
                <a href="/" class="desk-home"><span data-icon="home"></span><span>الموقع</span></a>
            </div>
        </header>
        <section class="desk wrap">
            <div class="desk-form">
                <h2>تقرير الابن</h2>
                <p class="desk-copy">الكود للتقرير بس. مش بيفتح الحصص.</p>
                <button type="button" class="btn btn-primary" id="open-parent">أدخل كود الابن</button>
            </div>
            <article id="parent-report" class="desk-lesson" hidden></article>
        </section>
    </div>
@endsection

@push("scripts")
    <script src="/js/report.js?v=4"></script>
    <script src="/js/parent/report.js?v=3"></script>
@endpush
