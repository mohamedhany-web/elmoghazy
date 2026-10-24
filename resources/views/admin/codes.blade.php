@extends("admin.layout")

@section("title", "الأكواد")

@push("styles")
    <link rel="stylesheet" href="/css/admin/codes.css?v=2">
@endpush

@section("content")
    <nav class="cs-crumb"><span>التشغيل</span><span>/</span><strong>الأكواد</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>الأكواد</h1>
            <p>كل توليد بيتسجل كدفعة. تقدر تفتحها وتعيد طباعتها في أي وقت، وتشوف المستخدم والمتبقي.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-code">إصدار أكواد</button>
    </header>

    <article class="cs-panel">
        <div class="cs-panel__head">
            <div>
                <h2>دفعات التوليد</h2>
                <p>كل مرة بتولّد فيها أكواد بتظهر هنا كمجموعة.</p>
            </div>
        </div>
        <div class="cs-panel__body cs-panel__body--table" id="batch-list"></div>
        <div class="cs-pager" id="batch-pager" hidden>
            <button type="button" class="cs-btn cs-btn--ghost" data-batch-page="prev">السابق</button>
            <span id="batch-pager-label"></span>
            <button type="button" class="cs-btn cs-btn--ghost" data-batch-page="next">التالي</button>
        </div>
    </article>

    <article class="cs-panel" id="sheet" hidden>
        <div class="cs-panel__head">
            <div>
                <h2 id="sheet-title">ورقة الدفعة</h2>
                <p id="sheet-copy">الجدول جاهز للطباعة وإعادة الاستخدام.</p>
            </div>
            <div class="cs-mint-sheet__toolbar">
                <button type="button" class="cs-btn cs-btn--ghost" id="code-copy">نسخ</button>
                <button type="button" class="cs-btn cs-btn--ghost" id="code-export">تصدير Excel</button>
                <button type="button" class="cs-btn cs-btn--primary" id="code-print">طباعة PDF</button>
            </div>
        </div>
        <div class="cs-panel__body">
            <div id="code-print-sheet" class="cs-mint-print"></div>
        </div>
    </article>

    <article class="cs-panel">
        <div class="cs-panel__head">
            <div>
                <h2>كل الأكواد</h2>
                <p>بحث سريع في الأكواد الفردية.</p>
            </div>
        </div>
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" id="code-search" placeholder="آخر الكود أو الطالب أو الشهر"></label>
                <label class="cs-field"><span class="cs-label">الحالة</span>
                    <select class="cs-input" id="code-status">
                        <option value="">الكل</option>
                        <option value="open">متاح</option>
                        <option value="used">مستخدم</option>
                        <option value="expired">منتهي</option>
                        <option value="revoked">ملغي</option>
                    </select>
                </label>
            </div>
        </div>
        <div class="cs-panel__body cs-panel__body--table" id="code-list"></div>
        <div class="cs-pager" id="code-pager" hidden>
            <button type="button" class="cs-btn cs-btn--ghost" data-code-page="prev">السابق</button>
            <span id="code-pager-label"></span>
            <button type="button" class="cs-btn cs-btn--ghost" data-code-page="next">التالي</button>
        </div>
    </article>

    <div class="cs-modal" id="modal-code" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="code-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>إصدار أكواد</h2><p>الكود يقفل بعد صلاحيته، والشهر يتشال من الطالب بعد مدة الظهور.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label">النوع</span>
                    <select class="cs-input" name="type">
                        <option value="month">شهر</option>
                        <option value="parent">ولي أمر</option>
                    </select>
                </label>
                <div data-code-month>
                    <label class="cs-field"><span class="cs-label">الشهر</span><select class="cs-input" name="study_month_id"></select></label>
                    <label class="cs-field"><span class="cs-label">عدد الأكواد</span><input class="cs-input" type="number" name="count" min="1" max="500" value="10"></label>
                    <label class="cs-field"><span class="cs-label">صلاحية الكود بالأيام</span><input class="cs-input" type="number" name="code_valid_days" min="1" max="366" value="30"></label>
                    <label class="cs-field"><span class="cs-label">مدة ظهور الشهر بعد التفعيل</span><input class="cs-input" type="number" name="duration_days" min="1" max="366" value="30"></label>
                </div>
                <div data-code-parent hidden>
                    <label class="cs-field"><span class="cs-label">الطالب</span><input class="cs-input" id="parent-student-search" placeholder="اسم أو تليفون" autocomplete="off"></label>
                    <div id="parent-student-picks"></div>
                    <input type="hidden" name="user_id">
                    <p id="parent-student-name"></p>
                </div>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">إصدار</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script src="/js/admin/codes.js?v=10"></script>
@endpush
