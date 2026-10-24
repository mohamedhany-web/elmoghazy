@extends("student.layout")

@section("title", "الامتحان")

@section("content")
    @include("student.partials.top", [
        "pageTitle" => "الامتحان",
        "crumbs" => [
            ["label" => "الرئيسية", "url" => "/learn"],
            ["label" => "الامتحانات", "url" => "/learn/exams"],
            ["label" => "الامتحان"],
        ],
    ])
    <section class="stu-panel" id="exam-root" data-exam="{{ $exam }}">
        <p class="desk-copy">جاري تحميل الامتحان...</p>
    </section>
@endsection

@push("scripts")
    <script src="/js/student/exam.js?v=3"></script>
@endpush
