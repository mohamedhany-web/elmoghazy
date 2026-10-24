@extends("admin.layout")

@section("title", "بنوك الأسئلة")

@section("content")
    <nav class="cs-crumb"><span>المحتوى</span><span>/</span><strong>بنوك الأسئلة</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>بنوك الأسئلة</h1>
            <p>كل سنة صف في الجدول. التفاصيل تفتح وحداتها.</p>
        </div>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم السنة"></label>
                <label class="cs-field"><span class="cs-label">الأسئلة</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="filled">فيها أسئلة</option>
                        <option value="empty">لسه فاضية</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <div id="bank-list"></div>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>
@endsection

@push("scripts")
    <script src="/js/admin/banks.js?v=5"></script>
@endpush
