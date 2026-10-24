@extends("admin.layout")

@section("title", "حصص الكورس")

@section("content")
    <nav class="cs-crumb"><a href="/admin/courses">الكورسات</a><span>/</span><strong id="course-crumb">الحصص</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1 id="course-title">الحصص</h1>
            <p>حصص الكورس في جدول واحد.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" id="lesson-add">حصة جديدة</button>
    </header>
    <div id="lesson-list"></div>

    <div class="cs-modal" id="modal-lesson" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="lesson-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="lesson-form-title">حصة جديدة</h2></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="lesson_id">
                <label class="cs-field"><span class="cs-label">الاسم</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">النوع</span>
                    <select class="cs-input" name="kind"><option value="text">نص</option><option value="video">فيديو</option><option value="file">ملف</option></select>
                </label>
                <label class="cs-field"><span class="cs-label">المحتوى النصي</span><textarea class="cs-input" name="body" rows="4"></textarea></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script>window.COURSE_ID = @json($course);</script>
    <script src="/js/admin/course.js?v=2"></script>
@endpush
