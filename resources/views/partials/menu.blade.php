<ul id="js-nav-menu" class="nav-menu">
    <li>
        <a href="{{route('backoffice.dashboard')}}" title="Dashboard" data-filter-tags="dashboard">
            <i class="fal fa-desktop"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
    </li>
    <li>
        <a href="#" title="Media" data-filter-tags="Media">
            <i class="fal fa-book"></i>
            <span class="nav-link-text">Master</span>
        </a>
        <ul>
            <li>
                <a href="{{route('kategoribioskop.index')}}" title="Category Cinema" data-filter-tags="Category Cinema">
                    {{-- <i class="fal fa-project-diagram"></i> --}}
                    <span class="nav-link-text">Master Category Cinema</span>
                </a>
            </li>
            <li>
                <a href="{{route('masterbioskop.index')}}" title="Cinema Name" data-filter-tags="Cinema Name">
                    {{-- <i class="fal fa-landmark"></i> --}}
                    <span class="nav-link-text">Master Cinema Name</span>
                </a>
            </li>
            <li>
                <a href="{{route('typetiket.index')}}" title="Type Ticket" data-filter-tags="Type Ticket">
                    {{-- <i class="fal fa-ticket-alt"></i> --}}
                    <span class="nav-link-text">Master Type Ticket</span>
                </a>
            </li>
            <li>
                <a href="{{route('kapasitas.index')}}" title="Kapasitas" data-filter-tags="Kapasitas">
                    {{-- <i class="fal fa-ticket-alt"></i> --}}
                    <span class="nav-link-text">Master Kapasitas</span>
                </a>
            </li>
        </ul>
    </li>

    <li>
        <a href="{{route('pelaporan.index')}}" title="Reporting" data-filter-tags="Reporting">
            <i class="fal fa-clipboard-list"></i>
            <span class="nav-link-text">Reporting</span>
        </a>
    </li>
    <li>
        <a href="{{route('laporan.index')}}" title="Resume Report" data-filter-tags="Resume Report">
            <i class="fal fa-clipboard-list"></i>
            <span class="nav-link-text">Resume Report</span>
        </a>
    </li>
    @hasrole('superadmin')
    @isset($menu)
    @foreach ($menu as $parent_menu)
    <li class="">
        <a href="{{$parent_menu->route_name ? route($parent_menu->route_name): '#'}}"
            title="{{$parent_menu->menu_title ? $parent_menu->menu_title:''}}">
            <i class="{{$parent_menu->icon_class ? $parent_menu->icon_class:''}}"></i>
            <span class="nav-link-text">{{$parent_menu->menu_title ?$parent_menu->menu_title:''}}</span>
        </a>
        @if (count($parent_menu->childs))
        <ul>
            @include('partials.submenu',['submenu' => $parent_menu->childs])
        </ul>
        @endif
    </li>
    @endforeach
    @endisset
    @endhasrole
</ul>