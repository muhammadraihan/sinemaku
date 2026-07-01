@php
    $masterActive = request()->routeIs(
        'vendor.*',
        'kategoribioskop.*',
        'masterbioskop.*',
        'typetiket.*',
        'kapasitas.*'
    );
@endphp

<ul id="js-nav-menu" class="nav-menu">
    <li class="{{request()->routeIs('backoffice.dashboard') ? 'active' : ''}}">
        <a href="{{route('backoffice.dashboard')}}" title="Dashboard" data-filter-tags="dashboard">
            <i class="fal fa-home"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
    </li>

    <li class="{{$masterActive ? 'active open' : ''}}">
        <a href="#" title="Master Data" data-filter-tags="master data">
            <i class="fal fa-book"></i>
            <span class="nav-link-text">Master</span>
        </a>
        <ul>
            <li class="{{request()->routeIs('vendor.*') ? 'active' : ''}}">
                <a href="{{route('vendor.index')}}" title="Vendor" data-filter-tags="vendor">
                    <i class="fal fa-building"></i>
                    <span class="nav-link-text">Master Vendor</span>
                </a>
            </li>
            <li class="{{request()->routeIs('kategoribioskop.*') ? 'active' : ''}}">
                <a href="{{route('kategoribioskop.index')}}" title="Kategori Bioskop" data-filter-tags="kategori bioskop">
                    <i class="fal fa-tags"></i>
                    <span class="nav-link-text">Kategori Bioskop</span>
                </a>
            </li>
            <li class="{{request()->routeIs('masterbioskop.*') ? 'active' : ''}}">
                <a href="{{route('masterbioskop.index')}}" title="Nama Bioskop" data-filter-tags="nama bioskop">
                    <i class="fal fa-film"></i>
                    <span class="nav-link-text">Nama Bioskop</span>
                </a>
            </li>
            <li class="{{request()->routeIs('typetiket.*') ? 'active' : ''}}">
                <a href="{{route('typetiket.index')}}" title="Tipe Tiket" data-filter-tags="tipe tiket">
                    <i class="fal fa-ticket-alt"></i>
                    <span class="nav-link-text">Tipe Tiket</span>
                </a>
            </li>
            <li class="{{request()->routeIs('kapasitas.*') ? 'active' : ''}}">
                <a href="{{route('kapasitas.index')}}" title="Kapasitas" data-filter-tags="kapasitas">
                    <i class="fal fa-database"></i>
                    <span class="nav-link-text">Kapasitas</span>
                </a>
            </li>
        </ul>
    </li>

    <li class="{{request()->routeIs('pelaporan.*') ? 'active' : ''}}">
        <a href="{{route('pelaporan.index')}}" title="Laporan" data-filter-tags="laporan">
            <i class="fal fa-clipboard-list"></i>
            <span class="nav-link-text">Laporan</span>
        </a>
    </li>

    <li class="{{request()->routeIs('laporan.*') ? 'active' : ''}}">
        <a href="{{route('laporan.index')}}" title="Rekap Laporan" data-filter-tags="rekap laporan">
            <i class="fal fa-chart-line"></i>
            <span class="nav-link-text">Rekap Omset</span>
        </a>
    </li>
    @hasrole('superadmin|superuser')
    <li class="{{request()->routeIs('grafik_kota.*') ? 'active' : ''}}">
        <a href="{{route('grafik_kota.index')}}" title="Grafik TOP 20 per Kota" data-filter-tags="grafik top kota">
            <i class="fal fa-chart-bar"></i>
            <span class="nav-link-text">Grafik Kota</span>
        </a>
    </li>
    @endhasrole

    @hasrole('superadmin')
    @isset($menu)
    @foreach ($menu as $parent_menu)
    <li>
        <a href="{{$parent_menu->route_name ? route($parent_menu->route_name): '#'}}"
            title="{{$parent_menu->menu_title ? $parent_menu->menu_title:''}}">
            <i class="{{$parent_menu->icon_class ? $parent_menu->icon_class:'fal fa-circle'}}"></i>
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
