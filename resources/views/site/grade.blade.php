@extends("site.layout")

@section("content")
    @include("site.partials.nav")
    <main>
        <div class="grade-page" data-slug="{{ $slug }}">
            <div class="wrap" id="grade-root">
                <p class="grade-note">جاري تحميل الشهور...</p>
            </div>
        </div>
    </main>
    @include("site.partials.footer")
@endsection

@push("scripts")
    <script src="/js/grade.js?v=4"></script>
@endpush
