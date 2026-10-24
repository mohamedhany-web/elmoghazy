@extends("admin.layout")

@section("title", "لوحة التحكم")

@section("content")
    <nav class="cs-crumb"><span>التشغيل</span><span>/</span><strong>لوحة التحكم</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>لوحة التحكم</h1>
            <p>التفعيل الساري، المقفل، واستهلاك الأكواد.</p>
        </div>
        <div class="cs-page-head__acts">
            <a class="cs-btn cs-btn--ghost" href="/admin/activations">التفعيلات</a>
            <a class="cs-btn cs-btn--primary" href="/admin/codes">إصدار كود</a>
        </div>
    </header>
    <div id="summary-stats" class="cs-stats"></div>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="اسم الطالب أو الكورس"></label>
                <label class="cs-field"><span class="cs-label">الحالة</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="active">ساري</option>
                        <option value="locked">مقفول</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__head">
            <div>
                <h2>آخر التفعيلات</h2>
                <p>مد المدة من هنا من غير ما تغيّر باقي الطلبة.</p>
            </div>
            <a class="cs-btn cs-btn--ghost" href="/admin/activations">الكل</a>
        </div>
        <div class="cs-panel__body" id="dash-activations"></div>
    </article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>
@endsection

@push("scripts")
    <script src="/js/admin/dashboard.js?v=4"></script>
@endpush
