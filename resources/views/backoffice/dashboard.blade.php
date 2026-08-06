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
            <img id="report-logo" src="{{ asset('img/sinemaku.png') }}" alt="logo" style="display:none">
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
                    <h3 id="metric-audience">{{ number_format($totalPenonton) }}</h3>
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
                    <p>Bioskop Dianalisis</p>
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

<section class="chart dashboard-analytics" style="display: none">
    <div class="analytics-section-heading">
        <div>
            <h2>Audience Performance</h2>
            <p>Analisis performa film berdasarkan kota, show, dan jaringan bioskop.</p>
        </div>
        <span class="badge badge-light-primary px-3 py-2">6 grafik interaktif</span>
    </div>
    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon"><i class="fal fa-map-marker-alt"></i></span>
                        <div class="analytics-card-title">
                            <h3>TOP 20 Kota</h3>
                            <span>Kota dengan jumlah penonton tertinggi</span>
                        </div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="topCities" data-title="Top 20 Kota" title="Download PDF Top 20 Kota" aria-label="Download PDF Top 20 Kota">
                        <i class="fal fa-file-pdf"></i>
                    </button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart is-tall">
                        <canvas id="topCitiesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div id="data-summary" class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon is-purple"><i class="fal fa-list-ol"></i></span>
                        <div class="analytics-card-title">
                            <h3>Ranking Kota</h3>
                            <span>Urutan performa berdasarkan penonton</span>
                        </div>
                    </div>
                </div>
                <div class="analytics-card-body pt-0 px-0">
                    <div class="table-responsive" style="max-height: 488px; overflow-y: auto;">
                        <table id="summary-table" class="table ranking-table mb-0 w-100">
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
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon is-green"><i class="fal fa-chart-line"></i></span>
                        <div class="analytics-card-title"><h3>Penonton per Show</h3><span>Distribusi audience di setiap urutan show</span></div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="shows" data-title="Penonton per Show" title="Download PDF Penonton per Show" aria-label="Download PDF Penonton per Show"><i class="fal fa-file-pdf"></i></button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart"><canvas id="showsChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon is-purple"><i class="fal fa-chart-pie"></i></span>
                        <div class="analytics-card-title"><h3>Komposisi Jaringan Bioskop</h3><span>Kontribusi penonton per kategori bioskop</span></div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="viewersByCinema" data-title="Komposisi Jaringan Bioskop" title="Download PDF Komposisi Jaringan Bioskop" aria-label="Download PDF Komposisi Jaringan Bioskop"><i class="fal fa-file-pdf"></i></button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart is-donut"><canvas id="viewersByCinemaChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon"><i class="fal fa-building"></i></span>
                        <div class="analytics-card-title"><h3>TOP 20 Bioskop</h3><span>Lokasi bioskop dengan audience tertinggi</span></div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="topCinemas" data-title="Top 20 Bioskop" title="Download PDF Top 20 Bioskop" aria-label="Download PDF Top 20 Bioskop"><i class="fal fa-file-pdf"></i></button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart is-tall"><canvas id="topCinemasChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="analytics-section-heading mt-2">
        <div>
            <h2>Area yang Perlu Perhatian</h2>
            <p>Kota dan bioskop dengan audience terendah pada periode aktif.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon is-warning"><i class="fal fa-map-marker-alt"></i></span>
                        <div class="analytics-card-title"><h3>Underperforming Kota</h3><span>20 kota dengan audience terendah</span></div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="underCities" data-title="Underperforming Kota" title="Download PDF Underperforming Kota" aria-label="Download PDF Underperforming Kota"><i class="fal fa-file-pdf"></i></button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart is-tall"><canvas id="underperfCitiesChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <div class="analytics-card-heading">
                        <span class="analytics-card-icon is-danger"><i class="fal fa-building"></i></span>
                        <div class="analytics-card-title"><h3>Underperforming Bioskop</h3><span>20 bioskop dengan audience terendah</span></div>
                    </div>
                    <button type="button" class="chart-download-btn" data-chart="underCinemas" data-title="Underperforming Bioskop" title="Download PDF Underperforming Bioskop" aria-label="Download PDF Underperforming Bioskop"><i class="fal fa-file-pdf"></i></button>
                </div>
                <div class="analytics-card-body">
                    <div class="analytics-chart is-tall"><canvas id="underperfCinemasChart"></canvas></div>
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
        'rgba(98, 91, 214, 0.84)',
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

        const marginX = 14;
        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();
        const usableW = pageW - marginX * 2;
        const brandColor = [47, 69, 88];
        const accentColor = [153, 27, 27];
        const softRed = [254, 242, 242];
        const textColor = [31, 41, 55];
        const mutedColor = [107, 114, 128];
        const generatedAt = new Date().toLocaleString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const namaFilm = $('#nama_film').val() ? $('#nama_film option:selected').text() : '{{ $lastFilm }}';
        const tanggalMulai = $('#tanggal_mulai').val() || '-';
        const tanggalAkhir = $('#tanggal_akhir').val() || '-';
        const kategoriBioskop = $('#bioskop_kategori').val() ? $('#bioskop_kategori option:selected').text() : 'Semua';
        const reportPeriod = tanggalMulai !== '-' && tanggalAkhir !== '-' ? `${tanggalMulai} s.d. ${tanggalAkhir}` : '{{ $periodeText }}';
        const metricCities = $('#metric-cities').text() || '-';
        const metricShows = $('#metric-shows').text() || '-';
        const metricCinemas = $('#metric-cinemas').text() || '-';
        const cityRows = $('#summary-table tbody tr').toArray().map(function (row) {
            const cells = $(row).find('td');
            return [
                $(cells[0]).text(),
                $(cells[1]).text(),
                $(cells[2]).text()
            ];
        });
        const top20Audience = cityRows.reduce(function (total, row) {
            return total + Number(String(row[2] || '0').replace(/,/g, ''));
        }, 0);
        const topCityRows = cityRows.slice(0, 12);

        function fitImageSize(imgProps, maxW, maxH) {
            const r = Math.min(maxW / imgProps.width, maxH / imgProps.height);
            return { w: imgProps.width * r, h: imgProps.height * r };
        }

        function addLogo(x, y, size) {
            const logoEl = document.getElementById('report-logo');
            if (logoEl && logoEl.complete) {
                doc.addImage(logoEl, 'PNG', x, y, size, size, undefined, 'FAST');
            }
        }

        function addHeader(subtitle) {
            addLogo(marginX, 8, 14);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(13);
            doc.setTextColor(...textColor);
            doc.text("SINEMAKU PICTURES", marginX + 18, 13);
            doc.setFontSize(8.5);
            doc.setTextColor(...accentColor);
            doc.text("Audience Analytics Dashboard", marginX + 18, 18);

            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(...mutedColor);
            doc.text(subtitle || "Executive Report", pageW - marginX, 13, { align: "right" });
            doc.text("Generated: " + generatedAt, pageW - marginX, 18, { align: "right" });

            doc.setDrawColor(...accentColor);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
            doc.setDrawColor(209, 213, 219);
            doc.setLineWidth(0.15);
            doc.line(marginX, 27, pageW - marginX, 27);
        }

        function addFooter() {
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setDrawColor(229, 231, 235);
                doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                doc.setFont("helvetica", "normal");
                doc.setFontSize(7);
                doc.setTextColor(...mutedColor);
                doc.text("Sinemaku Pictures - Confidential Analytics Report", marginX, pageH - 8);
                doc.text(`Halaman ${i} dari ${pageCount}`, pageW - marginX, pageH - 8, { align: "right" });
            }
        }

        function addFilterBox() {
            const y = 33;
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(marginX, y, usableW, 22, 2, 2, "FD");

            const items = [
                ["Nama Film", namaFilm],
                ["Periode", reportPeriod],
                ["Kategori Bioskop", kategoriBioskop]
            ];
            const colW = usableW / 3;
            items.forEach(function (item, index) {
                const x = marginX + (colW * index) + 5;
                doc.setFont("helvetica", "bold");
                doc.setFontSize(7);
                doc.setTextColor(...mutedColor);
                doc.text(item[0].toUpperCase(), x, y + 8);
                doc.setFontSize(9);
                doc.setTextColor(...textColor);
                doc.text(String(item[1]), x, y + 15, { maxWidth: colW - 10 });
            });
        }

        function addMetricCard(label, value, x, y, w, h, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(x, y, w, h, 2, 2, "FD");
            doc.setFillColor(...color);
            doc.roundedRect(x, y, 3, h, 1.5, 1.5, "F");
            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.setTextColor(...textColor);
            doc.text(String(value), x + 8, y + 11);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(...mutedColor);
            doc.text(label, x + 8, y + 17);
        }

        function addSectionTitle(title, subtitle, y) {
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10.5);
            doc.setTextColor(...textColor);
            doc.text(title, marginX, y);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(...mutedColor);
            doc.text(subtitle, marginX, y + 5);
        }

        function addChartPanel(title, dataURL, x, y, boxW, boxH) {
            if (!dataURL) return;
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(x, y, boxW, boxH, 2, 2, "FD");
            doc.setFont("helvetica", "bold");
            doc.setFontSize(8.8);
            doc.setTextColor(...textColor);
            doc.text(title, x + 4, y + 7);

            const titleH = 11;
            const imgProps = doc.getImageProperties(dataURL);
            const size = fitImageSize(imgProps, boxW - 8, boxH - titleH - 4);
            const imgX = x + (boxW - size.w) / 2;
            const imgY = y + titleH + ((boxH - titleH - size.h) / 2);
            doc.addImage(dataURL, 'PNG', imgX, imgY, size.w, size.h, undefined, 'FAST');
        }

        const imgTopCities  = chartInstances.topCities ? chartInstances.topCities.toBase64Image() : null;
        const imgShows      = chartInstances.shows ? chartInstances.shows.toBase64Image() : null;
        const imgViewCinema = chartInstances.viewersByCinema ? chartInstances.viewersByCinema.toBase64Image() : null;
        const imgTopCinemas = chartInstances.topCinemas ? chartInstances.topCinemas.toBase64Image() : null;
        const imgUnderCity  = chartInstances.underCities ? chartInstances.underCities.toBase64Image() : null;
        const imgUnderCin   = chartInstances.underCinemas ? chartInstances.underCinemas.toBase64Image() : null;

        addHeader("Executive Summary");
        addFilterBox();

        const cardY = 62;
        const cardW = (usableW - 12) / 4;
        addMetricCard("Top 20 Penonton", top20Audience.toLocaleString('en-US'), marginX, cardY, cardW, 22, accentColor);
        addMetricCard("Kota Dianalisis", metricCities, marginX + cardW + 4, cardY, cardW, 22, [98, 91, 214]);
        addMetricCard("Show Terbaca", metricShows, marginX + (cardW + 4) * 2, cardY, cardW, 22, [40, 199, 162]);
        addMetricCard("Bioskop Teratas", metricCinemas, marginX + (cardW + 4) * 3, cardY, cardW, 22, [141, 124, 255]);

        addSectionTitle("Audience Performance Overview", "Ringkasan visual penonton berdasarkan filter yang sedang aktif.", 94);
        addChartPanel("TOP 20 Kota - Penonton Terbanyak", imgTopCities, marginX, 103, 170, 76);

        doc.autoTable({
            startY: 103,
            margin: { left: marginX + 176, right: marginX },
            tableWidth: usableW - 176,
            head: [["Rank", "Kota", "Penonton"]],
            body: topCityRows,
            theme: "grid",
            styles: {
                font: "helvetica",
                fontSize: 7.6,
                cellPadding: 2.2,
                textColor: textColor
            },
            headStyles: {
                fillColor: brandColor,
                textColor: [255, 255, 255],
                fontStyle: "bold"
            },
            alternateRowStyles: {
                fillColor: [249, 250, 251]
            },
            columnStyles: {
                0: { halign: "center", cellWidth: 16 },
                1: { cellWidth: "auto" },
                2: { halign: "right", cellWidth: 30 }
            },
            didDrawPage: function () {
                addHeader("Executive Summary");
            }
        });

        doc.setFillColor(...softRed);
        doc.setDrawColor(254, 202, 202);
        doc.roundedRect(marginX + 176, 166, usableW - 176, 13, 2, 2, "FD");
        doc.setFont("helvetica", "bold");
        doc.setFontSize(7.7);
        doc.setTextColor(...accentColor);
        doc.text("Catatan", marginX + 181, 171);
        doc.setFont("helvetica", "normal");
        doc.setTextColor(...textColor);
        doc.text("Ranking tabel menampilkan 12 kota teratas agar halaman tetap ringkas dan mudah dibaca.", marginX + 181, 176, { maxWidth: usableW - 186 });

        doc.addPage("a4", "landscape");
        addHeader("Chart Details");

        const panelGap = 6;
        const colW = (usableW - panelGap) / 2;
        const panelH = 48;
        const y1 = 35;
        const y2 = y1 + panelH + panelGap;
        const y3 = y2 + panelH + panelGap;

        addChartPanel("Grafik Show", imgShows, marginX, y1, colW, panelH);
        addChartPanel("Penonton per Bioskop", imgViewCinema, marginX + colW + panelGap, y1, colW, panelH);
        addChartPanel("TOP 20 Bioskop", imgTopCinemas, marginX, y2, colW, panelH);
        addChartPanel("Underperforming Kota", imgUnderCity, marginX + colW + panelGap, y2, colW, panelH);
        addChartPanel("Underperforming Bioskop", imgUnderCin, marginX, y3, usableW, panelH);

        addFooter();

        doc.save("report-charts-dashboard.pdf");
    });

    function destroyIfExists(inst) {
        if (inst && typeof inst.destroy === 'function') inst.destroy();
    }

    function commonOptions(extra) {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            layout: {
                padding: { top: 6, right: 10, bottom: 2, left: 2 }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(126, 149, 178, 0.10)', drawTicks: false },
                    ticks: {
                        color: '#8a93a6',
                        padding: 8,
                        font: { size: 11, weight: '600' },
                        callback: function (value) { return compactNumber(value); }
                    }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { display: false },
                    ticks: {
                        color: '#596276',
                        autoSkip: false,
                        padding: 8,
                        font: { size: 11, weight: '600' }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                datalabels: { display: false },
                tooltip: {
                    backgroundColor: '#202638',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return (context.dataset.label || 'Penonton') + ': ' + Number(context.raw || 0).toLocaleString('id-ID');
                        }
                    }
                }
            }
        };

        return $.extend(true, {}, baseOptions, extra || {});
    }

    function compactNumber(value) {
        const number = Number(value || 0);
        if (Math.abs(number) >= 1000000) return (number / 1000000).toFixed(1).replace('.0', '') + 'M';
        if (Math.abs(number) >= 1000) return (number / 1000).toFixed(1).replace('.0', '') + 'K';
        return number.toLocaleString('id-ID');
    }

    function dataset(label, data, color, borderColor) {
        return {
            label: label,
            data: data,
            backgroundColor: color || 'rgba(98, 91, 214, 0.84)',
            borderColor: borderColor || color || 'rgba(98, 91, 214, 1)',
            borderWidth: 0,
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 22
        };
    }

    function downloadSingleChart(chartKey, title) {
        const chart = chartInstances[chartKey];
        if (!chart) {
            alert('Grafik belum siap untuk didownload.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');
        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();
        const marginX = 14;
        const usableW = pageW - (marginX * 2);
        const textColor = [31, 41, 55];
        const mutedColor = [107, 114, 128];
        const accentColor = [153, 27, 27];
        const brandColor = [47, 69, 88];
        const film = $('#nama_film').val() ? $('#nama_film option:selected').text() : '{{ $lastFilm }}';
        const start = $('#tanggal_mulai').val() || '-';
        const end = $('#tanggal_akhir').val() || '-';
        const period = start !== '-' && end !== '-' ? start + ' s.d. ' + end : '{{ $periodeText }}';
        const category = $('#bioskop_kategori').val() ? $('#bioskop_kategori option:selected').text() : 'Semua';
        const generatedAt = new Date().toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        const labels = (chart.data.labels || []).map(function (label) { return String(label); });
        const values = ((chart.data.datasets[0] || {}).data || []).map(function (value) { return Number(value || 0); });
        const total = values.reduce(function (sum, value) { return sum + value; }, 0);
        const average = values.length ? total / values.length : 0;
        const maximum = values.length ? Math.max.apply(null, values) : 0;
        const minimum = values.length ? Math.min.apply(null, values) : 0;
        const maxIndex = values.indexOf(maximum);
        const minIndex = values.indexOf(minimum);
        const descriptions = {
            topCities: 'Peringkat 20 kota dengan jumlah penonton tertinggi pada filter aktif.',
            shows: 'Persebaran jumlah penonton berdasarkan urutan show pada filter aktif.',
            viewersByCinema: 'Komposisi kontribusi penonton berdasarkan kategori atau jaringan bioskop.',
            topCinemas: 'Peringkat 20 lokasi bioskop dengan jumlah penonton tertinggi.',
            underCities: 'Daftar 20 kota dengan jumlah penonton terendah yang memerlukan perhatian.',
            underCinemas: 'Daftar 20 bioskop dengan jumlah penonton terendah yang memerlukan perhatian.'
        };
        const dimensionLabels = {
            topCities: 'Kota',
            shows: 'Show',
            viewersByCinema: 'Jaringan Bioskop',
            topCinemas: 'Bioskop',
            underCities: 'Kota',
            underCinemas: 'Bioskop'
        };
        const dimensionLabel = dimensionLabels[chartKey] || 'Kategori';

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        }

        function addLogo(x, y, size) {
            const logo = document.getElementById('report-logo');
            if (logo && logo.complete) {
                doc.addImage(logo, 'PNG', x, y, size, size, undefined, 'FAST');
            }
        }

        function addHeader(sectionName) {
            addLogo(marginX, 8, 14);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(13);
            doc.setTextColor(...textColor);
            doc.text('SINEMAKU PICTURES', marginX + 18, 13);
            doc.setFontSize(8.5);
            doc.setTextColor(...accentColor);
            doc.text('Audience Analytics Dashboard', marginX + 18, 18);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor(...mutedColor);
            doc.text(sectionName, pageW - marginX, 13, { align: 'right' });
            doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });
            doc.setDrawColor(...accentColor);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
        }

        function addFooter() {
            const pages = doc.internal.getNumberOfPages();
            for (let page = 1; page <= pages; page++) {
                doc.setPage(page);
                doc.setDrawColor(229, 231, 235);
                doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor(...mutedColor);
                doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                doc.text('Halaman ' + page + ' dari ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
            }
        }

        function addFilterBox() {
            const y = 48;
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(marginX, y, usableW, 22, 2, 2, 'FD');
            const filters = [
                ['Nama Film', film],
                ['Periode', period],
                ['Kategori Bioskop', category]
            ];
            const columnWidth = usableW / filters.length;
            filters.forEach(function (filter, index) {
                const x = marginX + (columnWidth * index) + 5;
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7);
                doc.setTextColor(...mutedColor);
                doc.text(filter[0].toUpperCase(), x, y + 8);
                doc.setFontSize(9);
                doc.setTextColor(...textColor);
                doc.text(String(filter[1]), x, y + 15, { maxWidth: columnWidth - 10 });
            });
        }

        function addMetric(label, value, x, y, width, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(x, y, width, 22, 2, 2, 'FD');
            doc.setFillColor(...color);
            doc.roundedRect(x, y, 3, 22, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.setTextColor(...textColor);
            doc.text(String(value), x + 8, y + 10);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.2);
            doc.setTextColor(...mutedColor);
            doc.text(label, x + 8, y + 17);
        }

        function addChartImage() {
            const dataUrl = chart.toBase64Image();
            const image = doc.getImageProperties(dataUrl);
            const boxX = marginX;
            const boxY = 108;
            const boxW = usableW;
            const boxH = 71;
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(229, 231, 235);
            doc.roundedRect(boxX, boxY, boxW, boxH, 2, 2, 'FD');
            const ratio = Math.min((boxW - 10) / image.width, (boxH - 8) / image.height);
            const width = image.width * ratio;
            const height = image.height * ratio;
            doc.addImage(dataUrl, 'PNG', boxX + ((boxW - width) / 2), boxY + ((boxH - height) / 2), width, height, undefined, 'FAST');
        }

        addHeader('Individual Chart Report');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(16);
        doc.setTextColor(...textColor);
        doc.text(title, marginX, 36);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(...mutedColor);
        doc.text(descriptions[chartKey] || 'Detail performa berdasarkan filter aktif.', marginX, 42);
        addFilterBox();

        const metricGap = 4;
        const metricWidth = (usableW - (metricGap * 3)) / 4;
        addMetric('Jumlah ' + dimensionLabel, formatNumber(values.length), marginX, 78, metricWidth, [98, 91, 214]);
        addMetric('Total dalam Grafik', formatNumber(total), marginX + metricWidth + metricGap, 78, metricWidth, [40, 199, 162]);
        addMetric('Nilai Tertinggi', formatNumber(maximum), marginX + ((metricWidth + metricGap) * 2), 78, metricWidth, [141, 124, 255]);
        addMetric('Rata-rata', formatNumber(average), marginX + ((metricWidth + metricGap) * 3), 78, metricWidth, [255, 159, 67]);
        addChartImage();

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(...accentColor);
        doc.text('HIGHLIGHT', marginX, 186);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...textColor);
        doc.text(
            'Tertinggi: ' + (labels[maxIndex] || '-') + ' (' + formatNumber(maximum) + ')' +
            '   |   Terendah: ' + (labels[minIndex] || '-') + ' (' + formatNumber(minimum) + ')' +
            '   |   Data ditampilkan: ' + values.length + ' item.',
            marginX,
            191,
            { maxWidth: usableW }
        );

        const detailRows = labels.map(function (label, index) {
            const contribution = total > 0 ? ((values[index] / total) * 100).toFixed(2) + '%' : '0.00%';
            return [index + 1, label, formatNumber(values[index]), contribution];
        });

        doc.addPage('a4', 'landscape');
        doc.autoTable({
            startY: 42,
            margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
            head: [['Rank', dimensionLabel, 'Jumlah Penonton', 'Kontribusi']],
            body: detailRows,
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 8, cellPadding: 2.5, textColor: textColor },
            headStyles: { fillColor: brandColor, textColor: [255, 255, 255], fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 18 },
                1: { cellWidth: 'auto' },
                2: { halign: 'right', cellWidth: 42 },
                3: { halign: 'right', cellWidth: 34 }
            },
            didDrawPage: function () {
                addHeader('Detail Data - ' + title);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(11);
                doc.setTextColor(...textColor);
                doc.text('Rincian Data Grafik', marginX, 34);
            }
        });

        addFooter();
        const safeTitle = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        doc.save('report-' + safeTitle + '.pdf');
    }

    $(document).on('click', '.chart-download-btn', function () {
        downloadSingleChart($(this).data('chart'), $(this).data('title'));
    });

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

                const metrics = payload.metrics || {};
                $('#metric-audience').text(Number(metrics.audience || 0).toLocaleString('id-ID'));
                $('#metric-cities').text(Number(metrics.cities || labelsTopCities.length).toLocaleString('id-ID'));
                $('#metric-shows').text(Number(metrics.shows || (payload.shows_over_time || []).length).toLocaleString('id-ID'));
                $('#metric-cinemas').text(Number(metrics.cinemas || (payload.top_cinemas || []).length).toLocaleString('id-ID'));

                destroyIfExists(chartInstances.topCities);
                chartInstances.topCities = new Chart(
                    document.getElementById('topCitiesChart').getContext('2d'),
                    {
                        type: 'bar',
                        data: {
                            labels: labelsTopCities,
                            datasets: [dataset('Jumlah Penonton', valuesTopCities, 'rgba(98, 91, 214, 0.84)')]
                        },
                        options: commonOptions({ indexAxis: 'y' })
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
                                label: 'Jumlah Penonton',
                                data: showsValues,
                                fill: true,
                                tension: 0.35,
                                borderColor: 'rgba(98, 91, 214, 1)',
                                backgroundColor: 'rgba(98, 91, 214, 0.12)',
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: 'rgba(98, 91, 214, 1)',
                                pointRadius: 4,
                                borderWidth: 3
                            }]
                        },
                        options: commonOptions({
                            scales: {
                                x: {
                                    ticks: {
                                        callback: function (value) { return this.getLabelForValue(value); }
                                    }
                                },
                                y: {
                                    ticks: {
                                        callback: function (value) { return compactNumber(value); }
                                    }
                                }
                            }
                        })
                    }
                );

                const vb = payload.viewers_by_cinema || [];
                const vbLabels = vb.map(x => x.bioskop);
                const vbValues = vb.map(x => Number(x.penonton || 0));

                destroyIfExists(chartInstances.viewersByCinema);
                chartInstances.viewersByCinema = new Chart(
                    document.getElementById('viewersByCinemaChart').getContext('2d'),
                    {
                        type: 'doughnut',
                        data: {
                            labels: vbLabels,
                            datasets: [{
                                label: 'Penonton',
                                data: vbValues,
                                backgroundColor: chartPalette,
                                borderColor: '#ffffff',
                                borderWidth: 4,
                                hoverOffset: 8
                            }]
                        },
                        options: commonOptions({
                            cutout: '68%',
                            scales: { x: { display: false }, y: { display: false } },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: { usePointStyle: true, boxWidth: 8, padding: 18, color: '#596276', font: { size: 11, weight: '600' } }
                                }
                            }
                        })
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
                            datasets: [dataset('Penonton', topCValues, 'rgba(40, 199, 162, 0.82)')]
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
                            datasets: [dataset('Penonton', ucValues, 'rgba(255, 184, 77, 0.84)')]
                        },
                        options: commonOptions({ indexAxis: 'y' })
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
                            datasets: [dataset('Penonton', ubValues, 'rgba(255, 107, 125, 0.80)')]
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
