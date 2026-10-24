@extends("admin.layout")

@section("title", "الطلبات")

@section("content")
    <nav class="cs-crumb"><span>التشغيل</span><span>/</span><strong>الطلبات</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>طلبات الشراء</h1>
            <p>الطلب وصل من صفحة الشهر. الدفع بره المنصة، وبعد التأكيد تصدر الكود أو تفعّل الطالب.</p>
        </div>
            <a class="cs-btn cs-btn--ghost" href="/admin/codes">إصدار كود</a>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" data-page-search placeholder="الاسم أو الموبايل أو الشهر"></label>
                <label class="cs-field"><span class="cs-label">الحالة</span>
                    <select class="cs-input" data-page-filter="status">
                        <option value="">الكل</option>
                        <option value="pending">جديد</option>
                        <option value="done">تم</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body" id="request-list"></div>
    </article>
    <p id="page-filter-empty" class="cs-empty" hidden>مفيش نتائج مطابقة للبحث أو الفلتر.</p>
@endsection

@push("scripts")
    <script src="/js/admin/requests.js?v=5"></script>
@endpush
