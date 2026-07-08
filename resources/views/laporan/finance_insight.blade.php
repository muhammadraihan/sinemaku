@extends('layouts.page')

@section('title', 'Finance Insight')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<style>
    .insight-filter,
    .insight-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .insight-filter {
        padding: 18px;
    }

    .insight-panel {
        display: none;
        padding: 20px;
    }

    .insight-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .insight-title h3 {
        margin: 0;
        color: #111827;
        font-size: 20px;
        font-weight: 800;
    }

    .insight-title span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
    }

    .insight-badge {
        border-radius: 999px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 11px;
        font-weight: 800;
        padding: 7px 12px;
        white-space: nowrap;
    }

    .insight-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .insight-kpi {
        border: 1px solid #e5e7eb;
        border-left: 4px solid #2f4558;
        border-radius: 8px;
        background: #f9fafb;
        padding: 12px;
    }

    .insight-kpi small {
        display: block;
        color: #6b7280;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .insight-kpi strong {
        display: block;
        color: #111827;
        font-size: 18px;
        line-height: 1.25;
    }

    .insight-kpi--good {
        border-left-color: #047857;
        background: #ecfdf5;
    }

    .insight-kpi--risk {
        border-left-color: #991b1b;
        background: #fef2f2;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr);
        gap: 14px;
        margin-bottom: 14px;
    }

    .insight-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        background: #ffffff;
    }

    .insight-card h4 {
        margin: 0 0 10px;
        color: #111827;
        font-size: 15px;
        font-weight: 800;
    }

    .insight-list {
        padding-left: 18px;
        margin: 0;
        color: #374151;
        line-height: 1.6;
    }

    .performer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .performer-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
        padding: 13px;
    }

    .performer-card small {
        display: block;
        color: #6b7280;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11px;
        margin-bottom: 6px;
    }

    .performer-card strong {
        display: block;
        color: #111827;
        font-size: 15px;
        margin-bottom: 4px;
    }

    .performer-card span {
        color: #6b7280;
        font-size: 12px;
    }

    .risk-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 0;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 13px;
    }

    .risk-row:last-child {
        border-bottom: 0;
    }

    .risk-row strong {
        color: #111827;
    }

    .insight-table {
        margin-bottom: 0;
    }

    .insight-empty,
    .insight-loading {
        display: none;
        text-align: center;
        padding: 26px;
        color: #6b7280;
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        background: #f9fafb;
    }

    @media (max-width: 992px) {
        .insight-grid {
            grid-template-columns: 1fr;
        }

        .insight-title {
            display: block;
        }

        .insight-badge {
            display: inline-block;
            margin-top: 10px;
        }
    }
</style>
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class="subheader-icon fal fa-analytics"></i>
        Finance <span class="fw-300">Insight</span>
        <small>Executive summary untuk membaca performa omset, kontribusi PH, dan risiko data.</small>
    </h1>
</div>

<div class="insight-filter mb-4">
    <div class="insight-title mb-3">
        <div>
            <h3>Filter Insight</h3>
            <span>Pakai filter yang sama dengan laporan omset untuk menghasilkan ringkasan management.</span>
        </div>
        <div class="insight-badge">Executive View</div>
    </div>
    <form id="insight-form" autocomplete="off">
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

<div id="insight-loading" class="insight-loading mb-4">
    Memuat executive summary...
</div>

<div id="insight-empty" class="insight-empty mb-4">
    Tidak ada data pada filter ini.
</div>

