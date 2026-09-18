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
