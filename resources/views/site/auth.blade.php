@extends("site.layout")

@section("content")
    @php($login = $mode === "login")
    <div class="auth">
        <div class="auth__glow" aria-hidden="true"><span></span><span></span></div>
        <div class="auth__top wrap">
            <a href="/" class="brand">
                <span class="brand__mark brand__mark--accent" data-icon="atom"></span>
                <div>
                    <p class="brand__name display">المغازي</p>
                    <p class="brand__tag">منصة الفيزياء</p>
                </div>
            </a>
            <div class="nav__actions">
                <button type="button" class="theme-toggle" data-theme aria-label="تبديل الوضع"></button>
                <a href="/" class="auth__home"><span data-icon="home"></span>الرئيسية</a>
            </div>
        </div>
        <div class="auth__grid wrap">
            <div class="auth__aside">
                <div class="auth__photo">
                    @if ($login)
                        <img src="/images/login.png?v=1" alt="تسجيل الدخول إلى منصة المغازي">
                    @else
                        <img src="/images/signup.png?v=1" alt="إنشاء حساب على منصة المغازي">
                    @endif
                </div>
            <div class="auth__copy rise" data-role-copy="student">
                @if ($login)
                    <p class="auth__kicker">دخول الطالب</p>
                    <h1 class="display">ادخل حسابك وكمّل مذاكرتك</h1>
                    <p>من حسابك هتلاقي صفك وشهوره، وتكمّل من حيث وقفت.</p>
                    <ul class="auth__points">
                        <li><span data-icon="book"></span>شهور صفك بس</li>
                        <li><span data-icon="play"></span>كمّل التعلم</li>
                        <li><span data-icon="check"></span>الصف المسجّل في حسابك</li>
                    </ul>
                @else
                    <p class="auth__kicker">حساب طالب جديد</p>
                    <h1 class="display">سجّل واختر صفك <span>وابدأ</span></h1>
                    <p>بعد التسجيل، اختر صفك وابدأ استكشاف الشهور المناسبة لك بسهولة.</p>
                    <ul class="auth__points">
                        <li><span data-icon="book"></span>اختيار الصف</li>
                        <li><span data-icon="cap"></span>استعراض الشهور المناسبة</li>
                        <li><span data-icon="play"></span>ابدأ التعلم بعد الدخول</li>
                    </ul>
                @endif
            </div>
                @if ($login)
                    <div class="auth__copy rise" data-role-copy="teacher" hidden>
                        <p class="auth__kicker">دخول المدرس</p>
                        <h1 class="display">ادخل لوحة التشغيل</h1>
                        <p>من هنا الشهور والطلبة والأكواد والامتحانات.</p>
                        <ul class="auth__points">
                            <li><span data-icon="book"></span>الشهور والفيديوهات</li>
                            <li><span data-icon="check"></span>التفعيل والأكواد</li>
                            <li><span data-icon="play"></span>بنك الأسئلة والامتحانات</li>
                        </ul>
                    </div>
                @endif
            </div>
            <div class="auth-card surface rise">
                <div class="auth-card__head">
                    <span class="auth-card__badge" data-icon="{{ $login ? "login" : "userPlus" }}"></span>
                    <div>
                        <h2 class="display">{{ $login ? "تسجيل الدخول" : "إنشاء حساب" }}</h2>
                        <p id="auth-card-copy">{{ $login ? "اختار نوع الحساب، وبعدين ادخل" : "الاسم، رقمك، ورقم ولي الأمر" }}</p>
                    </div>
                </div>
                <div class="auth-done" id="auth-done" hidden>
                    <p class="display">{{ $login ? "تم الدخول" : "تم إنشاء الحساب" }}</p>
                    <p>تقدر ترجع للرئيسية وتبدأ من صفك.</p>
                    <a href="/" class="btn btn-primary">الرجوع للرئيسية</a>
                </div>
                <form id="auth-form" class="auth-form" data-mode="{{ $mode }}">
                    <p class="form-error" id="auth-error" hidden></p>
                    @if ($login)
                        <div class="auth-roles" role="tablist" aria-label="نوع الحساب">
                            <button type="button" data-role="student" class="is-on" role="tab" aria-selected="true">طالب</button>
                            <button type="button" data-role="teacher" role="tab" aria-selected="false">مدرس</button>
                        </div>
                    @endif
                    @unless ($login)
                        <label>
                            <span>الاسم الأول</span>
                            <div class="field">
                                <span data-icon="user"></span>
                                <input required name="first_name" class="auth-input" placeholder="الاسم الأول" autocomplete="given-name">
                            </div>
                        </label>
                        <label>
                            <span>الاسم الثاني</span>
                            <div class="field">
                                <span data-icon="user"></span>
                                <input required name="second_name" class="auth-input" placeholder="الاسم الثاني" autocomplete="additional-name">
                            </div>
                        </label>
                    @endunless
                    <label data-login="student">
                        <span>رقم التليفون</span>
                        <div class="field">
                            <span data-icon="user"></span>
                            <input required name="phone" class="auth-input" placeholder="01xxxxxxxxx" dir="ltr" inputmode="tel" autocomplete="{{ $login ? "username" : "tel" }}">
                        </div>
                    </label>
                    @if ($login)
                        <label data-login="teacher" hidden>
                            <span>البريد</span>
                            <div class="field">
                                <span data-icon="user"></span>
                                <input name="email" type="email" class="auth-input" placeholder="name@email.com" dir="ltr" autocomplete="username">
                            </div>
                        </label>
                    @endif
                    @unless ($login)
                        <label>
                            <span>رقم تليفون ولي الأمر</span>
                            <div class="field">
                                <span data-icon="user"></span>
                                <input required name="parent_phone" class="auth-input" placeholder="01xxxxxxxxx" dir="ltr" inputmode="tel" autocomplete="tel">
                            </div>
                        </label>
                    @endunless
                    <label>
                        <span>كلمة المرور</span>
                        <div class="field">
                            <input required type="password" name="password" minlength="8" class="auth-input auth-input--secret" placeholder="••••••••" dir="ltr">
                            <button type="button" class="field__toggle" id="toggle-pass" aria-label="إظهار كلمة المرور" data-icon="eye"></button>
                        </div>
                    </label>
                    @unless ($login)
                        <label>
                            <span>السنة الدراسية</span>
                            <div class="field">
                                <span data-icon="book"></span>
                                <select required name="grade" class="auth-input auth-input--select">
                                    <option value="" disabled selected>اختر السنة الدراسية</option>
                                </select>
                                <span class="field__chevron" data-icon="up" aria-hidden="true"></span>
                            </div>
                        </label>
                    @endunless
                    <button type="submit" class="btn btn-primary btn-block auth-submit">
                        <span data-label>{{ $login ? "دخول" : "إنشاء الحساب" }}</span>
                        <span class="auth-submit__go" data-icon="back" aria-hidden="true"></span>
                    </button>
                    <p class="auth-switch" id="auth-switch">
                        {{ $login ? "ليس لديك حساب؟" : "عندك حساب؟" }}
                        <a href="{{ $login ? "/signup" : "/login" }}">{{ $login ? "إنشاء حساب" : "تسجيل الدخول" }}</a>
                    </p>
                    @if ($login)
                        <p class="auth-parent"><a href="/parent">تقرير ولي الأمر بكود الابن</a></p>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script src="/js/auth.js?v=3"></script>
@endpush
