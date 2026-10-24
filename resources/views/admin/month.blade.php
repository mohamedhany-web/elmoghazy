@extends("admin.layout")

@section("title", "محتوى الشهر")

@section("content")
    <nav class="cs-crumb"><span>المحتوى</span><span>/</span><a href="/admin/months">الشهور</a><span>/</span><strong id="month-crumb">الشهر</strong></nav>
    <header class="cs-page-head">
        <div>
            <a class="cs-btn cs-btn--ghost" href="/admin/months">رجوع</a>
            <h1 id="month-detail-title">الشهر</h1>
            <p id="month-detail-copy"></p>
        </div>
        <div class="cs-page-head__acts">
            <button type="button" class="cs-btn cs-btn--ghost" data-act="month-edit">تعديل الظهور</button>
            <button type="button" class="cs-btn cs-btn--primary" data-act="unit-add">قسم جديد</button>
        </div>
    </header>
    <div class="cs-stats" id="month-stats"></div>
    <div class="upload-dock" id="upload-dock" hidden>
        <div class="upload-dock__top">
            <strong id="upload-label">رفع الفيديو</strong>
            <span id="upload-percent">0%</span>
        </div>
        <div class="upload-dock__track" aria-hidden="true"><span id="upload-fill"></span></div>
        <p id="upload-note">الرفع على كلاود فلير</p>
    </div>
    <article class="cs-panel" id="month-builder" data-month="{{ $month }}">
        <div class="cs-panel__body">
            <select id="month-status-filter" data-page-filter="status" hidden aria-hidden="true">
                <option value=""></option>
                <option value="published">ظاهر</option>
                <option value="hidden">مخفي</option>
            </select>
            <div id="month-identity"></div>
            <video id="admin-player" class="outline-player" controls controlsList="nodownload" playsinline hidden></video>
            <div id="month-detail-body"></div>
            <p id="page-filter-empty" class="outline-empty" hidden>مفيش نتائج.</p>
        </div>
    </article>

    <div class="cs-modal" id="modal-month" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="month-build" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2>ظهور الشهر</h2><p>الصورة والاسم والوصف يظهروا في الكتالوج وعند الطالب.</p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <label class="cs-field"><span class="cs-label">اسم الشهر</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">الوصف</span><textarea class="cs-input" name="description" rows="3"></textarea></label>
                <label class="cs-field"><span class="cs-label">صورة الشهر</span><input class="cs-input" type="file" name="cover" accept="image/jpeg,image/png,image/webp"></label>
                <label class="cs-field"><span class="cs-label">قفل فيديوهات الشهر في</span><input class="cs-input" type="datetime-local" name="locks_at"></label>
                <p class="outline-hint">بعد التاريخ الفيديو يفضل ظاهر ويتقفل. القسم أو الفيديو يقدر ياخد تاريخ أقرب.</p>
                <label class="cs-check"><input type="checkbox" name="published"> ظاهر في الكتالوج وعند الطالب</label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="cs-modal" id="modal-unit" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="unit-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="unit-form-title">قسم جديد</h2><p id="unit-month-name"></p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="month_id" value="{{ $month }}">
                <input type="hidden" name="unit_id">
                <label class="cs-field"><span class="cs-label">اسم القسم</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">الوصف اللي الطالب هيقراه</span><textarea class="cs-input" name="description" rows="3"></textarea></label>
                <label class="cs-field"><span class="cs-label">قفل فيديوهات القسم في</span><input class="cs-input" type="datetime-local" name="locks_at"></label>
                <p class="outline-hint">لو فاضي، فيديوهات القسم تاخد تاريخ قفل الشهر.</p>
                <label class="cs-check"><input type="checkbox" name="published" checked> القسم ظاهر للطالب</label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">حفظ</button>
            </div>
        </form>
    </div>

    <div class="cs-modal" id="modal-video" hidden>
        <button type="button" class="cs-modal__backdrop" data-close aria-label="إغلاق"></button>
        <form id="video-form" class="cs-modal__panel">
            <div class="cs-modal__head">
                <div><h2 id="video-form-title">فيديو جديد</h2><p id="video-unit-name"></p></div>
                <button type="button" class="cs-modal__x" data-close aria-label="إغلاق">×</button>
            </div>
            <div class="cs-modal__body">
                <input type="hidden" name="unit_id">
                <input type="hidden" name="video_id">
                <label class="cs-field"><span class="cs-label">العنوان</span><input class="cs-input" name="title" required></label>
                <label class="cs-field"><span class="cs-label">الوصف</span><textarea class="cs-input" name="description" rows="3"></textarea></label>
                <label class="cs-field"><span class="cs-label">قفل هذا الفيديو في</span><input class="cs-input" type="datetime-local" name="locks_at"></label>
                <p class="outline-hint">لو فاضي، الفيديو ياخد قفل القسم، وبعدين قفل الشهر. بعد التاريخ يفضل ظاهر ويتقفل.</p>
                <label class="cs-field"><span class="cs-label">الفيديو (حتى 2 جيجا)</span><input class="cs-input" type="file" name="video" accept="video/mp4,video/webm,video/quicktime"></label>
                <label class="cs-field"><span class="cs-label">مرفقات</span><input class="cs-input" type="file" name="attachments[]" multiple></label>
                <label class="cs-check"><input type="checkbox" name="published" checked> ظاهر للطالب</label>
            </div>
            <div class="cs-modal__foot">
                <button type="button" class="cs-btn cs-btn--ghost" data-close>إلغاء</button>
                <button type="submit" class="cs-btn cs-btn--primary">رفع</button>
            </div>
        </form>
    </div>
@endsection

@push("scripts")
    <script src="/js/admin/month.js?v=14"></script>
@endpush
