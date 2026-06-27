<header class="page-header" role="banner">
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative"
            data-toggle="modal" data-target="#modal-shortcut">
            <img src="{{asset('img/sinemaku.png')}}" alt="{{env('APP_NAME','')}}" aria-roledescription="logo" style="width: 50px; height:50px">
            <span class="page-logo-text mr-1">{{env('APP_NAME','')}}</span>
            <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
        </a>
    </div>
    <div class="search">
        <form class="app-forms hidden-xs-down" role="search" action="page_search.html" autocomplete="off">
            <input type="text" id="search-field" placeholder="Cari laporan, film, bioskop..." class="form-control" tabindex="1">
            <a href="#" onclick="return false;" class="btn-danger btn-search-close js-waves-off d-none"
                data-action="toggle" data-class="mobile-search-on">
                <i class="fal fa-times"></i>
            </a>
        </form>
    </div>
    <div class="ml-auto d-flex">
        <div class="hidden-sm-up">
            <a href="#" class="header-icon" data-action="toggle" data-class="mobile-search-on" data-focus="search-field"
                title="Search">
                <i class="fal fa-search"></i>
            </a>
        </div>
        <div>
            <a href="#" data-toggle="dropdown" title="{{Auth::user()->email}}"
                class="header-icon d-flex align-items-center justify-content-center ml-2">
                @if (!is_null(Auth::user()->avatar))
                <img src="{{asset('img/avatar').'/'.'user'.'/'.Auth::user()->avatar}}"
                    class="rounded-circle profile-image" alt="User Avatar">
                @else
                <img src="{{asset('img/avatar/avatar_icon.png')}}" class="rounded-circle profile-image"
                    alt="User Avatar">
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                    <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                        <span class="mr-2">
                            @if (!is_null(Auth::user()->avatar))
                            <img src="{{asset('img/avatar').'/'.'user'.'/'.Auth::user()->avatar}}"
                                class="rounded-circle profile-image" alt="User Avatar">
                            @else
                            <img src="{{asset('img/avatar/avatar_icon.png')}}" class="rounded-circle profile-image"
                                alt="User Avatar">
                            @endif
                        </span>
                        <div class="info-card-text">
                            <div class="fs-lg text-truncate text-truncate-lg">{{Auth::user()->name}}</div>
                            <span class="text-truncate text-truncate-md opacity-80">{{Auth::user()->email}}</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider m-0"></div>
                <a href="{{route('profile')}}" class="dropdown-item">
                    <span>Profile</span>
                </a>
                <a href="#" class="dropdown-item" data-action="app-reset">
                    <span data-i18n="drpdwn.reset_layout">Reset Layout</span>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a href="#" class="dropdown-item" data-action="app-fullscreen">
                    <span data-i18n="drpdwn.fullscreen">Fullscreen</span>
                    <i class="float-right text-muted fw-n">F11</i>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a class="dropdown-item fw-500 pt-3 pb-3" href="{{ route('logout') }}" onclick="event.preventDefault();
                           document.getElementById('logout-form').submit();">
                    Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    {{ csrf_field() }}
                </form>
            </div>
        </div>
    </div>
</header>
