@extends("admin.layout")

@section("title", "الشهور")

@section("content")
    <nav class="cs-crumb"><span>المحتوى</span><span>/</span><strong>الشهور</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>الشهور</h1>
            <p id="month-filter" hidden></p>
        </div>
        <div class="cs-row-acts">
            <a class="cs-btn cs-btn--ghost" id="month-filter-clear" href="/admin/months" hidden>كل السنوات</a>
            <button type="button" class="cs-btn cs-btn--primary" id="year-add-month">شهر جديد</button>
        </div>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم الشهر"></label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span>
                    <select class="cs-input" data-page-filter="year" id="month-year">
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
    <article class="cs-panel"><div class="cs-panel__body cs-panel__body--table" id="month-board"></div></article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>

    <div class="cs-modal" id="modal-month" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="month-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="month-form-title">شهر جديد</h2><p>بعد تاريخ القفل الفيديوهات تقفل وتفضل ظاهرة.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="id">
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span><select class="cs-input" name="grade" required></select></label>
                <label class="cs-field"><span class="cs-label">اسم الشهر</span><input class="cs-input" name="title" required placeholder="شهر ١٠"></label>
                <label class="cs-field"><span class="cs-label">الوصف</span><textarea class="cs-input" name="description" rows="3"></textarea></label>
                <label class="cs-field"><span class="cs-label">قفل الفيديوهات في</span><input class="cs-input" type="datetime-local" name="locks_at"></label>
                <label class="cs-field"><span class="cs-label">صورة الشهر</span><input class="cs-input" type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
                <label class="cs-check"><input type="checkbox" name="published"> ظاهر للطلبة</label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script src="/js/admin/months.js?v=6"></script>
@endpush
