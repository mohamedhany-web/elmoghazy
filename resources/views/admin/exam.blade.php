@extends("admin.layout")

@section("title", "بناء الامتحان")

@section("content")
    <nav class="cs-crumb"><a href="/admin/exams">الامتحانات</a><span>/</span><strong id="exam-crumb">الامتحان</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1 id="exam-title">الامتحان</h1>
            <p id="exam-copy">اختار الأسئلة من بنك السنة، وحدد درجة كل سؤال.</p>
        </div>
        <button type="button" class="cs-btn cs-btn--ghost" id="exam-publish" data-published="0">نشر</button>
    </header>
    <article class="cs-panel fold is-open">
        <div class="cs-panel__head"><button type="button" class="fold-toggle" data-fold aria-expanded="true"><h2>إعدادات الامتحان</h2></button></div>
        <div class="fold-body">
        <div class="cs-panel__body">
            <form id="exam-settings" class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">الاسم</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">الشهر</span><select class="cs-input" name="study_month_id"><option value="">من غير شهر</option></select></label>
                <label class="cs-field"><span class="cs-label">المدة بالدقائق</span><input class="cs-input" name="duration_minutes" type="number" min="5" max="480" placeholder="5–480"></label>
                <label class="cs-field"><span class="cs-label">درجة النجاح %</span><input class="cs-input" name="pass_percent" type="number" min="1" max="100"></label>
                <label class="cs-field"><span class="cs-label">عدد المحاولات</span><input class="cs-input" name="attempts_allowed" type="number" min="0" max="10" value="0" placeholder="0 = غير محدود"></label>
                <label class="cs-check"><input type="checkbox" name="permanent" checked> دائم</label>
                <label class="cs-check"><input type="checkbox" name="shuffle_questions"> خلط ترتيب الأسئلة مرة واحدة لكل محاولة</label>
                <label class="cs-check"><input type="checkbox" name="shuffle_choices"> خلط الاختيارات مرة واحدة لكل محاولة</label>
                <label class="cs-check"><input type="checkbox" name="show_results" checked> إظهار النتيجة بعد التسليم</label>
                <label class="cs-check"><input type="checkbox" name="show_answers" checked> تمييز الإجابة الصحيحة</label>
                <label class="cs-check"><input type="checkbox" name="show_explanations" checked> إظهار الشرح</label>
                <label class="cs-check"><input type="checkbox" name="allow_review" checked> مراجعة الإجابات</label>
                <label class="cs-check"><input type="checkbox" name="prevent_tab_switch"> تسليم تلقائي بعد 3 تبديلات للتبويب</label>
                <label class="cs-field"><span class="cs-label">يفتح في</span><input class="cs-input" name="opens_at" type="datetime-local"></label>
                <label class="cs-field" id="settings-close" hidden><span class="cs-label">يقفل في</span><input class="cs-input" name="closes_at" type="datetime-local"></label>
                <label class="cs-field"><span class="cs-label">تعليمات</span><textarea class="cs-input" name="instructions" rows="2"></textarea></label>
                <div class="cs-row-acts"><button type="submit" class="cs-btn cs-btn--ghost">حفظ الإعدادات</button></div>
            </form>
        </div>
        </div>
    </article>
    <article class="cs-panel fold">
        <div class="cs-panel__head"><button type="button" class="fold-toggle" data-fold aria-expanded="false"><h2>بحث في البنك</h2></button></div>
        <div class="fold-body">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث في البنك</span><input class="cs-input" id="pick-search" placeholder="نص السؤال"></label>
                <label class="cs-field"><span class="cs-label">الوحدة</span><select class="cs-input" id="pick-unit"><option value="">كل الوحدات</option></select></label>
            </div>
        </div>
        </div>
    </article>
    <article class="cs-panel fold is-open">
        <div class="cs-panel__head"><button type="button" class="fold-toggle" data-fold aria-expanded="true"><h2 id="pick-count">أسئلة البنك</h2></button><button type="button" class="cs-btn cs-btn--primary" id="save-picks">حفظ الأسئلة المختارة</button></div>
        <div class="fold-body">
        <div class="cs-panel__body cs-panel__body--table">
            <table class="cs-table">
                <thead><tr><th></th><th>السؤال</th><th>مكانه</th><th>الدرجة</th></tr></thead>
                <tbody id="pick-list"></tbody>
            </table>
        </div>
        <div class="bank-pager" id="pick-pager"></div>
        </div>
    </article>
    <article class="cs-panel fold">
        <div class="cs-panel__head"><button type="button" class="fold-toggle" data-fold aria-expanded="false"><h2>سحب تلقائي من البنك</h2></button></div>
        <div class="fold-body">
        <form id="generate-form" class="cs-panel__body cs-filter-grid">
            <label class="cs-field"><span class="cs-label">الوحدة</span><select class="cs-input" name="unit" id="generate-unit"><option value="">كل الوحدات</option></select></label>
            <label class="cs-field"><span class="cs-label">الصعوبة</span>
                <select class="cs-input" name="difficulty"><option value="">الكل</option><option value="easy">سهل</option><option value="medium">متوسط</option><option value="hard">صعب</option></select>
            </label>
            <label class="cs-field"><span class="cs-label">العدد</span><input class="cs-input" name="count" type="number" min="1" max="50" value="5" required></label>
            <div class="cs-row-acts"><button type="submit" class="cs-btn cs-btn--ghost">أضف للامتحان</button></div>
        </form>
        </div>
    </article>
    <article class="cs-panel fold">
        <div class="cs-panel__head"><button type="button" class="fold-toggle" data-fold aria-expanded="false"><h2>التصحيح</h2></button></div>
        <div class="fold-body">
        <div class="cs-panel__body" id="attempt-list"><p class="cs-empty">لسه مفيش تسليمات.</p></div>
        </div>
    </article>
@endsection

@push("scripts")
    <script>window.EXAM_ID = @json($exam);</script>
    <script src="/js/admin/exam.js?v=4"></script>
@endpush
