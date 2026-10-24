@extends("student.layout")

@section("title", "الشهر")

@push("styles")
    <link rel="stylesheet" href="/css/student/course.css?v=5">
@endpush

@section("content")
    <div class="cv" id="month-gate" data-month="{{ $month }}">
        <p class="desk-copy">جاري تحميل الشهر...</p>
    </div>
@endsection

@push("scripts")
    <script src="/js/student/kit.js?v=2"></script>
    <script src="/js/student/month.js?v=2"></script>
@endpush
