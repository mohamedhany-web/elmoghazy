@extends("admin.layout")

@section("title", "الطلبة")

@section("content")
    <nav class="cs-crumb"><span>الخدمات</span><span>/</span><strong>الطلبة</strong></nav>
    <header class="cs-page-head">
        <div>
            <h1>الطلبة</h1>
            <p>حسابات الصف، التفعيل اليدوي، والقفل.</p>
        </div>
        <div class="cs-page-head__acts">
            <button type="button" class="cs-btn cs-btn--ghost" id="student-export">تصدير</button>
            <button type="button" class="cs-btn cs-btn--primary" data-open-modal="modal-student">إضافة طالب</button>
        </div>
    </header>
    <article class="cs-panel">
        <div class="cs-panel__body">
            <div class="cs-filter-grid">
                <label class="cs-field"><span class="cs-label">بحث</span><input class="cs-input" id="student-search" placeholder="الاسم أو التليفون أو كود الحماية"></label>
                <label class="cs-field"><span class="cs-label">الحالة</span>
                    <select class="cs-input" id="student-status">
                        <option value="">الكل</option>
                        <option value="active">نشط</option>
                        <option value="disabled">متوقف</option>
                    </select>
                </label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span>
                    <select class="cs-input" id="student-year">
                        <option value="">كل السنوات</option>
                    </select>
                </label>
            </div>
        </div>
    </article>
    <article class="cs-panel">
        <div class="cs-panel__body cs-panel__body--table" id="student-list"></div>
        <div class="cs-pager" id="student-pager" hidden>
            <button type="button" class="cs-btn cs-btn--ghost" data-student-page="prev">السابق</button>
            <span id="student-pager-label"></span>
            <button type="button" class="cs-btn cs-btn--ghost" data-student-page="next">التالي</button>
        </div>
    </article>

    <div class="cs-modal" id="modal-student" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="student-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="student-form-title">طالب جديد</h2><p id="student-form-copy">الحساب يتسجل طالبًا نشطًا.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="id">
                <label class="cs-field"><span class="cs-label">الاسم الأول</span><input class="cs-input" name="first_name" required></label>
                <label class="cs-field"><span class="cs-label">الاسم الثاني</span><input class="cs-input" name="second_name" required></label>
                <label class="cs-field"><span class="cs-label">رقم التليفون</span><input class="cs-input" name="phone" required inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx"></label>
                <label class="cs-field"><span class="cs-label">رقم ولي الأمر</span><input class="cs-input" name="parent_phone" required inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx"></label>
                <label class="cs-field"><span class="cs-label">كلمة المرور</span><input class="cs-input" type="password" name="password" required minlength="8"></label>
                <label class="cs-field"><span class="cs-label">السنة الدراسية</span><select class="cs-input" name="grade" required></select></label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="cs-modal" id="modal-activate" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="activate-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>تفعيل يدوي</h2><p id="activate-student-name"></p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="student_id">
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
    <script src="/js/admin/students.js?v=10"></script>
@endpush
