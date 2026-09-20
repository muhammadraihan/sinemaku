<aside class="page-sidebar sinemaku-sidebar">
    <a href="#" class="sinemaku-sidebar-close d-none" data-action="toggle" data-class="mobile-nav-on" aria-label="Tutup menu">
        <i class="fal fa-times"></i>
    </a>
    <div class="page-logo">
        <a href="{{route('backoffice.dashboard')}}" class="sidebar-brand">
            <span class="sidebar-brand-mark">
                <img src="{{asset('img/sinemaku.png')}}" alt="{{env('APP_NAME','')}}">
            </span>
            <span class="sidebar-brand-text">
                <strong>{{env('APP_NAME','Sinemaku')}}</strong>
                <span>Backoffice analytics</span>
            </span>
        </a>
    </div>

    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        @php
            $menu = Helper::menu()->getData();
        @endphp
        @include('partials.menu', ['menu' => $menu])

        <ul class="nav-menu mt-2">
            <li class="{{ request()->routeIs('seatmap-monitor.*') ? 'active open' : '' }}">
                <a href="{{ route('seatmap-monitor.index') }}" title="Audience Estimate Ranking">
                    <i class="fal fa-signal-stream"></i>
                    <span class="nav-link-text">Audience Estimate</span>
                </a>
                <ul>
                    <li class="{{ request()->routeIs('seatmap-monitor.index') ? 'active' : '' }}">
                        <a href="{{ route('seatmap-monitor.index') }}"><span class="nav-link-text">Cinepoint Daily</span></a>
                    </li>
                </ul>
            </li>
            <li class="{{ request()->routeIs('backoffice.city-performance.*') ? 'active' : '' }}">
                <a href="{{ route('backoffice.city-performance.index') }}" title="City Performance">
                    <i class="fal fa-city"></i>
                    <span class="nav-link-text">City Performance</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-profile-card">
            @if (!is_null(Auth::user()->avatar))
                <img src="{{asset('img/avatar').'/'.'user'.'/'.Auth::user()->avatar}}" alt="{{Auth::user()->name}}">
            @else
                <img src="{{asset('img/foto_sinemaku.jpg')}}" alt="{{Auth::user()->name}}">
            @endif
            <strong>{{Auth::user()->name}}</strong>
            <span>{{Auth::user()->email}}</span>
            <div class="profile-line"></div>
        </div>
    </nav>
</aside>
