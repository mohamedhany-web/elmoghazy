@extends("admin.layout")

@section("title", "التفعيلات")

@section("content")
    <nav class="cs-crumb"><span>التشغيل</span><span>/</span><strong>التفعيلات</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>التفعيلات</h1>
            <p>كل التفعيلات، وتفعيل طالب من اللوحة من غير كود.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-activate">تفعيل</button>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" id="activation-search" placeholder="اسم الطالب أو الكورس"></label>
                <label class="cs-field"><span class="cs-label">الحالة</span>
                    <select class="cs-input" id="activation-status">
                        <option value="">الكل</option>
                        <option value="active">ساري</option>
                        <option value="locked">مقفول</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body cs-panel__body--table" id="activation-list"></div>
        <div class="cs-pager" id="activation-pager" hidden>
            <button type="button" class="cs-btn cs-btn--ghost" data-activation-page="prev">السابق</button>
            <span id="activation-pager-label"></span>
            <button type="button" class="cs-btn cs-btn--ghost" data-activation-page="next">التالي</button>
        </div>
    </article>

    <div class="cs-modal" id="modal-activate" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="activate-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>تفعيل طالب</h2><p>التفعيل لطالب واحد، ومدة باقي الطلبة ما بتتغيرش.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="student_id">
                <label class="cs-field"><span class="cs-label">الطالب</span><input class="cs-input" id="activate-student-search" placeholder="اسم أو تليفون" autocomplete="off"></label>
                <div id="activate-student-picks"></div>
                <p id="activate-student-name"></p>
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
    <script src="/js/admin/activations.js?v=5"></script>
@endpush
