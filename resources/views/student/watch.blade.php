@extends("student.layout")

@section("title", "الشرح")

@push("styles")
    <link rel="stylesheet" href="/css/student/course.css?v=14">
@endpush

@section("content")
    <div class="cv-watch" id="watch" data-month="{{ $month }}">
        <aside class="cv-tree" id="watch-tree"></aside>
        <header class="cv-head">
            <div class="cv-head__title">
                <span id="watch-kicker">الشرح</span>
                <strong id="watch-title">جاري التحميل…</strong>
            </div>
            <div class="cv-head__acts">
                <button type="button" class="cv-btn-ghost-ink" id="watch-menu">المنهج</button>
                <a class="cv-btn" id="watch-exit" href="/learn/months/{{ $month }}">رجوع</a>
            </div>
        </header>
        <main class="cv-watch-main" id="watch-main"><p class="cv-note">جاري تحميل الشرح…</p></main>
        <div class="cv-bottom">
            <button type="button" class="cv-btn-dark" id="watch-prev">السابق</button>
            <button type="button" class="cv-btn" id="watch-menu-mobile">المنهج</button>
            <button type="button" class="cv-btn-dark" id="watch-next">التالي</button>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="/js/student/kit.js?v=3"></script>
    <script src="/js/student/watch.js?v=16"></script>
@endpush
