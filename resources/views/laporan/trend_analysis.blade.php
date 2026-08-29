@extends('layouts.page')

@section('title', 'Trend Analysis')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<style>
    .trend-filter,
    .trend-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .trend-filter {
        padding: 18px;
    }

    .trend-panel {
        display: none;
        padding: 20px;
    }

    .trend-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .trend-title h3 {
        margin: 0;
        color: #111827;
        font-size: 20px;
        font-weight: 800;
    }

    .trend-title span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
    }

    .trend-badge {
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 800;
        padding: 7px 12px;
        white-space: nowrap;
    }

    .trend-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .trend-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .trend-kpi {
        border: 1px solid #e5e7eb;
        border-left: 4px solid #2f4558;
        border-radius: 8px;
        background: #f9fafb;
        padding: 12px;
    }

    .trend-kpi small {
        display: block;
        color: #6b7280;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .trend-kpi strong {
        display: block;
        color: #111827;
        font-size: 18px;
        line-height: 1.25;
    }

    .trend-kpi--positive {
        border-left-color: #047857;
        background: #ecfdf5;
    }

    .trend-kpi--negative {
        border-left-color: #991b1b;
        background: #fef2f2;
    }

    .trend-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
        gap: 14px;
        margin-bottom: 14px;
    }

    .trend-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        background: #ffffff;
    }

    .trend-card h4 {
        margin: 0 0 10px;
        color: #111827;
        font-size: 15px;
        font-weight: 800;
    }

    .trend-chart-wrap {
        min-height: 360px;
        position: relative;
    }

    .trend-list {
        padding-left: 18px;
        margin: 0;
        color: #374151;
        line-height: 1.6;
    }

    .trend-table-wrap {
        overflow-x: auto;
    }

    .trend-table th,
    .trend-table td {
        white-space: nowrap;
    }

    .trend-empty,
    .trend-loading {
        display: none;
        text-align: center;
        padding: 26px;
        color: #6b7280;
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        background: #f9fafb;
    }

    @media (max-width: 992px) {
        .trend-grid {
            grid-template-columns: 1fr;
        }

        .trend-title {
            display: block;
        }

        .trend-badge {
            display: inline-block;
            margin-top: 10px;
        }

        .trend-actions {
            justify-content: flex-start;
        }
    }
</style>
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class="subheader-icon fal fa-chart-area"></i>
        Trend <span class="fw-300">Analysis</span>
        <small>Analisa pergerakan harian untuk Gross, Total Production House, penonton, ATP, dan occupancy.</small>
    </h1>
</div>

<div class="trend-filter mb-4">
    <div class="trend-title mb-3">
        <div>
            <h3>Filter Trend</h3>
            <span>Gunakan periode untuk membaca arah pergerakan performa dari hari ke hari.</span>
        </div>
        <div class="trend-badge">Movement View</div>
    </div>
    <form id="trend-form" autocomplete="off">
        <div class="row align-items-end">
            <div class="form-group col-lg-4 mb-3">
                {{ Form::label('nama_film','Nama Film',['class' => 'form-label'])}}
                {!! Form::select('nama_film', $nama_film, 'ALL', ['id'=>'nama_film','class' => 'custom-select']) !!}
            </div>
            <div class="form-group col-sm-6 col-lg-2 mb-3">
                {{ Form::label('tgl_mulai','Tanggal Mulai',['class' => 'form-label'])}}
                <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control">
            </div>
            <div class="form-group col-sm-6 col-lg-2 mb-3">
                {{ Form::label('tgl_akhir','Tanggal Akhir',['class' => 'form-label'])}}
                <input type="date" id="tgl_akhir" name="tgl_akhir" class="form-control">
            </div>
            <div class="form-group col-lg-4 mb-3">
                {{ Form::label('bioskop_kategori','Kategori Bioskop',['class' => 'form-label'])}}
                {!! Form::select('bioskop_kategori', $bioskop_kategori, 'ALL', ['id'=>'bioskop_kategori','class' => 'custom-select']) !!}
            </div>
            <div class="form-group col-lg-4 mb-3">
                {{ Form::label('kota','Kota',['class' => 'form-label'])}}
                {!! Form::select('kota', $kota, 'ALL', ['id'=>'kota','class' => 'custom-select']) !!}
            </div>
            <div class="form-group col-lg-4 mb-3">
                {{ Form::label('nama_bioskop','Nama Bioskop',['class' => 'form-label'])}}
                {!! Form::select('nama_bioskop', $nama_bioskop, 'ALL', ['id'=>'nama_bioskop','class' => 'custom-select']) !!}
            </div>
            <div class="form-group col-lg-3 mb-3">
                {{ Form::label('type_tiket','Tipe Tiket',['class' => 'form-label'])}}
                {!! Form::select('type_tiket', $type_tiket, 'ALL', ['id'=>'type_tiket','class' => 'custom-select']) !!}
            </div>
            <div class="form-group col-lg-1 mb-3 filter-search-column">
                <button type="button" id="search-btn" class="btn btn-primary w-100 filter-search-btn" title="Tampilkan Trend Analysis" aria-label="Tampilkan Trend Analysis">
                    <i class="fal fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<div id="trend-loading" class="trend-loading mb-4">Memuat trend analysis...</div>
