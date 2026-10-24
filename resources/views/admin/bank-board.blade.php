@extends("admin.layout")

@section("title", "إدارة البنك")

@section("content")
    <nav class="cs-crumb" id="bank-crumb"><a href="/admin/banks">بنوك الأسئلة</a></nav>
    <header class="cs-page-head">
        <div>
            <h1 id="board-title">الإدارة</h1>
            <p id="board-copy">العناصر في جدول واحد.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" id="board-add">إضافة</button>
    </header>
    <div id="board-list"></div>

    <div class="cs-modal" id="modal-title" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="title-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="title-form-heading">اسم</h2></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label" id="title-form-label">الاسم</span><input class="cs-input" name="title" required></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script>
        window.BANK_BOARD = @json($board);
    </script>
    <script src="/js/admin/bank-board.js?v=2"></script>
@endpush
