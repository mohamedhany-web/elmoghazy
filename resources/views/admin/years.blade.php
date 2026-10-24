@extends("admin.layout")

@section("title", "السنوات")

@section("content")
    <nav class="cs-crumb"><span>المحتوى</span><span>/</span><strong>السنوات</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>السنوات الدراسية</h1>
            <p>أضف السنة من هنا. شهورها صورتها ومحتواها من صفحة الشهور.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-stage" data-act="stage-new">سنة جديدة</button>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم السنة"></label>
                <label class="cs-field"><span class="cs-label">الشهور</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="filled">فيها شهور</option>
                        <option value="empty">لسه فاضية</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body" id="stage-list"></div>
    </article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>

    <div class="cs-modal" id="modal-stage" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="stage-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="stage-form-title">سنة دراسية</h2><p>الاسم اللي الطالب والمدرس هيشوفوه.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="id">
                <label class="cs-field"><span class="cs-label">اسم السنة</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">وصف قصير</span><input class="cs-input" name="subtitle"></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script src="/js/admin/years.js?v=4"></script>
@endpush
