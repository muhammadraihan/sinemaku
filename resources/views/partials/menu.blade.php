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
                <a href="{{route('vendor.index')}}" title="Vendor" data-filter-tags="Vendor">
                    {{-- <i class="fal fa-project-diagram"></i> --}}
                    <span class="nav-link-text">Master Vendor</span>
                </a>
            </li>
            <li>
                <a href="{{route('kategoribioskop.index')}}" title="Kategori Bioskop" data-filter-tags="Kategori Bioskop">
                    {{-- <i class="fal fa-project-diagram"></i> --}}
                    <span class="nav-link-text">Master Kategori Bioskop</span>
                </a>
            </li>
            <li>
                <a href="{{route('masterbioskop.index')}}" title="Nama Bioskop" data-filter-tags="Nama Bioskop">
                    {{-- <i class="fal fa-landmark"></i> --}}
                    <span class="nav-link-text">Master Nama Bioskop</span>
                </a>
            </li>
            <li>
                <a href="{{route('typetiket.index')}}" title="Tipe Tiket" data-filter-tags="Tipe Tiket">
                    {{-- <i class="fal fa-ticket-alt"></i> --}}
                    <span class="nav-link-text">Master Tipe Tiket</span>
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
        <a href="{{route('pelaporan.index')}}" title="Laporan" data-filter-tags="Laporan">
            <i class="fal fa-clipboard-list"></i>
            <span class="nav-link-text">Laporan</span>
        </a>
    </li>
    @hasrole('superadmin|superuser')
    <li>
        <a href="{{route('laporan.index')}}" title="Rekap Laporan" data-filter-tags="Rekap Laporan">
            <i class="fal fa-book"></i>
            <span class="nav-link-text">Rekap Laporan</span>
        </a>
    </li>
    <li>
        <a href="{{route('grafik_kota.index')}}" title="Grafik TOP 20 per Kota" data-filter-tags="Grafik TOP 20 per Kota">
            <i class="fal fa-chart-bar"></i>
            <span class="nav-link-text">Grafik TOP 10 per Kota</span>
        </a>
    </li>
    @endhasrole
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