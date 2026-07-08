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
    }
</style>
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class="subheader-icon fal fa-chart-area"></i>
        Trend <span class="fw-300">Analysis</span>
        <small>Analisa pergerakan harian untuk Gross, Total PH, penonton, ATP, dan occupancy.</small>
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
            <div class="form-group col-lg-1 mb-3">
                <button type="button" id="search-btn" class="btn btn-primary w-100">Run</button>
            </div>
        </div>
    </form>
</div>

<div id="trend-loading" class="trend-loading mb-4">Memuat trend analysis...</div>
<div id="trend-empty" class="trend-empty mb-4">Tidak ada data trend pada filter ini.</div>

<section id="trend-panel" class="trend-panel">
    <div class="trend-title">
        <div>
            <h3>Daily Movement</h3>
            <span id="trend-period">Semua periode</span>
        </div>
        <div class="trend-badge" id="trend-status">Ready</div>
    </div>

    <div class="trend-kpi-grid">
        <div class="trend-kpi">
            <small>Total PH</small>
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
            <small>Rata-rata PH / Hari</small>
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
                        <th>Total PH</th>
                        <th>ATP</th>
                        <th>Occupancy</th>
                        <th>PH Growth</th>
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
<script>
    var trendChart = null;

    $(document).ready(function(){
        $('#nama_film, #bioskop_kategori, #kota, #nama_bioskop, #type_tiket').select2({ width: '100%' });

        $('#search-btn').on('click', function () {
            loadTrend();
        });
    });

    function loadTrend() {
        $('#trend-panel').hide();
        $('#trend-empty').hide();
        $('#trend-loading').show();

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
                $('#trend-panel').show();
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
        $('#kpi-best-day').text(summary.best_day ? summary.best_day.tanggal : '-');

        $('#kpi-movement').closest('.trend-kpi')
            .toggleClass('trend-kpi--positive', Number(summary.period_change || 0) >= 0)
            .toggleClass('trend-kpi--negative', Number(summary.period_change || 0) < 0);

        var notes = (data.notes || []).map(function (note) {
            return '<li>' + escapeHtml(note) + '</li>';
        }).join('');
        $('#trend-notes').html(notes || '<li>Tidak ada catatan movement.</li>');

        renderChart(data.daily || []);
        renderTable(data.daily || []);
    }

    function renderChart(rows) {
        var labels = rows.map(function (row) { return row.tanggal; });
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
                        label: 'Total PH',
                        data: totalPh,
                        borderColor: '#047857',
                        backgroundColor: 'rgba(4, 120, 87, 0.08)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'money'
                    },
                    {
                        label: 'Gross',
                        data: gross,
                        borderColor: '#2f4558',
                        backgroundColor: 'rgba(47, 69, 88, 0.07)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
                        yAxisID: 'money'
                    },
                    {
                        label: 'Penonton',
                        data: audience,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: false,
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
                    '<td>' + escapeHtml(row.tanggal) + '</td>',
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

    function reportPeriod() {
        var start = $('#tgl_mulai').val();
        var end = $('#tgl_akhir').val();

        if (start && end) {
            return start + ' s/d ' + end;
        }

        return 'Semua periode';
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
