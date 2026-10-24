@extends("site.layout")

@section("content")
    @include("site.partials.nav")
    <main>
        <div class="catalog wrap" id="catalog-root">
            <header class="catalog__head">
                <div>
                    <p class="hero__kicker">كتالوج الشهور</p>
                    <h1 class="display">شهور الفيزياء</h1>
                    <p class="section-copy">صفّ حسب السنة الدراسية. السنوات اللي هنا هي اللي المدرس أضافها من اللوحة، والترتيب من طلبات التسجيل.</p>
                </div>
                <aside class="catalog__help surface">
                    <h2>مش عارف تختار الشهر؟</h2>
                    <p>ابدأ من صفك، أو افتح الشهر وابعت طلب شراء. المدرس هيراجع الطلب من اللوحة.</p>
                    <a class="btn btn-primary btn-sm" href="/signup">ابدأ من صفك</a>
                </aside>
            </header>
            <form class="catalog__search" id="catalog-search">
                <input type="search" name="q" placeholder="ابحث باسم الشهر أو المرحلة..." autocomplete="off">
                <button class="btn btn-primary btn-sm" type="submit">بحث</button>
            </form>
            <div class="catalog__chips" id="catalog-chips"></div>
            <div class="catalog__layout">
                <aside class="catalog__filters surface">
                    <h2>السنة الدراسية</h2>
                    <div id="catalog-stages"></div>
                </aside>
                <div>
                    <div class="catalog__bar">
                        <h2>الشهور المتاحة <span id="catalog-count"></span></h2>
                        <label class="catalog__sort">ترتيب حسب
                            <select id="catalog-sort">
                                <option value="sales">الأكثر تسجيلاً</option>
                                <option value="new">الأحدث</option>
                            </select>
                        </label>
                    </div>
                    <div id="catalog-grid" class="catalog__grid"></div>
                </div>
            </div>
        </div>
    </main>
    @include("site.partials.footer")
@endsection

@push("scripts")
    <script src="/js/catalog.js?v=6"></script>
@endpush
