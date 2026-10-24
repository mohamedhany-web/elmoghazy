@extends("admin.layout")

@section("title", "بنك الأسئلة")

@section("content")
    <nav class="cs-crumb"><a href="/admin/banks">بنوك الأسئلة</a><span>/</span><strong id="bank-crumb">البنك</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1 id="bank-title">بنك السنة</h1>
            <p id="bank-stats">السنة، ثم الوحدة، ثم الدرس، ثم الموضوع، وبعدين الأسئلة.</p>
        </div>
        <div class="cs-row-acts">
            <button type="button" class="cs-btn cs-btn--ghost" data-act="import-open">استيراد</button>
            <button type="button" class="cs-btn cs-btn--ghost" data-act="unit-new">وحدة جديدة</button>
        </div>
    </header>
    <div class="bank-meter" id="bank-meter"></div>

    <div class="bank-workspace">
        <aside class="cs-panel bank-rail">
            <div class="cs-panel__head"><h2>التصنيف</h2></div>
            <div class="cs-panel__body" id="bank-rail"></div>
        </aside>
        <div class="bank-main">
            <article class="cs-panel">
                <div class="cs-panel__body">
                    <div class="cs-filter-grid bank-filters">
                        <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" id="bank-search" placeholder="نص السؤال أو وسم"></label>
                        <label class="cs-field"><span class="cs-label">النوع</span><select class="cs-input" id="filter-type"><option value="">الكل</option></select></label>
                        <label class="cs-field"><span class="cs-label">الصعوبة</span>
                            <select class="cs-input" id="filter-difficulty">
                                <option value="">الكل</option>
                                <option value="easy">سهل</option>
                                <option value="medium">متوسط</option>
                                <option value="hard">صعب</option>
                            </select>
                        </label>
                        <label class="cs-field"><span class="cs-label">الحالة</span>
                            <select class="cs-input" id="filter-status">
                                <option value="">الكل</option>
                                <option value="published">منشور</option>
                                <option value="draft">مسودة</option>
                                <option value="archived">مؤرشف</option>
                            </select>
                        </label>
                    </div>
                    <div class="cs-row-acts" style="margin-top:12px">
                        <button type="button" class="cs-btn cs-btn--ghost" id="topic-new" data-act="topic-new" hidden>موضوع</button>
                        <button type="button" class="cs-btn cs-btn--ghost" id="lesson-rename" data-act="lesson-rename" hidden>تعديل الدرس</button>
                        <button type="button" class="cs-btn cs-btn--ghost" id="lesson-delete" data-act="lesson-delete" hidden>حذف الدرس</button>
                        <button type="button" class="cs-btn cs-btn--ghost" id="question-start" disabled>سؤال جديد</button>
                    </div>
                </div>
            </article>

            <div class="bank-bulk" id="bulk-bar" hidden>
                <strong id="bulk-count">0</strong>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="publish">نشر</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="draft">مسودة</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="archive">أرشفة</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="difficulty">صعوبة</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="tag">وسم</button>
                <button type="button" class="cs-btn cs-btn--ghost" data-act="bulk" data-bulk="delete">حذف</button>
            </div>

            <div class="cs-modal" id="modal-question" hidden>
                <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
                <form id="question-form" class="cs-modal__panel cs-modal__panel--wide">
                    <div class="cs-modal__head">
                        <div>
                            <h2 id="composer-title">سؤال جديد</h2>
                            <p id="composer-place"></p>
                        </div>
                        <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
                    </div>
                    <div class="cs-modal__body">
                    <input type="hidden" name="question_id">
                    <input type="hidden" name="lesson_id">
                    <div class="cs-filter-grid bank-filters">
                        <label class="cs-field"><span class="cs-label">نوع السؤال</span><select class="cs-input" name="type" id="question-type"></select></label>
                        <label class="cs-field"><span class="cs-label">الصعوبة</span>
                            <select class="cs-input" name="difficulty">
                                <option value="2">سهل</option>
                                <option value="3" selected>متوسط</option>
                                <option value="4">صعب</option>
                            </select>
                        </label>
                        <label class="cs-field"><span class="cs-label">الدرجة</span><input class="cs-input" name="marks" type="number" min="0.25" max="100" step="0.25" value="1"></label>
                        <label class="cs-field"><span class="cs-label">الحالة</span>
                            <select class="cs-input" name="status">
                                <option value="published">منشور</option>
                                <option value="draft">مسودة</option>
                                <option value="archived">مؤرشف</option>
                            </select>
                        </label>
                    </div>
                    <label class="cs-field"><span class="cs-label">نص السؤال</span><textarea class="cs-input" name="prompt" rows="4" required></textarea></label>
                    <div id="type-fields"></div>
                    <div class="cs-filter-grid bank-filters">
                        <label class="cs-field"><span class="cs-label">الشرح بعد الإجابة</span><textarea class="cs-input" name="explanation" rows="2"></textarea></label>
                        <label class="cs-field"><span class="cs-label">تلميح</span><input class="cs-input" name="hint" placeholder="يظهر للطالب وهو بيحل"></label>
                        <label class="cs-field"><span class="cs-label">خصم الغلط</span><input class="cs-input" name="negative_mark" type="number" min="0" max="100" step="0.25" placeholder="0"></label>
                        <label class="cs-field"><span class="cs-label">وسوم</span><input class="cs-input" name="tags" placeholder="مراجعة، 2026"></label>
                        <label class="cs-field"><span class="cs-label">الموضوع</span><select class="cs-input" name="topic_id" id="question-topic"><option value="">من غير موضوع</option></select></label>
                    </div>
                    </div>
                    <div class="cs-modal__foot">
                        <button type="button" class="cs-btn cs-btn--ghost" data-close>إغلاق</button>
                        <button type="submit" class="cs-btn cs-btn--ghost" id="question-next" value="next">حفظ وإضافة التالي</button>
                        <button type="submit" class="cs-btn cs-btn--primary" id="question-save">حفظ السؤال</button>
                    </div>
                </form>
            </div>

            <article class="cs-panel">
                <div class="cs-panel__body cs-panel__body--table">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="pick-all" aria-label="تحديد الصفحة"></th>
                                <th>السؤال</th>
                                <th>التصنيف</th>
                                <th>النوع</th>
                                <th>الصعوبة</th>
                                <th>الدرجة</th>
                                <th>الاستخدام</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="question-rows"></tbody>
                    </table>
                </div>
            </article>
            <div class="bank-pager" id="bank-pager"></div>
        </div>
    </div>

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

    <div class="cs-modal" id="modal-import" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="import-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>استيراد أسئلة</h2><p>سطر للعناوين ثم سطر لكل سؤال. الأعمدة: prompt,type,a,b,c,d,correct,difficulty,marks</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <textarea class="cs-input" name="csv" rows="8" required placeholder="prompt,type,a,b,c,d,correct,difficulty,marks"></textarea>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--ghost">استيراد للدرس المفتوح</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script>window.BANK_GRADE = @json($grade);</script>
    <script src="/js/admin/bank.js?v=8"></script>
@endpush
