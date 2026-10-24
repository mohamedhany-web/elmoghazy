@extends("admin.layout")

@section("title", "الامتحانات")

@section("content")
    <nav class="cs-crumb"><span>المحتوى</span><span>/</span><strong>الامتحانات</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>الامتحانات</h1>
            <p>الامتحان الدائم يفضل متاح طول ما هو منشور. أسئلته تتختار من بنك السنة.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-exam" data-act="exam-new">امتحان جديد</button>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم الامتحان أو السنة"></label>
                <label class="cs-field"><span class="cs-label">السنة</span><select class="cs-input" data-page-filter="year" id="exam-year"><option value="">كل السنوات</option></select></label>
                <label class="cs-field"><span class="cs-label">الإتاحة</span>
                    <select class="cs-input" data-page-filter="kind">
                        <option value="">الكل</option>
                        <option value="permanent">دائم</option>
                        <option value="timed">له موعد إغلاق</option>
                    </select>
                </label>
                <label class="cs-field"><span class="cs-label">النشر</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="published">منشور</option>
                        <option value="hidden">مخفي</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body" id="exam-list"></div>
    </article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>

    <div class="cs-modal" id="modal-exam" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="exam-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>امتحان جديد</h2><p>بعد الحفظ هتختار الأسئلة من بنك السنة.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label">اسم الامتحان</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span><select class="cs-input" name="grade_id" id="exam-grade" required></select></label>
                <label class="cs-field"><span class="cs-label">تعليمات للطالب</span><textarea class="cs-input" name="instructions" rows="2"></textarea></label>
                <label class="cs-check"><input type="checkbox" name="permanent" checked> امتحان دائم، يفضل متاح طول ما هو منشور</label>
                <label class="cs-field"><span class="cs-label">المدة بالدقائق، فاضي يعني من غير مؤقت</span><input class="cs-input" name="duration_minutes" type="number" min="1" max="300"></label>
                <label class="cs-field"><span class="cs-label">درجة النجاح بالمئة، فاضي يعني من غير نجاح ورسوب</span><input class="cs-input" name="pass_percent" type="number" min="1" max="100"></label>
                <label class="cs-field" id="exam-close-field" hidden><span class="cs-label">يقفل في</span><input class="cs-input" name="closes_at" type="datetime-local"></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script src="/js/admin/exams.js?v=3"></script>
@endpush
