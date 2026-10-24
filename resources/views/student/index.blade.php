@extends("student.layout")

@section("title", "الرئيسية")

@push("styles")
    <link rel="stylesheet" href="/css/student/course.css?v=5">
@endpush

@section("content")
    <div class="cv">
        <header class="cv-page">
            <div>
                <p class="stu-chip" id="stu-grade">السنة الدراسية</p>
                <h1 id="stu-hello">أهلاً</h1>
                <p id="stu-lead">شهور الشرح والامتحانات اللي تفعيلها شغّال على حسابك.</p>
                <p class="cv-note" id="stu-progress-label">جاري التحميل</p>
            </div>
            <div class="cv-hero__acts">
                <a class="cv-btn" href="/learn/months">شهور الشرح</a>
                <button type="button" class="cv-btn-dark" id="open-redeem">كود اشتراك</button>
            </div>
        </header>

        <p id="learn-note" class="desk-note" hidden></p>

        <section id="subscribe-gate" class="stu-panel subscribe-gate" hidden>
            <h2>ادخل كود الاشتراك</h2>
            <p class="desk-copy">الكود يفعّل الشهر على الجهاز ده، ومرة واحدة بس.</p>
            <form id="subscribe-form" class="subscribe-form">
                <input name="code" required autocomplete="off" autocapitalize="characters" placeholder="XXXX-XXXX-XXXX" aria-label="كود الاشتراك">
                <button type="submit" class="stu-cta stu-cta--primary">تفعيل</button>
            </form>
        </section>

        <div class="cv-stats">
            <a class="cv-stat" href="/learn/months"><div><span>شهور مفعّلة</span><strong id="stu-stat-months">0</strong></div><span class="cv-bubble cv-bubble--lilac" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 4h12a1 1 0 0 1 1 1v15l-7-3-7 3V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></span></a>
            <a class="cv-stat" href="/learn/exams"><div><span>امتحانات</span><strong id="stu-stat-exams">0</strong></div><span class="cv-bubble cv-bubble--sky" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 4h8a1 1 0 0 1 1 1v15l-5-2.5L7 20V5a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.7"/></svg></span></a>
            <a class="cv-stat" href="/learn/months"><div><span>أقرب نهاية</span><strong id="stu-stat-until">—</strong></div><span class="cv-bubble cv-bubble--amber" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 9v4l2.5 1.5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span></a>
            <a class="cv-stat" href="/learn/months"><div><span>فيديوهات</span><strong id="stu-stat-videos">0</strong></div><span class="cv-bubble cv-bubble--mint" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 8.5v7l6-3.5z" fill="currentColor"/></svg></span></a>
        </div>

        <div id="learn-body" class="stu-body">
            <div class="stu-split">
                <section class="stu-panel" id="months">
                    <div class="stu-head">
                        <div>
                            <h2>شهور الشرح</h2>
                            <p>كل شهر بطاقة. افتحها عشان تشوف الأقسام والفيديوهات.</p>
                        </div>
                        <a href="/learn/months">صفحة الشهور</a>
                    </div>
                    <div id="activation-strip" class="stu-chips"></div>
                    <div id="learn-months" class="stu-stack"></div>
                </section>
                <section class="stu-panel" id="home-exams">
                    <div class="stu-head">
                        <div>
                            <h2>الامتحانات</h2>
                            <p>أقرب الامتحانات.</p>
                        </div>
                        <a href="/learn/exams">عرض الكل</a>
                    </div>
                    <div id="learn-exams" class="stu-stack"></div>
                </section>
            </div>
            <article id="month-player" class="stu-panel" hidden>
                <h2 id="month-player-title"></h2>
                <video id="student-player" class="desk-player" controls controlsList="nodownload" playsinline></video>
                <div id="month-player-files" class="desk-files"></div>
            </article>
            <article id="lesson-view" class="stu-panel" hidden></article>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="/js/student/learn.js?v=10"></script>
@endpush
