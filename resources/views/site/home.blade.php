@extends("site.layout")

@section("content")
    <div class="intro" id="intro">
        <div class="intro__field" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span class="is-star"></span><span></span>
            <span></span><span></span><span class="is-star"></span><span></span><span></span>
            <span></span><span></span><span></span><span class="is-star"></span><span></span>
            <span></span><span></span><span></span><span></span><span class="is-star"></span>
        </div>
        <div class="intro__shade"></div>
        <div class="intro__photo">
            <img src="/images/teacher.png?v=4" alt="مستر محمد المغازي">
        </div>
        <div class="intro__lines">
            <h1 class="display" id="intro-line">الفيزياء مش صعبة…</h1>
            <p id="intro-name" hidden>مستر محمد المغازي</p>
        </div>
        <div class="intro__enter" id="intro-enter" hidden>
            <div class="intro__swipe" id="intro-swipe">
                <div class="intro__chevron" data-icon="up"></div>
                <p>اسحب لفوق للدخول</p>
            </div>
            <button type="button" id="intro-go">ادخل المنصة</button>
        </div>
    </div>

    <div id="site" hidden>
        <div class="sky" aria-hidden="true">
            <span class="sky__blob sky__blob--a"></span>
            <span class="sky__blob sky__blob--b"></span>
            <span class="sky__formula sky__formula--a">E = mc²</span>
            <span class="sky__formula sky__formula--b">F = ma</span>
            <span class="sky__formula sky__formula--c">V = IR</span>
            <span class="sky__formula sky__formula--d">v = d / t</span>
        </div>
        @include("site.partials.nav")
        <main>
            <div class="home">
                <section class="hero wrap">
                    <div>
                        <p class="hero__kicker">منصة المغازي</p>
                        <h1 class="hero__title display"><span class="hero__line">فيزياء أوضح…</span><span class="hero__line">مع محمد المغازي</span></h1>
                        <p class="hero__lead">اختار السنة الدراسية، وشوف شهورها بس. شرح منظم، تدريبات، ومتابعة لحد الامتحان.</p>
                        <div class="hero__actions stack">
                            <a href="/#grades" class="btn btn-primary" data-start>ابدأ التعلم</a>
                            <a href="/months" class="btn btn-ghost">تصفح الشهور</a>
                            <a href="/#parent" class="btn btn-ghost">متابعة ولي الأمر</a>
                        </div>
                        <div class="quick" id="quick" hidden></div>
                    </div>
                    <div class="hero__photo">
                        <span class="orbit" aria-hidden="true"></span>
                        <img src="/images/teacher.png?v=4" alt="مستر محمد المغازي">
                    </div>
                </section>
                <div class="wave" aria-hidden="true"></div>

                <section id="grades" class="band">
                    <div class="wrap">
                        <h2 class="section-title display">اختار السنة الدراسية</h2>
                        <p class="section-copy">لما تختار السنة، الشهور اللي تحت هتبقى بتاعتها بس.</p>
                        <div id="grade-grid" class="grade-grid stagger"></div>
                        <div id="months" class="home-months">
                            <h2 class="section-title display" id="months-title">الأكثر تسجيلاً</h2>
                            <p class="section-copy" id="months-copy">ترتيب الشهور من طلبات التسجيل الفعلية. اختار صفك عشان القائمة تبقى بتاعته بس.</p>
                            <div id="continue" class="continue" hidden></div>
                            <div id="month-grid"></div>
                        </div>
                    </div>
                </section>

                <section id="why" class="why wrap">
                    <h2 class="section-title display">ليه تتعلم مع المستر؟</h2>
                    <p class="section-copy">اللي الطالب بياخده فعلًا جوّه الشهر.</p>
                    <div class="why__grid stagger">
                        @foreach ([
                            ["spark", "شرح مبسط ومنظم", "الحصة ماشية بترتيب: الفكرة، المثال، وبعدين السؤال."],
                            ["pen", "حل تدريبات وأسئلة كثيرة", "بعد كل جزء فيه تدريب، مش شرح بس وخلاص."],
                            ["check", "امتحانات دورية", "امتحان قصير يوضّح النقطة الضعيفة قبل ما تكبر."],
                            ["trend", "متابعة مستوى الطالب", "الصف والشهر والوحدة ظاهرين في الحساب."],
                            ["book", "مراجعات قبل الامتحان", "تجميع للنقاط اللي الامتحان بيسأل فيها."],
                            ["chat", "دعم وأسئلة للطلبة", "سؤال الطالب ليه مكان، مش بيتساب لبعد الامتحان."],
                        ] as $item)
                            <article class="why-card surface">
                                <span class="icon-badge" data-icon="{{ $item[0] }}"></span>
                                <h3>{{ $item[1] }}</h3>
                                <p>{{ $item[2] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="path" class="path wrap">
                    <h2 class="section-title display">رحلة الطالب</h2>
                    <p class="section-copy">من أول ما يفتح المنصة لحد المتابعة.</p>
                    <ol class="path__steps stagger">
                        <li>
                            <span>1</span>
                            <h3>اختار السنة</h3>
                            <p>السنوات اللي المدرس مفعّلها. الشهور اللي تظهر بتاعتها بس.</p>
                        </li>
                        <li>
                            <span>2</span>
                            <h3>سجّل الحساب</h3>
                            <p>الاسم، البريد، والصف. من هنا يبدأ «كمّل التعلم».</p>
                        </li>
                        <li>
                            <span>3</span>
                            <h3>اشترك في الشهر</h3>
                            <p>كود من المدرس، أو طلب شراء يوصل للوحة عشان التفعيل يتم بعد الدفع بره المنصة.</p>
                        </li>
                        <li>
                            <span>4</span>
                            <h3>ولي الأمر يتابع</h3>
                            <p>الحصص، نتيجة الامتحان، والصف المسجّل من غير ما يحضر الحصة.</p>
                        </li>
                    </ol>
                </section>

                <section id="parent" class="parent wrap">
                    <div class="parent__panel surface">
                        <div>
                            <p class="hero__kicker">متابعة ولي الأمر</p>
                            <h2 class="section-title display">ولي الأمر يشوف المستوى</h2>
                            <p class="section-copy">من غير ما يحضر الحصة: الحصص، الامتحانات، والصف المسجّل.</p>
                            <ul class="parent__points">
                                <li><span data-icon="tick"></span>الحصص اللي الطالب خلّصها</li>
                                <li><span data-icon="tick"></span>نتيجة الامتحانات الدورية</li>
                                <li><span data-icon="tick"></span>الصف والشهر المسجّل فيهم</li>
                            </ul>
                            <a href="/parent" class="btn btn-primary parent__cta">افتح تقرير الابن</a>
                        </div>
                        <div class="phone" aria-hidden="true">
                            <div class="phone__screen">
                                <div class="phone__top">
                                    <strong>نموذج متابعة</strong>
                                    <span>اليوم</span>
                                </div>
                                <p class="phone__who">السنة الدراسية</p>
                                <div class="phone__row">
                                    <span>الحضور</span>
                                    <strong><span data-count="92">0</span>%</strong>
                                    <i></i>
                                </div>
                                <div class="phone__row">
                                    <span>الاختبارات</span>
                                    <strong><span data-count="85">0</span>%</strong>
                                </div>
                                <div class="phone__row">
                                    <span>الواجبات</span>
                                    <strong>8 من 10</strong>
                                </div>
                                <div class="phone__row">
                                    <span>آخر نشاط</span>
                                    <strong>منذ ساعتين</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="cta wrap" id="join">
                    <div class="cta__panel">
                        <h2 class="display">جاهز تخلي الفيزياء أوضح؟</h2>
                        <p>ابدأ من صفك، اختار الشهر، وابدأ تتعلم خطوة بخطوة.</p>
                        <div class="cta__actions stack">
                            <a class="btn btn-primary" href="/signup">ابدأ التعلم الآن</a>
                            <a class="btn-invert" href="/#grades">اختار السنة</a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
        @include("site.partials.footer")
    </div>
@endsection

@push("scripts")
    <script src="/js/motion/reveal.js"></script>
    <script src="/js/motion/cards.js"></script>
    <script src="/js/motion/counters.js"></script>
    <script src="/js/motion/nav.js"></script>
    <script src="/js/home.js?v=10"></script>
@endpush
