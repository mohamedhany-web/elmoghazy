<header class="st-top">
    <div class="st-top__row">
        <div class="st-top__heading">
            <h1 class="st-top__title">{{ $pageTitle }}</h1>
            @if (!empty($crumbs))
                <p class="st-crumb">
                    @foreach ($crumbs as $crumb)
                        @if (!$loop->first)<span>/</span>@endif
                        @if (!empty($crumb["url"]))
                            <a href="{{ $crumb["url"] }}">{{ $crumb["label"] }}</a>
                        @else
                            <strong>{{ $crumb["label"] }}</strong>
                        @endif
                    @endforeach
                </p>
            @endif
        </div>
        <button type="button" class="st-top__menu" id="stTopMenu" aria-label="القائمة">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>
</header>
