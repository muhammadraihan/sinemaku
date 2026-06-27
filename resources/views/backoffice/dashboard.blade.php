@extends('layouts.page')

@section('title', 'Dashboard Sinemaku')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
@endsection

@section('content')
@php
    $lastFilm = optional($last)->nama_film ?: 'Belum ada film';
    $periodeText = ($periode && $periode->tgl_awal && $periode->tgl_akhir)
        ? \Carbon\Carbon::parse($periode->tgl_awal)->format('d M Y').' - '.\Carbon\Carbon::parse($periode->tgl_akhir)->format('d M Y')
        : 'Periode belum tersedia';
    $totalPenonton = optional($total_penonton)->penonton ?? 0;
@endphp

<div class="row mb-4">
    <div class="col-xl-8 mb-4 mb-xl-0">
        <section class="dashboard-hero">
            <div class="hero-chip mb-4">
                <i class="fal fa-chart-line"></i>
                Live audience dashboard
            </div>
            <h1>Sinemaku Analytics</h1>
            <p>Pantau performa film, kota, bioskop, jumlah show, dan area underperforming dalam satu dashboard yang mudah dibaca.</p>
            <button id="download-pdf" class="btn btn-danger">
                <i class="fal fa-file-pdf mr-1"></i> Download PDF
            </button>
            <img id="report-logo" src="{{ asset('img/sinemaku_full_logo.png') }}" alt="logo" style="display:none">
        </section>
    </div>
    <div class="col-xl-4">
        <div class="modern-info-card h-100">
            <div class="modern-card-title">
                <h3>Ringkasan Data</h3>
                <span>Default terbaru</span>
            </div>
            <div class="insight-list">
                <div class="insight-item">
                    <i class="fal fa-film metric-blue"></i>
                    <div>
                        <strong>{{ $lastFilm }}</strong>
                        <span>Film terakhir di pelaporan</span>
                    </div>
                </div>
                <div class="insight-item">
                    <i class="fal fa-calendar-alt metric-green"></i>
                    <div>
                        <strong>{{ $periodeText }}</strong>
                        <span>Periode tayang tersedia</span>
                    </div>
                </div>
                <div class="insight-item">
                    <i class="fal fa-users metric-red"></i>
                    <div>
                        <strong>{{ number_format($totalPenonton) }}</strong>
                        <span>Total penonton film terakhir</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="title-dashboard mb-4">
    <div class="row">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="metric-card">
                <span class="metric-icon metric-blue"><i class="fal fa-users"></i></span>
                <div>
                    <h3>{{ number_format($totalPenonton) }}</h3>
                    <p>Total Penonton</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="metric-card">
                <span class="metric-icon metric-green"><i class="fal fa-map-marker-alt"></i></span>
                <div>
                    <h3 id="metric-cities">-</h3>
                    <p>Kota Dianalisis</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="metric-card">
                <span class="metric-icon metric-yellow"><i class="fal fa-ticket-alt"></i></span>
                <div>
                    <h3 id="metric-shows">-</h3>
                    <p>Show Terbaca</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="metric-card">
                <span class="metric-icon metric-purple"><i class="fal fa-building"></i></span>
                <div>
                    <h3 id="metric-cinemas">-</h3>
                    <p>Bioskop Teratas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modern-filter-card mb-4">
    <form id="filter-form" autocomplete="off">
        <div class="modern-card-title">
            <h3>Filter Laporan</h3>
            <span>Pilih film, periode, dan kategori</span>
        </div>
        <div class="row align-items-end">
            <div class="form-group col-lg-4 mb-3">
                {{ Form::label('nama_film','Nama Film',['class' => 'required form-label'])}}
                {!! Form::select('nama_film', $nama_film, '',
                    ['id'=>'nama_film','class' => 'custom-select'.($errors->has('nama_film') ? 'is-invalid':''), 'required' => '', 'placeholder' => 'Pilih Nama Film ...'])!!}
                @if ($errors->has('nama_film'))
                <div class="invalid-feedback">{{ $errors->first('nama_film') }}</div>
                @endif
            </div>
            <div class="form-group col-sm-6 col-lg-2 mb-3">
                {{ Form::label('tanggal_mulai','Tanggal Mulai',['class' => 'required form-label'])}}
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control">
            </div>
            <div class="form-group col-sm-6 col-lg-2 mb-3">
                {{ Form::label('tanggal_akhir','Tanggal Akhir',['class' => 'required form-label'])}}
                <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="form-control">
            </div>
            <div class="form-group col-lg-3 mb-3">
                {{ Form::label('bioskop_kategori','Kategori Bioskop',['class' => 'required form-label'])}}
                {!! Form::select('bioskop_kategori', $bioskop_kategori, '',
                    ['id'=>'bioskop_kategori','class' => 'custom-select'.($errors->has('bioskop_kategori') ? 'is-invalid':''), 'required' => '', 'placeholder' => 'Pilih Kategori Bioskop ...'])!!}
                @if ($errors->has('bioskop_kategori'))
                <div class="invalid-feedback">{{ $errors->first('bioskop_kategori') }}</div>
                @endif
            </div>
            <div class="form-group col-lg-1 mb-3">
                <button type="button" id="search-btn" class="btn btn-primary w-100" title="Terapkan filter">
                    <i class="fal fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<div id="loading" style="display: none; text-align: center;" class="mb-4">
    <img src="{{asset('img/loading.gif')}}" alt="Loading...">
    <p class="mb-0 mt-2">Memuat data analytics...</p>
