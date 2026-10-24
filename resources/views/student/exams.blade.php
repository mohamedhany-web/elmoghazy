@extends("student.layout")

@section("title", "الامتحانات")

@push("styles")
    <link rel="stylesheet" href="/css/student/course.css?v=5">
@endpush

@section("content")
    <div class="cv">
        <header class="cv-page">
            <div>
                <h1>الامتحانات</h1>
                <p>امتحانات سنتك. المقفل يظهر من غير ما يفتح الورقة.</p>
            </div>
        </header>
        <div class="cv-stats" id="exam-stats"></div>
        <div class="cv-tools">
            <input id="exam-search" type="search" placeholder="ابحث باسم الامتحان" aria-label="بحث في الامتحانات">
            <span class="cv-count" id="exam-count"></span>
        </div>
        <p id="exam-note" class="cv-note" hidden></p>
        <div class="cv-grid" id="exam-list"></div>
    </div>
@endsection

@push("scripts")
    <script src="/js/student/exams.js?v=7"></script>
@endpush
