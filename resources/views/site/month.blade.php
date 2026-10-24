@extends("site.layout")

@section("content")
    @include("site.partials.nav")
    <main class="month-page" data-month="{{ $month }}">
        <div id="month-root">
            <p class="grade-note month-loading">جاري تحميل الشهر...</p>
        </div>
    </main>
    @include("site.partials.footer")
@endsection

@push("scripts")
    <script src="/js/catalog.js?v=8"></script>
@endpush