<div id="trend-empty" class="trend-empty mb-4">Tidak ada data trend pada filter ini.</div>

<section id="trend-panel" class="trend-panel">
    <img id="trend-report-logo" src="{{ asset('img/sinemaku.png') }}" alt="Sinemaku Pictures" style="display:none">
    <div class="trend-title">
        <div>
            <h3>Daily Movement</h3>
            <span id="trend-period">Semua periode</span>
        </div>
        <div class="trend-actions">
            <button type="button" id="download-trend-pdf" class="btn btn-danger btn-sm" style="display:none">
                <i class="fal fa-file-pdf mr-1"></i> Download PDF
            </button>
            <div class="trend-badge" id="trend-status">Ready</div>
        </div>
    </div>

    <div class="trend-kpi-grid">
        <div class="trend-kpi">
            <small>Total Production House</small>
            <strong id="kpi-total-ph">0.00</strong>
        </div>
        <div class="trend-kpi">
            <small>Gross</small>
            <strong id="kpi-gross">0.00</strong>
        </div>
        <div class="trend-kpi">
            <small>Penonton</small>
            <strong id="kpi-audience">0</strong>
        </div>
        <div class="trend-kpi">
            <small>Rata-rata Production House / Hari</small>
            <strong id="kpi-avg-ph">0.00</strong>
        </div>
        <div class="trend-kpi">
            <small>Period Movement</small>
            <strong id="kpi-movement">0.00%</strong>
        </div>
        <div class="trend-kpi">
            <small>Best Day</small>
            <strong id="kpi-best-day">-</strong>
        </div>
    </div>

    <div class="trend-grid">
        <div class="trend-card">
            <h4>Daily Trend Chart</h4>
            <div class="trend-chart-wrap">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <div class="trend-card">
            <h4>Movement Notes</h4>
            <ul id="trend-notes" class="trend-list"></ul>
        </div>
    </div>

    <div class="trend-card">
        <h4>Daily Movement Table</h4>
        <div class="trend-table-wrap">
            <table class="table table-hover table-striped trend-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Penonton</th>
                        <th>Gross</th>
                        <th>Total Production House</th>
                        <th>ATP</th>
                        <th>Occupancy</th>
                        <th>Production House Growth</th>
                        <th>Gross Growth</th>
                        <th>Audience Growth</th>
                    </tr>
                </thead>
                <tbody id="trend-table-body"></tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/sinemaku-chart-value-labels.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
    if (window.Chart && window.SinemakuChartValueLabels) {
        Chart.register(window.SinemakuChartValueLabels);
    }

    var trendChart = null;
    var latestTrendData = null;

    $(document).ready(function(){
        $('#nama_film, #bioskop_kategori, #kota, #nama_bioskop, #type_tiket').select2({ width: '100%' });

        $('#search-btn').on('click', function () {
            loadTrend();
        });

        $('#download-trend-pdf').on('click', function () {
            downloadTrendPdf();
        });
    });

    function loadTrend() {
        $('#trend-panel').hide();
        $('#trend-empty').hide();
        $('#trend-loading').show();
        $('#download-trend-pdf').hide();
        latestTrendData = null;

        $.ajax({
            url: '{{ route('trend-analysis.data') }}',
            type: 'GET',
            data: {
                nama_film: $('#nama_film').val(),
                tgl_mulai: $('#tgl_mulai').val(),
                tgl_akhir: $('#tgl_akhir').val(),
                bioskop_kategori: $('#bioskop_kategori').val(),
                kota: $('#kota').val(),
                nama_bioskop: $('#nama_bioskop').val(),
                type_tiket: $('#type_tiket').val()
            },
            success: function (response) {
                $('#trend-loading').hide();

                if (!response.daily || !response.daily.length) {
                    $('#trend-empty').show();
                    return;
                }

                renderTrend(response);
                latestTrendData = response;
                $('#trend-panel').show();
                $('#download-trend-pdf').show();
            },
            error: function () {
                $('#trend-loading').hide();
                alert('Gagal memuat trend analysis.');
            }
        });
    }

    function renderTrend(data) {
        var summary = data.summary || {};

        $('#trend-period').text(reportPeriod());
        $('#trend-status').text(summary.period_change >= 0 ? 'Positive Movement' : 'Needs Attention');

        $('#kpi-total-ph').text(formatCurrency(summary.total_ph));
        $('#kpi-gross').text(formatCurrency(summary.gross));
        $('#kpi-audience').text(formatNumber(summary.audience));
        $('#kpi-avg-ph').text(formatCurrency(summary.avg_daily_ph));
        $('#kpi-movement').text(formatNullablePercent(summary.period_change));
        $('#kpi-best-day').text(summary.best_day ? formatDisplayDate(summary.best_day.tanggal) : '-');

        $('#kpi-movement').closest('.trend-kpi')
            .toggleClass('trend-kpi--positive', Number(summary.period_change || 0) >= 0)
            .toggleClass('trend-kpi--negative', Number(summary.period_change || 0) < 0);

        var notes = (data.notes || []).map(function (note) {
            return '<li>' + escapeHtml(formatDatesInText(note)) + '</li>';
        }).join('');
        $('#trend-notes').html(notes || '<li>Tidak ada catatan movement.</li>');

        renderChart(data.daily || []);
        renderTable(data.daily || []);
    }

    function renderChart(rows) {
        var labels = rows.map(function (row) { return formatDisplayDate(row.tanggal); });
        var totalPh = rows.map(function (row) { return Number(row.total_ph || 0); });
        var gross = rows.map(function (row) { return Number(row.gross || 0); });
        var audience = rows.map(function (row) { return Number(row.audience || 0); });

        if (trendChart) {
            trendChart.destroy();
        }

        trendChart = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Production House',
                        data: totalPh,
                        borderColor: '#00a86b',
                        backgroundColor: '#00a86b',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: false,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#00a86b',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'money'
                    },
                    {
                        label: 'Gross',
                        data: gross,
                        borderColor: '#f97316',
                        backgroundColor: '#f97316',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: false,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#f97316',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'money'
                    },
                    {
                        label: 'Penonton',
                        data: audience,
                        borderColor: '#0284c7',
                        backgroundColor: '#0284c7',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: false,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0284c7',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        yAxisID: 'audience'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (context.dataset.yAxisID === 'audience') {
                                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                                }
                                return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    money: {
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            callback: function (value) {
                                return compactNumber(value);
                            }
                        }
                    },
                    audience: {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            callback: function (value) {
                                return compactNumber(value);
                            }
                        }
                    }
                }
            }
        });
    }

    function renderTable(rows) {
        var html = rows.map(function (row) {
            return [
                '<tr>',
                    '<td>' + escapeHtml(formatDisplayDate(row.tanggal)) + '</td>',
                    '<td class="text-right">' + formatNumber(row.audience) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.gross) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.total_ph) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.atp) + '</td>',
                    '<td class="text-right">' + formatPercent(row.occupancy_rate) + '</td>',
                    '<td class="text-right">' + formatNullablePercent(row.total_ph_change) + '</td>',
                    '<td class="text-right">' + formatNullablePercent(row.gross_change) + '</td>',
                    '<td class="text-right">' + formatNullablePercent(row.audience_change) + '</td>',
                '</tr>'
            ].join('');
        }).join('');

        $('#trend-table-body').html(html || '<tr><td colspan="9" class="text-center">Tidak ada data.</td></tr>');
    }

    function downloadTrendPdf() {
        if (!latestTrendData || !latestTrendData.daily || !latestTrendData.daily.length || !trendChart) {
            alert('Data Trend Analysis belum tersedia. Silakan jalankan filter terlebih dahulu.');
            return;
        }

        var jsPDF = window.jspdf && window.jspdf.jsPDF;
        if (!jsPDF) {
            alert('Library PDF belum berhasil dimuat. Silakan refresh halaman dan coba kembali.');
            return;
        }

        var doc = new jsPDF('l', 'mm', 'a4');
        var pageW = doc.internal.pageSize.getWidth();
        var pageH = doc.internal.pageSize.getHeight();
        var marginX = 14;
        var usableW = pageW - (marginX * 2);
        var brandColor = [47, 69, 88];
        var accentColor = [153, 27, 27];
        var textColor = [31, 41, 55];
        var mutedColor = [107, 114, 128];
        var borderColor = [229, 231, 235];
        var summary = latestTrendData.summary || {};
        var daily = latestTrendData.daily || [];
        var notes = latestTrendData.notes || [];
        var generatedDate = new Date();
        var padDatePart = function (value) {
            return String(value).padStart(2, '0');
        };
        var generatedAt = padDatePart(generatedDate.getDate())
            + '-' + padDatePart(generatedDate.getMonth() + 1)
            + '-' + generatedDate.getFullYear()
            + ' ' + padDatePart(generatedDate.getHours())
            + ':' + padDatePart(generatedDate.getMinutes());

        function selectedText(selector) {
            var value = $(selector).val();
            return value ? $(selector + ' option:selected').text() : '-';
        }

        var filters = [
            ['Nama Film', selectedText('#nama_film')],
            ['Periode', reportPeriod()],
            ['Kategori Bioskop', selectedText('#bioskop_kategori')],
            ['Kota', selectedText('#kota')],
            ['Nama Bioskop', selectedText('#nama_bioskop')],
            ['Tipe Tiket', selectedText('#type_tiket')]
        ];

        function pdfNumber(value, decimals) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: decimals || 0,
                maximumFractionDigits: decimals || 0
            });
        }

        function pdfPercent(value) {
            if (value === null || typeof value === 'undefined') {
                return '-';
            }

            return pdfNumber(value, 2) + '%';
        }

        function addLogo(x, y, size) {
            var logo = document.getElementById('trend-report-logo');
            if (logo && logo.complete && logo.naturalWidth) {
                doc.addImage(logo, 'PNG', x, y, size, size, undefined, 'FAST');
            }
        }

        function addHeader(sectionName) {
            addLogo(marginX, 8, 14);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor.apply(doc, textColor);
            doc.text('SINEMAKU PICTURES', marginX + 18, 13);
            doc.setFontSize(9.5);
            doc.setTextColor.apply(doc, accentColor);
            doc.text('Audience Analytics Dashboard', marginX + 18, 18);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8.5);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(sectionName || 'Trend Analysis Report', pageW - marginX, 13, { align: 'right' });
            doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });

            doc.setDrawColor.apply(doc, accentColor);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
            doc.setDrawColor.apply(doc, [209, 213, 219]);
            doc.setLineWidth(0.15);
            doc.line(marginX, 27, pageW - marginX, 27);
        }

        function addFooter() {
            var pageCount = doc.internal.getNumberOfPages();
            for (var page = 1; page <= pageCount; page++) {
                doc.setPage(page);
                doc.setDrawColor.apply(doc, borderColor);
                doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                doc.text('Halaman ' + page + ' dari ' + pageCount, pageW - marginX, pageH - 8, { align: 'right' });
            }
        }

        function addFilterBox() {
            var y = 46;
            var boxH = 20;
            var columnW = usableW / 3;
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(marginX, y, usableW, boxH, 2, 2, 'FD');

            filters.forEach(function (filter, index) {
                var column = index % 3;
                var row = Math.floor(index / 3);
                var x = marginX + (columnW * column) + 5;
                var itemY = y + 6.5 + (row * 9);
                var label = filter[0].toUpperCase() + ':';
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(6.2);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text(label, x, itemY);
                var labelWidth = doc.getTextWidth(label) + 2;
                doc.setFontSize(7.4);
                doc.setTextColor.apply(doc, textColor);
                doc.text(String(filter[1] || '-'), x + labelWidth, itemY, {
                    maxWidth: columnW - labelWidth - 8
                });
            });
        }

        function addMetric(label, value, x, y, width, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(x, y, width, 20, 2, 2, 'FD');
            doc.setFillColor.apply(doc, color);
            doc.roundedRect(x, y, 3, 20, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(11.5);
            doc.setTextColor.apply(doc, textColor);
            doc.text(String(value), x + 7, y + 9, { maxWidth: width - 10 });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(label, x + 7, y + 15.5, { maxWidth: width - 10 });
        }

        function addMovementInsightPanel(y) {
            var height = 48;
            var gap = 5;
            var notesWidth = (usableW - gap) * 0.62;
            var detailX = marginX + notesWidth + gap;
            var detailWidth = usableW - notesWidth - gap;
            var movementNotes = notes.length ? notes : ['Tidak ada catatan movement.'];
            var bestGrowthDay = summary.best_growth_day || null;
            var summaryDetails = [
                'Hari dianalisis: ' + pdfNumber(summary.day_count, 0),
                'Production House hari pertama: ' + pdfNumber(summary.first_day_ph, 2),
                'Production House hari terakhir: ' + pdfNumber(summary.last_day_ph, 2),
                'Best growth day: ' + (bestGrowthDay ? formatDisplayDate(bestGrowthDay.tanggal) : '-'),
                'Pertumbuhan Production House terbaik: ' + (bestGrowthDay ? pdfPercent(bestGrowthDay.total_ph_change) : '-')
            ];

            doc.setFillColor(249, 250, 251);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(marginX, y, usableW, height, 2, 2, 'FD');
            doc.line(detailX - (gap / 2), y + 5, detailX - (gap / 2), y + height - 5);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, accentColor);
            doc.text('MOVEMENT NOTES', marginX + 5, y + 7);
            doc.text('SUMMARY DETAIL', detailX + 3, y + 7);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor.apply(doc, textColor);
            var noteY = y + 14;
            movementNotes.forEach(function (note, index) {
                var lines = doc.splitTextToSize((index + 1) + '. ' + formatDatesInText(note), notesWidth - 10);
                doc.text(lines, marginX + 5, noteY);
                noteY += (lines.length * 4) + 1.5;
            });

            summaryDetails.forEach(function (detail, index) {
                doc.text(detail, detailX + 3, y + 14 + (index * 5.5), {
                    maxWidth: detailWidth - 6
                });
            });
        }

        function addChartPanel(x, y, width, height) {
            var imageData = trendChart.toBase64Image();
            var image = doc.getImageProperties(imageData);
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(x, y, width, height, 2, 2, 'FD');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9.5);
            doc.setTextColor.apply(doc, textColor);
            doc.text('Daily Trend Chart - Total Production House, Gross, dan Penonton', x + 4, y + 7);

            var ratio = Math.min((width - 10) / image.width, (height - 13) / image.height);
            var imageW = image.width * ratio;
            var imageH = image.height * ratio;
            doc.addImage(
                imageData,
                'PNG',
                x + ((width - imageW) / 2),
                y + 10 + ((height - 12 - imageH) / 2),
                imageW,
                imageH,
                undefined,
                'FAST'
            );
        }

        addHeader('Executive Summary - Trend Analysis');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Trend Analysis Report', marginX, 35);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor.apply(doc, mutedColor);
        doc.text('Analisa movement harian berdasarkan filter laporan yang sedang aktif.', marginX, 41);
        addFilterBox();

        var metricGap = 4;
        var metricW = (usableW - (metricGap * 3)) / 4;
        var metricColors = [[98, 91, 214], [40, 199, 162], [153, 27, 27], [255, 159, 67]];
        addMetric('Total Production House', pdfNumber(summary.total_ph, 2), marginX, 69, metricW, metricColors[0]);
        addMetric('Gross', pdfNumber(summary.gross, 2), marginX + metricW + metricGap, 69, metricW, metricColors[1]);
        addMetric('Penonton', pdfNumber(summary.audience, 0), marginX + ((metricW + metricGap) * 2), 69, metricW, metricColors[2]);
        addMetric('Rata-rata Production House / Hari', pdfNumber(summary.avg_daily_ph, 2), marginX + ((metricW + metricGap) * 3), 69, metricW, metricColors[3]);
        addMetric('Period Movement', pdfPercent(summary.period_change), marginX, 91, metricW, summary.period_change >= 0 ? [4, 120, 87] : accentColor);
        addMetric('Best Day', summary.best_day ? formatDisplayDate(summary.best_day.tanggal) : '-', marginX + metricW + metricGap, 91, metricW, [37, 99, 235]);
        addMetric('ATP', pdfNumber(summary.atp, 2), marginX + ((metricW + metricGap) * 2), 91, metricW, [141, 124, 255]);
        addMetric('Occupancy', pdfPercent(summary.occupancy_rate), marginX + ((metricW + metricGap) * 3), 91, metricW, [234, 88, 12]);
        addMovementInsightPanel(115);

        doc.addPage('a4', 'landscape');
        addHeader('Daily Trend Chart');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Daily Trend Chart', marginX, 35);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor.apply(doc, mutedColor);
        doc.text('Pergerakan Total Production House, Gross, dan Penonton dari hari ke hari.', marginX, 41);
        addChartPanel(marginX + 18, 51, usableW - 36, 112);

        doc.addPage('a4', 'landscape');
        var tableRows = daily.map(function (row, index) {
            return [
                index + 1,
                formatDisplayDate(row.tanggal),
                pdfNumber(row.audience, 0),
                pdfNumber(row.seats_available, 0),
                pdfNumber(row.gross, 2),
                pdfNumber(row.tax, 2),
                pdfNumber(row.net, 2),
                pdfNumber(row.total_ph, 2),
                pdfNumber(row.atp, 2),
                pdfPercent(row.occupancy_rate),
                pdfPercent(row.effective_tax_rate),
                pdfPercent(row.total_ph_change),
                pdfPercent(row.gross_change),
                pdfPercent(row.audience_change)
            ];
        });
        var compactTableStyle = tableRows.length <= 10
            ? { fontSize: 7.2, cellPadding: 1.7 }
            : (tableRows.length <= 20
                ? { fontSize: 6.6, cellPadding: 1.15 }
                : { fontSize: 6.2, cellPadding: 1.1 });

        doc.autoTable({
            startY: 42,
            margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
            head: [[
                'No', 'Tanggal', 'Penonton', 'Kursi', 'Gross', 'Tax', 'Net', 'Total Production House',
                'ATP', 'Occupancy', 'Effective Tax', 'Production House Growth', 'Gross Growth', 'Audience Growth'
            ]],
            body: tableRows,
            theme: 'grid',
            showHead: 'everyPage',
            pageBreak: 'auto',
            rowPageBreak: 'avoid',
            styles: {
                font: 'helvetica',
                fontSize: compactTableStyle.fontSize,
                cellPadding: compactTableStyle.cellPadding,
                textColor: textColor,
                overflow: 'linebreak',
                valign: 'middle'
            },
            headStyles: {
                fillColor: brandColor,
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                halign: 'center'
            },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 8 },
                1: { halign: 'center', cellWidth: 18 },
                2: { halign: 'right', cellWidth: 16 },
                3: { halign: 'right', cellWidth: 14 },
                4: { halign: 'right', cellWidth: 25 },
                5: { halign: 'right', cellWidth: 21 },
                6: { halign: 'right', cellWidth: 25 },
                7: { halign: 'right', cellWidth: 25 },
                8: { halign: 'right', cellWidth: 19 },
                9: { halign: 'right', cellWidth: 17 },
                10: { halign: 'right', cellWidth: 18 },
                11: { halign: 'right', cellWidth: 17 },
                12: { halign: 'right', cellWidth: 17 },
                13: { halign: 'right', cellWidth: 18 }
            },
            didDrawPage: function (tableData) {
                addHeader('Detail Data - Trend Analysis');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(11);
                doc.setTextColor.apply(doc, textColor);
                doc.text(
                    tableData.pageNumber === 1
                        ? 'Daily Movement Table'
                        : 'Daily Movement Table (Lanjutan)',
                    marginX,
                    35
                );
            }
        });

        addFooter();

        var filmName = selectedText('#nama_film')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        doc.save('report-trend-analysis-' + (filmName || 'semua-film') + '.pdf');
    }

    function reportPeriod() {
        var start = $('#tgl_mulai').val();
        var end = $('#tgl_akhir').val();

        if (start && end) {
            return formatDisplayDate(start) + ' s/d ' + formatDisplayDate(end);
        }

        return 'Semua periode';
    }

    function formatDisplayDate(value) {
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);

        if (!match) {
            return value || '-';
        }

        return match[3] + '-' + match[2] + '-' + match[1];
    }

    function formatDatesInText(value) {
        return String(value || '').replace(/\b(\d{4})-(\d{2})-(\d{2})\b/g, function (date, year, month, day) {
            return day + '-' + month + '-' + year;
        });
    }

    function formatNumber(value) {
        value = Number(value || 0);
        return value.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function formatCurrency(value) {
        value = Number(value || 0);
        return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatPercent(value) {
        value = Number(value || 0);
        return value.toFixed(2) + '%';
    }

    function formatNullablePercent(value) {
        if (value === null || typeof value === 'undefined') {
            return '-';
        }

        return formatPercent(value);
    }

    function compactNumber(value) {
        value = Number(value || 0);

        if (Math.abs(value) >= 1000000000) {
            return (value / 1000000000).toFixed(1) + 'B';
        }

        if (Math.abs(value) >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
        }

        if (Math.abs(value) >= 1000) {
            return (value / 1000).toFixed(1) + 'K';
        }

        return value;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endsection
