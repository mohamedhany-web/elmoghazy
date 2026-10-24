@extends("student.layout")

@section("title", "الملف الشخصي")

@section("content")
    <section class="stu-panel" id="student-profile">
        <h1 class="st-top__title">الملف الشخصي</h1>
        <p class="desk-copy">بيانات حسابك على المنصة.</p>
        <dl class="st-file" id="student-file">
            <div><dt>الاسم</dt><dd id="file-name">...</dd></div>
            <div><dt>السنة</dt><dd id="file-grade">...</dd></div>
            <div><dt>الهاتف</dt><dd id="file-phone">...</dd></div>
            <div><dt>البريد</dt><dd id="file-email">...</dd></div>
        </dl>
    </section>
@endsection

@push("scripts")
    <script src="/js/student/profile.js?v=1"></script>
@endpush
