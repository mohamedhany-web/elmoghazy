@extends("admin.layout")

@section("title", "الكورسات")

@section("content")
    <nav class="cs-crumb"><span>التشغيل</span><span>/</span><strong>الكورسات</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>الكورسات</h1>
            <p>النشر، ترتيب الحصص، ومحتوى كل كورس.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-course">كورس جديد</button>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم الكورس"></label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span>
                    <select class="cs-input" data-page-filter="year" id="course-year">
                        <option value="">كل السنوات</option>
                    </select>
                </label>
                <label class="cs-field"><span class="cs-label">الظهور</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="published">ظاهر</option>
                        <option value="hidden">مخفي</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body" id="course-list"></div>
    </article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>

    <div class="cs-modal" id="modal-course" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="course-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="course-form-title">كورس جديد</h2><p>يظهر في الكتالوج بعد النشر.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="id">
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span><select class="cs-input" name="grade" required></select></label>
                <label class="cs-field"><span class="cs-label">الاسم</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">الوصف</span><textarea class="cs-input" name="description" required rows="3"></textarea></label>
                <label class="cs-check"><input type="checkbox" name="published"> ظاهر في الكتالوج</label>
                <label class="cs-check"><input type="checkbox" name="unlock_in_order"> الحصص بالترتيب</label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="cs-modal" id="modal-lesson" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="lesson-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="lesson-form-title">حصة جديدة</h2><p id="lesson-course-name"></p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="course_id">
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
    <script src="/js/admin/courses.js?v=5"></script>
@endpush
