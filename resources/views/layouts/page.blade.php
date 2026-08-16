@extends('layouts.master')

@section('body_class', 'sinemaku-modern-shell')

@section('themes_css')
@stack('css')
<!-- Custom CSS for this page only -->
@yield('css')
@endsection

@section('body')
<!-- BEGIN Page Wrapper -->
<div class="page-wrapper">
    <div class="page-inner">
        <!-- BEGIN Left Aside -->
        @include('partials.sidebar')
        <!-- END Left Aside -->
        <div class="page-content-wrapper">
            <!-- BEGIN Page Header -->
            @include('partials.header')
            <!-- END Page Header -->
            <!-- BEGIN Page Content -->
            <!-- the #js-page-content id is needed for some plugins to initialize -->
            <main id="js-page-content" role="main" class="page-content">
                <ol class="breadcrumb page-breadcrumb">
                    <li class="breadcrumb-item"><a href="{{route('backoffice.dashboard')}}"> Backoffice</a></li>
                    @for ($i = 2; $i <= count(Request::segments()); $i++) 
                    <li class="breadcrumb-item">
                        {{ucwords(strtolower(Request::segment($i)))}}
                    </li>
                    @endfor
                    <li class="position-absolute pos-top pos-right d-none d-sm-block"><span class="js-get-date"></span>
                    </li>
                </ol>
                @yield('content')
            </main>
            <!-- this overlay is activated only when mobile menu is triggered -->
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
            <!-- END Page Content -->
            <!-- BEGIN Page Footer -->
            <footer class="page-footer" role="contentinfo">
                <div class="d-flex align-items-center flex-1 text-muted">
                    @php
                        $json = file_get_contents(base_path('package.json'));
                        $decode = json_decode($json,true);
                        $version = $decode['version'];
                    @endphp
                    <span class="hidden-md-down fw-700">{{date('Y')}} &copy; <a href='#' class='text-primary fw-500'
                            title='' target='_blank'>{{env('APP_DEVELOPER','')}}</a> - v {{$version}}</span>
                </div>
                <div>
                    <ul class="list-table m-0">
                        <li><a href="#" class="text-secondary fw-700">About</a></li>
                        <li class="pl-3"><a href="#" class="text-secondary fw-700">License</a>
                        </li>
                        <li class="pl-3"><a href="#" class="text-secondary fw-700">Documentation</a>
                        </li>
                    </ul>
                </div>
            </footer>
            <!-- END Page Footer -->
        </div>
    </div>
</div>
<!-- END Page Wrapper -->
<!-- BEGIN Quick Menu -->
<!-- to add more items, please make sure to change the variable '$menu-items: number;' in your _page-components-shortcut.scss -->
@include('partials.quickmenu')
<!-- END Quick Menu -->
<!-- BEGIN Messenger -->
<!-- END Messenger -->
<!-- BEGIN Page Settings -->
@include('partials.settings')
<!-- END Page Settings -->
@endsection

@section('themes_js')
@stack('js')
<!-- Custom JS for this page only -->
@yield('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var filmInput = document.getElementById('nama_film');
        var startDateInput = document.getElementById('tanggal_mulai') || document.getElementById('tgl_mulai');
        var endDateInput = document.getElementById('tanggal_akhir') || document.getElementById('tgl_akhir');

        if (!filmInput || !startDateInput || !endDateInput || filmInput.tagName !== 'SELECT') {
            return;
        }

        var filmFieldGroup = filmInput.closest('.form-group');
        if (!filmFieldGroup) {
            return;
        }

        var filmFilterRow = filmFieldGroup ? filmFieldGroup.closest('.row') : null;
        var filterColumns = filmFilterRow
            ? Array.prototype.filter.call(filmFilterRow.children, function (child) {
                return child.classList.contains('form-group');
            })
            : [];
        var isInlineFilter = filterColumns.length > 1;

        var filmDateInfo = document.createElement('small');
        filmDateInfo.id = 'film-screening-date-info';
        filmDateInfo.className = 'film-screening-date-badge d-none';
        filmDateInfo.setAttribute('aria-live', 'polite');
        filmFieldGroup.appendChild(filmDateInfo);

        if (isInlineFilter) {
            filmFieldGroup.classList.add('film-date-field-group');
        }

        var showFilmDateInfo = function (message, isWarning) {
            filmDateInfo.classList.remove('d-none', 'is-warning');
            if (isWarning) {
                filmDateInfo.classList.add('is-warning');
            }
            if (isInlineFilter) {
                filmFilterRow.classList.add('has-film-date-badge');
            }
            filmDateInfo.innerHTML = '<i class="fal fa-calendar-check mr-1"></i>' + message;
        };

        var hideFilmDateInfo = function () {
            filmDateInfo.classList.add('d-none');
            filmDateInfo.textContent = '';
            if (isInlineFilter) {
                filmFilterRow.classList.remove('has-film-date-badge');
            }
        };

        var formatFilmDate = function (dateValue) {
            var parts = dateValue.split('-');
            var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));

            return new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }).format(date);
        };

        var requestSequence = 0;
        var updateFilmStartDate = function () {
            var filmName = filmInput.value;
            var hasSelectedFilm = filmName && filmName !== 'ALL';
            var currentRequest = ++requestSequence;

            if (!hasSelectedFilm) {
                startDateInput.value = '';
                endDateInput.value = '';
                hideFilmDateInfo();
                startDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                endDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }

            startDateInput.value = '';
            showFilmDateInfo('Memuat tanggal tayang...', false);

            var url = new URL(@json(route('ref.film-start-date')), window.location.origin);
            url.searchParams.set('nama_film', filmName);

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Gagal mengambil tanggal mulai film.');
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (currentRequest !== requestSequence) {
                        return;
                    }

                    if (hasSelectedFilm && data.tgl_tayang) {
                        showFilmDateInfo(
                            'Tanggal Tayang: <strong>' + formatFilmDate(data.tgl_tayang) + '</strong>',
                            false
                        );
                    } else if (hasSelectedFilm) {
                        showFilmDateInfo('Tanggal tayang belum tersedia di Master Film.', true);
                    }

                    if (data.start_date) {
                        startDateInput.value = data.start_date;
                        startDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    endDateInput.value = data.end_date || '';
                    endDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                })
                .catch(function (error) {
                    if (currentRequest === requestSequence && hasSelectedFilm) {
                        showFilmDateInfo('Tanggal tayang gagal dimuat. Silakan pilih ulang film.', true);
                    }
                    console.error(error);
                });
        };

        // Select2 emits its selection changes through jQuery. Binding through
        // jQuery ensures both a regular <select> change and Select2 selection
        // call the same updater.
        if (window.jQuery) {
            window.jQuery(filmInput)
                .off('change.filmStartDate')
                .on('change.filmStartDate', updateFilmStartDate);
        } else {
            filmInput.addEventListener('change', updateFilmStartDate);
        }

    });
</script>
@endsection