<section id="insight-panel" class="insight-panel">
    <div class="insight-title">
        <div>
            <h3>Executive Summary</h3>
            <span id="insight-period">Semua periode</span>
        </div>
        <div class="insight-badge" id="insight-status">Ready</div>
    </div>

    <div class="insight-kpi-grid">
        <div class="insight-kpi">
            <small>Gross Box Office</small>
            <strong id="kpi-gross">0.00</strong>
        </div>
        <div class="insight-kpi insight-kpi--good">
            <small>Total PH</small>
            <strong id="kpi-total-ph">0.00</strong>
        </div>
        <div class="insight-kpi">
            <small>Penonton</small>
            <strong id="kpi-audience">0</strong>
        </div>
        <div class="insight-kpi">
            <small>ATP</small>
            <strong id="kpi-atp">0.00</strong>
        </div>
        <div class="insight-kpi">
            <small>Occupancy</small>
            <strong id="kpi-occupancy">0.00%</strong>
        </div>
        <div class="insight-kpi">
            <small>Effective Tax Rate</small>
            <strong id="kpi-tax-rate">0.00%</strong>
        </div>
        <div class="insight-kpi">
            <small>Bioskop / Kota</small>
            <strong id="kpi-coverage">0 / 0</strong>
        </div>
        <div class="insight-kpi insight-kpi--risk">
            <small>Audit Issues</small>
            <strong id="kpi-audit">0</strong>
        </div>
    </div>

    <div class="performer-grid">
        <div class="performer-card">
            <small>Top Category</small>
            <strong id="top-category">-</strong>
            <span id="top-category-detail">-</span>
        </div>
        <div class="performer-card">
            <small>Top Cinema</small>
            <strong id="top-cinema">-</strong>
            <span id="top-cinema-detail">-</span>
        </div>
        <div class="performer-card">
            <small>Top City</small>
            <strong id="top-city">-</strong>
            <span id="top-city-detail">-</span>
        </div>
        <div class="performer-card">
            <small>Highest Occupancy</small>
            <strong id="top-occupancy">-</strong>
            <span id="top-occupancy-detail">-</span>
        </div>
    </div>

    <div class="insight-grid">
        <div class="insight-card">
            <h4>Management Notes</h4>
            <ul id="insight-notes" class="insight-list"></ul>
        </div>
        <div class="insight-card">
            <h4>Audit Risk Summary</h4>
            <div class="risk-row"><span>Kapasitas / Studio</span><strong id="risk-capacity">0</strong></div>
            <div class="risk-row"><span>Pajak Bioskop</span><strong id="risk-tax">0</strong></div>
            <div class="risk-row"><span>Occupancy > 100%</span><strong id="risk-occupancy">0</strong></div>
            <div class="risk-row"><span>Gross Variance</span><strong id="risk-gross">0</strong></div>
        </div>
    </div>

    <div class="insight-card">
        <h4>Top 5 Cinema by Total PH</h4>
        <div class="table-responsive">
            <table class="table table-hover table-striped insight-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Kota</th>
                        <th>Nama Bioskop</th>
                        <th>Penonton</th>
                        <th>Gross</th>
                        <th>ATP</th>
                        <th>Occupancy</th>
                        <th>Total PH</th>
                    </tr>
                </thead>
                <tbody id="leaderboard-body"></tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script>
    $(document).ready(function(){
        $('#nama_film, #bioskop_kategori, #kota, #nama_bioskop, #type_tiket').select2({ width: '100%' });

        $('#search-btn').on('click', function () {
            loadInsight();
        });
    });

    function loadInsight() {
        $('#insight-panel').hide();
        $('#insight-empty').hide();
        $('#insight-loading').show();

        $.ajax({
            url: '{{ route('finance-insight.data') }}',
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
                $('#insight-loading').hide();

                if (!response.summary || !response.summary.row_count) {
                    $('#insight-empty').show();
                    return;
                }

                renderInsight(response);
                $('#insight-panel').show();
            },
            error: function () {
                $('#insight-loading').hide();
                alert('Gagal memuat finance insight.');
            }
        });
    }

    function renderInsight(data) {
        var summary = data.summary || {};
        var audit = data.audit || {};

        $('#insight-period').text(reportPeriod());
        $('#insight-status').text(summary.audit_issues > 0 ? 'Review Needed' : 'Clean Summary');

        $('#kpi-gross').text(formatCurrency(summary.gross));
        $('#kpi-total-ph').text(formatCurrency(summary.total_ph));
        $('#kpi-audience').text(formatNumber(summary.audience));
        $('#kpi-atp').text(formatCurrency(summary.atp));
        $('#kpi-occupancy').text(formatPercent(summary.occupancy_rate));
        $('#kpi-tax-rate').text(formatPercent(summary.effective_tax_rate));
        $('#kpi-coverage').text(formatNumber(summary.cinema_count) + ' / ' + formatNumber(summary.city_count));
        $('#kpi-audit').text(formatNumber(summary.audit_issues));

        renderTopCard(data.top_category, '#top-category', '#top-category-detail');
        renderTopCard(data.top_cinema, '#top-cinema', '#top-cinema-detail');
        renderTopCard(data.top_city, '#top-city', '#top-city-detail');
        renderOccupancyCard(data.top_occupancy);

        $('#risk-capacity').text(formatNumber(audit.capacity_issues || 0));
        $('#risk-tax').text(formatNumber(audit.tax_issues || 0));
        $('#risk-occupancy').text(formatNumber(audit.occupancy_issues || 0));
        $('#risk-gross').text(formatNumber(audit.gross_issues || 0));

        var notes = (data.notes || []).map(function (note) {
            return '<li>' + escapeHtml(note) + '</li>';
        }).join('');
        $('#insight-notes').html(notes || '<li>Tidak ada catatan khusus.</li>');

        var rows = (data.leaderboard || []).map(function (row, index) {
            return [
                '<tr>',
                    '<td>' + (index + 1) + '</td>',
                    '<td>' + escapeHtml(row.kota || '-') + '</td>',
                    '<td>' + escapeHtml(row.nama_bioskop || '-') + '</td>',
                    '<td class="text-right">' + formatNumber(row.audience) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.gross) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.atp) + '</td>',
                    '<td class="text-right">' + formatPercent(row.occupancy_rate) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.total_ph) + '</td>',
                '</tr>'
            ].join('');
        }).join('');

        $('#leaderboard-body').html(rows || '<tr><td colspan="8" class="text-center">Tidak ada data.</td></tr>');
    }

    function renderTopCard(row, titleSelector, detailSelector) {
        if (!row) {
            $(titleSelector).text('-');
            $(detailSelector).text('-');
            return;
        }

        $(titleSelector).text(row.label || '-');
        $(detailSelector).text('Total PH ' + formatCurrency(row.total_ph) + ' | Penonton ' + formatNumber(row.audience));
    }

    function renderOccupancyCard(row) {
        if (!row) {
            $('#top-occupancy').text('-');
            $('#top-occupancy-detail').text('-');
            return;
        }

        $('#top-occupancy').text(row.label || '-');
        $('#top-occupancy-detail').text(formatPercent(row.occupancy_rate) + ' | ' + (row.kota || '-'));
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
