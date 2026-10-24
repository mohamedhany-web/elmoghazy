@extends("student.layout")

@section("title", "شهور الشرح")

@push("styles")
    <link rel="stylesheet" href="/css/student/course.css?v=5">
@endpush

@section("content")
    <div class="cv" id="month-board">
        <header class="cv-page">
            <div>
                <h1>شهور الشرح</h1>
                <p>الشهور اللي تفعيلها شغّال على حسابك.</p>
            </div>
            <a class="cv-btn" href="/months">تصفح الشهور</a>
        </header>
        <div class="cv-stats" id="month-stats"></div>
        <div class="cv-tools">
            <input id="month-search" type="search" placeholder="ابحث بالشهر أو السنة" aria-label="بحث في الشهور">
            <span class="cv-count" id="month-count"></span>
            <label><input type="checkbox" id="month-on"> قيد التقدم</label>
            <label><input type="checkbox" id="month-done"> مكتمل</label>
        </div>
        <div class="cv-grid" id="month-grid"></div>
    </div>
@endsection

@push("scripts")
    <script src="/js/student/kit.js?v=2"></script>
    <script src="/js/student/months.js?v=2"></script>
@endpush
