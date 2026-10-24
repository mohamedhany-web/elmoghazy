@extends("admin.layout")

@section("content")
    <section class="cs-login">
        <form id="teacher-login" class="cs-panel">
            <div class="cs-panel__head">
                <div>
                    <h2>دخول المدرس</h2>
                    <p>لوحة تشغيل التفعيل والأكواد</p>
                </div>
            </div>
            <div class="cs-panel__body">
                <p id="teacher-error" class="cs-note is-bad" hidden></p>
                <label class="cs-field"><span class="cs-label">البريد</span><input class="cs-input" type="email" name="email" required autocomplete="username"></label>
                <label class="cs-field"><span class="cs-label">كلمة المرور</span><input class="cs-input" type="password" name="password" required autocomplete="current-password"></label>
                <button type="submit" class="cs-btn cs-btn--primary">دخول</button>
            </div>
        </form>
    </section>
@endsection

@push("scripts")
    <script src="/js/admin/login.js?v=1"></script>
@endpush
