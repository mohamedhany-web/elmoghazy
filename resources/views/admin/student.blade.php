@extends("admin.layout")

@section("title", "تقرير الطالب")

@push("styles")
    <link rel="stylesheet" href="/css/report.css?v=4">
@endpush

@section("content")
    <nav class="cs-crumb"><a href="/admin/students">الطلبة</a><span>/</span><strong id="student-crumb">التقرير</strong></nav>
    <header class="cs-page-head">
        <div>
            <a class="cs-btn cs-btn--ghost" href="/admin/students">رجوع</a>
            <h1 id="student-title">تقرير الطالب</h1>
            <p id="student-copy"></p>
        </div>
        <div class="cs-page-head__acts">
            <button type="button" class="cs-btn cs-btn--ghost" id="record-edit">تعديل</button>
            <button type="button" class="cs-btn cs-btn--ghost" id="record-activate">تفعيل</button>
            <button type="button" class="cs-btn cs-btn--ghost" id="record-toggle">إيقاف</button>
            <button type="button" class="cs-btn cs-btn--ghost" id="record-lock">قفل</button>
            <button type="button" class="cs-btn cs-btn--ghost" id="record-device" title="لو الطالب غيّر الموبايل أو المتصفح والشهر اختفى عنده">تصفير الجهاز</button>
            <button type="button" class="cs-btn cs-btn--danger" id="record-delete">حذف</button>
        </div>
    </header>
    <div id="student-report"></div>

    <div class="cs-modal" id="modal-student" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="student-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>تعديل الطالب</h2><p>سيبها فاضية لو مش هتغيّر كلمة المرور.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label">الاسم الأول</span><input class="cs-input" name="first_name" required></label>
                <label class="cs-field"><span class="cs-label">الاسم الثاني</span><input class="cs-input" name="second_name" required></label>
                <label class="cs-field"><span class="cs-label">رقم التليفون</span><input class="cs-input" name="phone" required inputmode="tel" dir="ltr"></label>
                <label class="cs-field"><span class="cs-label">رقم ولي الأمر</span><input class="cs-input" name="parent_phone" required inputmode="tel" dir="ltr"></label>
                <label class="cs-field"><span class="cs-label">كلمة المرور</span><input class="cs-input" type="password" name="password" minlength="8"></label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span><select class="cs-input" name="grade" required></select></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="cs-modal" id="modal-activate" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="activate-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>تفعيل يدوي</h2></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label">الكورس</span><select class="cs-input" name="course_id"></select></label>
                <label class="cs-field"><span class="cs-label">الأيام</span><input class="cs-input" type="number" name="duration_days" min="1" max="366" value="30"></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">تفعيل</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script>window.STUDENT_ID = @json($student);</script>
    <script src="/js/report.js?v=4"></script>
    <script src="/js/admin/student-report.js?v=3"></script>
@endpush