</div>

<section class="chart" style="display: none">
    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>TOP 20 Kota</h3>
                    <span>Penonton terbanyak</span>
                </div>
                <div class="chart-container">
                    <canvas id="topCitiesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div id="data-summary" class="modern-table-card h-100">
                <div class="modern-card-title">
                    <h3>Ranking Kota</h3>
                    <span>Top performers</span>
                </div>
                <div class="table-responsive">
                    <table id="summary-table" class="table table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Kota</th>
                                <th>Penonton</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>Grafik Show</h3>
                    <span>Distribusi show</span>
                </div>
                <div class="chart-fixed">
                    <canvas id="showsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>Penonton per Bioskop</h3>
                    <span>Per kategori bioskop</span>
                </div>
                <div class="chart-fixed">
                    <canvas id="viewersByCinemaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>TOP 20 Bioskop</h3>
                    <span>Penonton tertinggi</span>
                </div>
                <div class="chart-fixed">
                    <canvas id="topCinemasChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>Underperforming Kota</h3>
                    <span>Butuh perhatian</span>
                </div>
                <div class="chart-fixed">
                    <canvas id="underperfCitiesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="modern-chart-card">
                <div class="modern-card-title">
                    <h3>Underperforming Bioskop</h3>
                    <span>20 bioskop terbawah</span>
                </div>
                <div class="chart-fixed">
                    <canvas id="underperfCinemasChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    if (window.Chart && window.ChartDataLabels) {
        Chart.register(ChartDataLabels);
    }

    const chartPalette = [
        'rgba(45, 140, 255, 0.82)',
        'rgba(40, 199, 162, 0.82)',
        'rgba(255, 200, 87, 0.88)',
        'rgba(255, 107, 125, 0.82)',
        'rgba(141, 124, 255, 0.82)',
        'rgba(45, 196, 255, 0.82)',
        'rgba(255, 159, 67, 0.82)',
        'rgba(74, 222, 128, 0.82)'
    ];

    let chartInstances = {
        topCities: null,
        shows: null,
        viewersByCinema: null,
        topCinemas: null,
        underCities: null,
        underCinemas: null
    };

    $(document).ready(function(){
        $('#nama_film').select2({ width: '100%' });
        $('#bioskop_kategori').select2({ width: '100%' });
        $('#download-pdf').hide();
        $('#data-summary').hide();

        loadChart('', '', '', 'ALL');

        $(document).delegate("#search-btn", "click", function (event) {
            event.preventDefault();

            var nama_film = $('#nama_film').val();
            var tgl_mulai = $('#tanggal_mulai').val();
            var tgl_akhir = $('#tanggal_akhir').val();
            var bioskop_kategori = $('#bioskop_kategori').val();

            if (!nama_film || !tgl_mulai || !tgl_akhir || !bioskop_kategori) {
                alert("Harap isi semua filter!");
                return;
            }

            $('.chart').hide();
            $('#data-summary').hide();
            $('#download-pdf').hide();
            $('#loading').show();

            setTimeout(function () {
                loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori);
            }, 200);
        });
    });

    document.getElementById("download-pdf").addEventListener("click", function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a4");

        const marginX = 10;
        const marginTop = 10;
        const gutter = 5;
        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();
        const usableW = pageW - marginX * 2;
        const colW = (usableW - gutter) / 2;
        const chartStartY = 34;
        const chartGapY = 4;
        const topChartH = 50;
        const remainingH = pageH - chartStartY - topChartH - (chartGapY * 3) - marginTop;
        const compactChartH = remainingH / 3;

        const namaFilm = $('#nama_film option:selected').text() || '{{ $lastFilm }}';
        const tanggalMulai = $('#tanggal_mulai').val() || '-';
        const tanggalAkhir = $('#tanggal_akhir').val() || '-';
        const kategoriBioskop = $('#bioskop_kategori option:selected').text() || 'Semua';

        function fitImageSize(imgProps, maxW, maxH) {
            const r = Math.min(maxW / imgProps.width, maxH / imgProps.height);
            return { w: imgProps.width * r, h: imgProps.height * r };
        }

        function addChartBox(title, dataURL, x, y, boxW, boxH) {
            if (!dataURL) return;
            doc.setFont("helvetica", "bold");
            doc.setFontSize(9);
            doc.setTextColor(33,37,41);
            doc.text(title, x, y + 4);

            const titleH = 6;
            const imgProps = doc.getImageProperties(dataURL);
            const size = fitImageSize(imgProps, boxW, boxH - titleH);
            const imgX = x + (boxW - size.w) / 2;
            const imgY = y + titleH + ((boxH - titleH - size.h) / 2);
            doc.addImage(dataURL, 'PNG', imgX, imgY, size.w, size.h, undefined, 'FAST');
        }

        doc.setFont("helvetica", "bold");
        doc.setFontSize(15);
        doc.setTextColor(33,37,41);
        doc.text("Laporan Grafik Penonton", marginX, marginTop + 4);
        doc.setFont("helvetica","normal");
        doc.setFontSize(9.5);
        doc.setTextColor(80,80,80);
        doc.text(`Nama Film : ${namaFilm}`, marginX, marginTop + 12);
        doc.text(`Periode : ${tanggalMulai} s.d. ${tanggalAkhir}`, marginX, marginTop + 18);
        doc.text(`Kategori Bioskop : ${kategoriBioskop}`, marginX + (usableW / 2), marginTop + 12);

        const logoEl = document.getElementById('report-logo');
        if (logoEl && logoEl.complete) {
            const logoW = 28;
            const logoH = (logoEl.naturalHeight / logoEl.naturalWidth) * logoW;
            doc.addImage(logoEl, 'PNG', pageW - marginX - logoW, marginTop - 4, logoW, logoH, undefined, 'FAST');
        }

        const imgTopCities  = chartInstances.topCities ? chartInstances.topCities.toBase64Image() : null;
        const imgShows      = chartInstances.shows ? chartInstances.shows.toBase64Image() : null;
        const imgViewCinema = chartInstances.viewersByCinema ? chartInstances.viewersByCinema.toBase64Image() : null;
        const imgTopCinemas = chartInstances.topCinemas ? chartInstances.topCinemas.toBase64Image() : null;
        const imgUnderCity  = chartInstances.underCities ? chartInstances.underCities.toBase64Image() : null;
        const imgUnderCin   = chartInstances.underCinemas ? chartInstances.underCinemas.toBase64Image() : null;

        addChartBox("TOP 20 Kota", imgTopCities, marginX, chartStartY, usableW, topChartH);
        const row1Y = chartStartY + topChartH + chartGapY;
        addChartBox("Grafik Show", imgShows, marginX, row1Y, colW, compactChartH);
        addChartBox("Penonton per Bioskop", imgViewCinema, marginX + colW + gutter, row1Y, colW, compactChartH);
        const row2Y = row1Y + compactChartH + chartGapY;
        addChartBox("TOP 20 Bioskop", imgTopCinemas, marginX, row2Y, colW, compactChartH);
        addChartBox("Underperforming Kota", imgUnderCity, marginX + colW + gutter, row2Y, colW, compactChartH);
        const row3Y = row2Y + compactChartH + chartGapY;
        addChartBox("Underperforming Bioskop", imgUnderCin, marginX, row3Y, usableW, compactChartH);

        doc.save("report-charts-dashboard.pdf");
    });

    function destroyIfExists(inst) {
        if (inst && typeof inst.destroy === 'function') inst.destroy();
    }

    function commonOptions(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(126, 149, 178, 0.14)' },
                    ticks: { color: '#7b8aa4' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(126, 149, 178, 0.14)' },
                    ticks: { color: '#7b8aa4', autoSkip: false }
                }
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#182033',
                    font: { weight: 'bold', size: 10 }
                }
            }
        }, extra || {});
    }

    function dataset(label, data, colorOffset) {
        return {
            label: label,
            data: data,
            backgroundColor: data.map((_, index) => chartPalette[(index + (colorOffset || 0)) % chartPalette.length]),
            borderColor: data.map((_, index) => chartPalette[(index + (colorOffset || 0)) % chartPalette.length].replace('0.82', '1').replace('0.88', '1')),
            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 32
        };
    }

    function loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori) {
        $.ajax({
            url: "{{ route('getCharAudienceDashboard') }}",
            method: "GET",
            data: {
                nama_film: nama_film,
                tgl_mulai: tgl_mulai,
                tgl_akhir: tgl_akhir,
                bioskop_kategori: bioskop_kategori
            },
            success: function (payload) {
                if (Array.isArray(payload)) {
                    payload = { top_cities: payload };
                }

                $('#loading').hide();
                $('.chart').show();
                $('#data-summary').show();
                $('#download-pdf').show();

                const topCities = payload.top_cities || [];
                const labelsTopCities = topCities.map(x => x.kota);
                const valuesTopCities = topCities.map(x => Number(x.jumlah || 0));

                $('#summary-table tbody').empty();
                labelsTopCities.forEach(function(label, index) {
                    $('#summary-table tbody').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${label}</td>
                            <td>${Number(valuesTopCities[index]).toLocaleString()}</td>
                        </tr>
                    `);
                });

                $('#metric-cities').text(labelsTopCities.length);
                $('#metric-shows').text((payload.shows_over_time || []).length);
                $('#metric-cinemas').text((payload.top_cinemas || []).length);

                destroyIfExists(chartInstances.topCities);
                chartInstances.topCities = new Chart(
                    document.getElementById('topCitiesChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: labelsTopCities,
                            datasets: [dataset('Jumlah Penonton', valuesTopCities, 0)]
                        },
                        options: commonOptions({ maintainAspectRatio: false })
                    }
                );

                const shows = payload.shows_over_time || [];
                const showsLabels = shows.map(x => x.show);
                const showsValues = shows.map(x => Number(x.jumlah || 0));

                destroyIfExists(chartInstances.shows);
                chartInstances.shows = new Chart(
                    document.getElementById('showsChart').getContext('2d'),
                    {
                        type: 'line',
                        data: {
                            labels: showsLabels,
                            datasets: [{
                                label: 'Jumlah Show',
                                data: showsValues,
                                fill: true,
                                tension: 0.35,
                                borderColor: 'rgba(45, 140, 255, 1)',
                                backgroundColor: 'rgba(45, 140, 255, 0.12)',
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: 'rgba(45, 140, 255, 1)',
                                pointRadius: 4,
                                borderWidth: 3
                            }]
                        },
                        options: commonOptions()
                    }
                );

                const vb = payload.viewers_by_cinema || [];
                const vbLabels = vb.map(x => x.bioskop);
                const vbValues = vb.map(x => Number(x.penonton || 0));

                destroyIfExists(chartInstances.viewersByCinema);
                chartInstances.viewersByCinema = new Chart(
                    document.getElementById('viewersByCinemaChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: vbLabels,
                            datasets: [dataset('Penonton', vbValues, 2)]
                        },
                        options: commonOptions()
                    }
                );

                const topC = (payload.top_cinemas || []).slice(0, 20);
                const topCLabels = topC.map(x => x.bioskop);
                const topCValues = topC.map(x => Number(x.penonton || 0));

                destroyIfExists(chartInstances.topCinemas);
                chartInstances.topCinemas = new Chart(
                    document.getElementById('topCinemasChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: topCLabels,
                            datasets: [dataset('Penonton', topCValues, 3)]
                        },
                        options: commonOptions({ indexAxis: 'y' })
                    }
                );

                const uc = payload.underperf_cities || [];
                const ucLabels = uc.map(x => x.kota);
                const ucValues = uc.map(x => Number(x.penonton || 0));

                destroyIfExists(chartInstances.underCities);
                chartInstances.underCities = new Chart(
                    document.getElementById('underperfCitiesChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: ucLabels,
                            datasets: [dataset('Penonton', ucValues, 4)]
                        },
                        options: commonOptions()
                    }
                );

                const ub = payload.underperf_cinemas || [];
                const ubLabels = ub.map(x => x.nama_bioskop);
                const ubValues = ub.map(x => Number(x.penonton || 0));

                destroyIfExists(chartInstances.underCinemas);
                chartInstances.underCinemas = new Chart(
                    document.getElementById('underperfCinemasChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: ubLabels,
                            datasets: [dataset('Penonton', ubValues, 5)]
                        },
                        options: commonOptions({ indexAxis: 'y' })
                    }
                );
            },
            error: function (xhr, status, error) {
                $('#loading').hide();
                console.error("Error:", error);
                alert("Terjadi kesalahan saat mengambil data analytics.");
            }
        });
    }
</script>
@endsection
