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

    .insight-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
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

        .insight-actions {
            justify-content: flex-start;
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
            <div class="form-group col-lg-1 mb-3 filter-search-column">
                <button type="button" id="search-btn" class="btn btn-primary w-100 filter-search-btn" title="Tampilkan Finance Insight" aria-label="Tampilkan Finance Insight">
                    <i class="fal fa-search"></i>
                </button>
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
    <img id="finance-report-logo" src="{{ asset('img/sinemaku.png') }}" alt="Sinemaku Pictures" style="display:none">
    <div class="insight-title">
        <div>
            <h3>Executive Summary</h3>
            <span id="insight-period">Semua periode</span>
        </div>
        <div class="insight-actions">
            <button type="button" id="download-finance-pdf" class="btn btn-danger btn-sm" style="display:none">
                <i class="fal fa-file-pdf mr-1"></i> Download PDF
            </button>
            <div class="insight-badge" id="insight-status">Ready</div>
        </div>
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
            <small>Bioskop / Kota / Provinsi</small>
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
            <small>Top Province</small>
            <strong id="top-province">-</strong>
            <span id="top-province-detail">-</span>
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
        <h4>Top 5 Provinsi by Total PH</h4>
        <div class="table-responsive">
            <table class="table table-hover table-striped insight-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Provinsi</th>
                        <th>Kota</th>
                        <th>Bioskop</th>
                        <th>Penonton</th>
                        <th>Gross</th>
                        <th>ATP</th>
                        <th>Occupancy</th>
                        <th>Total PH</th>
                    </tr>
                </thead>
                <tbody id="province-leaderboard-body"></tbody>
            </table>
        </div>
    </div>

    <div class="insight-card mt-3">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
    var latestFinanceInsightData = null;

    $(document).ready(function(){
        $('#nama_film, #bioskop_kategori, #kota, #nama_bioskop, #type_tiket').select2({ width: '100%' });

        $('#search-btn').on('click', function () {
            loadInsight();
        });

        $('#download-finance-pdf').on('click', function () {
            downloadFinanceInsightPdf();
        });
    });

    function loadInsight() {
        $('#insight-panel').hide();
        $('#insight-empty').hide();
        $('#insight-loading').show();
        $('#download-finance-pdf').hide();
        latestFinanceInsightData = null;

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
                latestFinanceInsightData = response;
                $('#insight-panel').show();
                $('#download-finance-pdf').show();
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
        $('#kpi-coverage').text(formatNumber(summary.cinema_count) + ' / ' + formatNumber(summary.city_count) + ' / ' + formatNumber(summary.province_count));
        $('#kpi-audit').text(formatNumber(summary.audit_issues));

        renderTopCard(data.top_category, '#top-category', '#top-category-detail');
        renderTopCard(data.top_cinema, '#top-cinema', '#top-cinema-detail');
        renderTopCard(data.top_city, '#top-city', '#top-city-detail');
        renderTopCard(data.top_province, '#top-province', '#top-province-detail');
        renderOccupancyCard(data.top_occupancy);

        $('#risk-capacity').text(formatNumber(audit.capacity_issues || 0));
        $('#risk-tax').text(formatNumber(audit.tax_issues || 0));
        $('#risk-occupancy').text(formatNumber(audit.occupancy_issues || 0));
        $('#risk-gross').text(formatNumber(audit.gross_issues || 0));

        var notes = (data.notes || []).map(function (note) {
            return '<li>' + escapeHtml(note) + '</li>';
        }).join('');
        $('#insight-notes').html(notes || '<li>Tidak ada catatan khusus.</li>');

        var provinceRows = (data.province_leaderboard || []).map(function (row, index) {
            return [
                '<tr>',
                    '<td>' + (index + 1) + '</td>',
                    '<td>' + escapeHtml(row.provinsi || '-') + '</td>',
                    '<td class="text-right">' + formatNumber(row.city_count) + '</td>',
                    '<td class="text-right">' + formatNumber(row.cinema_count) + '</td>',
                    '<td class="text-right">' + formatNumber(row.audience) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.gross) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.atp) + '</td>',
                    '<td class="text-right">' + formatPercent(row.occupancy_rate) + '</td>',
                    '<td class="text-right">' + formatCurrency(row.total_ph) + '</td>',
                '</tr>'
            ].join('');
        }).join('');

        $('#province-leaderboard-body').html(provinceRows || '<tr><td colspan="9" class="text-center">Tidak ada data.</td></tr>');

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

    function downloadFinanceInsightPdf() {
        if (!latestFinanceInsightData || !latestFinanceInsightData.summary) {
            alert('Data Finance Insight belum tersedia. Silakan jalankan filter terlebih dahulu.');
            return;
        }

        var JsPdf = window.jspdf && window.jspdf.jsPDF;
        if (!JsPdf) {
            alert('Library PDF belum berhasil dimuat. Silakan refresh halaman dan coba kembali.');
            return;
        }

        var doc = new JsPdf('l', 'mm', 'a4');
        var pageW = doc.internal.pageSize.getWidth();
        var pageH = doc.internal.pageSize.getHeight();
        var marginX = 14;
        var usableW = pageW - (marginX * 2);
        var brandColor = [47, 69, 88];
        var accentColor = [153, 27, 27];
        var textColor = [31, 41, 55];
        var mutedColor = [107, 114, 128];
        var borderColor = [229, 231, 235];
        var data = latestFinanceInsightData;
        var summary = data.summary || {};
        var audit = data.audit || {};
        var generatedAt = new Date().toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        function selectedText(selector) {
            var value = $(selector).val();
            return value ? $(selector + ' option:selected').text() : '-';
        }

        function pdfNumber(value, decimals) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: decimals || 0,
                maximumFractionDigits: decimals || 0
            });
        }

        function pdfPercent(value) {
            return pdfNumber(value, 2) + '%';
        }

        function addLogo(x, y, size) {
            var logo = document.getElementById('finance-report-logo');
            if (logo && logo.complete && logo.naturalWidth) {
                doc.addImage(logo, 'PNG', x, y, size, size, undefined, 'FAST');
            }
        }

        function addHeader(sectionName) {
            addLogo(marginX, 8, 14);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(13);
            doc.setTextColor.apply(doc, textColor);
            doc.text('SINEMAKU PICTURES', marginX + 18, 13);
            doc.setFontSize(8.5);
            doc.setTextColor.apply(doc, accentColor);
            doc.text('Audience Analytics Dashboard', marginX + 18, 18);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7.5);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(sectionName, pageW - marginX, 13, { align: 'right' });
            doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });
            doc.setDrawColor.apply(doc, accentColor);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
            doc.setDrawColor.apply(doc, [209, 213, 219]);
            doc.setLineWidth(0.15);
            doc.line(marginX, 27, pageW - marginX, 27);
        }

        function addFooter() {
            var pages = doc.internal.getNumberOfPages();
            for (var page = 1; page <= pages; page++) {
                doc.setPage(page);
                doc.setDrawColor.apply(doc, borderColor);
                doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                doc.text('Halaman ' + page + ' dari ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
            }
        }

        function addFilterBox() {
            var filters = [
                ['Nama Film', selectedText('#nama_film')],
                ['Periode', reportPeriod()],
                ['Kategori Bioskop', selectedText('#bioskop_kategori')],
                ['Kota', selectedText('#kota')],
                ['Nama Bioskop', selectedText('#nama_bioskop')],
                ['Tipe Tiket', selectedText('#type_tiket')]
            ];
            var y = 46;
            var columnW = usableW / 3;
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(marginX, y, usableW, 34, 2, 2, 'FD');
            filters.forEach(function (filter, index) {
                var x = marginX + ((index % 3) * columnW) + 5;
                var itemY = y + 7 + (Math.floor(index / 3) * 15);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(6.5);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text(filter[0].toUpperCase(), x, itemY);
                doc.setFontSize(8.3);
                doc.setTextColor.apply(doc, textColor);
                doc.text(String(filter[1] || '-'), x, itemY + 5, { maxWidth: columnW - 10 });
            });
        }

        function addMetric(label, value, x, y, width, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(x, y, width, 20, 2, 2, 'FD');
            doc.setFillColor.apply(doc, color);
            doc.roundedRect(x, y, 3, 20, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(10.5);
            doc.setTextColor.apply(doc, textColor);
            doc.text(String(value), x + 7, y + 9, { maxWidth: width - 10 });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.8);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(label, x + 7, y + 15.5);
        }

        function topCard(label, row, x, y, width, detailOverride) {
            var title = row && row.label ? row.label : '-';
            var detail = detailOverride || (row
                ? 'Total PH ' + pdfNumber(row.total_ph, 2) + ' | Penonton ' + pdfNumber(row.audience, 0)
                : '-');
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(x, y, width, 18, 2, 2, 'FD');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(6.3);
            doc.setTextColor.apply(doc, accentColor);
            doc.text(label.toUpperCase(), x + 4, y + 5);
            doc.setFontSize(8.2);
            doc.setTextColor.apply(doc, textColor);
            doc.text(String(title), x + 4, y + 10.5, { maxWidth: width - 8 });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.2);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(String(detail), x + 4, y + 15, { maxWidth: width - 8 });
        }

        addHeader('Executive Summary - Finance Insight');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Finance Insight Report', marginX, 35);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor.apply(doc, mutedColor);
        doc.text('Executive summary performa omset, kontribusi PH, coverage, dan risiko data.', marginX, 41);
        addFilterBox();

        var gap = 4;
        var metricW = (usableW - (gap * 3)) / 4;
        addMetric('Gross Box Office', pdfNumber(summary.gross, 2), marginX, 86, metricW, [98, 91, 214]);
        addMetric('Total PH', pdfNumber(summary.total_ph, 2), marginX + metricW + gap, 86, metricW, [4, 120, 87]);
        addMetric('Penonton', pdfNumber(summary.audience, 0), marginX + ((metricW + gap) * 2), 86, metricW, [37, 99, 235]);
        addMetric('ATP', pdfNumber(summary.atp, 2), marginX + ((metricW + gap) * 3), 86, metricW, [255, 159, 67]);
        addMetric('Occupancy', pdfPercent(summary.occupancy_rate), marginX, 108, metricW, [141, 124, 255]);
        addMetric('Effective Tax Rate', pdfPercent(summary.effective_tax_rate), marginX + metricW + gap, 108, metricW, [47, 69, 88]);
        addMetric('Bioskop / Kota / Provinsi', pdfNumber(summary.cinema_count, 0) + ' / ' + pdfNumber(summary.city_count, 0) + ' / ' + pdfNumber(summary.province_count, 0), marginX + ((metricW + gap) * 2), 108, metricW, [40, 199, 162]);
        addMetric('Audit Issues', pdfNumber(summary.audit_issues, 0), marginX + ((metricW + gap) * 3), 108, metricW, accentColor);

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Performance Highlights', marginX, 136);
        var topW = (usableW - (gap * 2)) / 3;
        topCard('Top Category', data.top_category, marginX, 141, topW);
        topCard('Top Cinema', data.top_cinema, marginX + topW + gap, 141, topW);
        topCard('Top City', data.top_city, marginX + ((topW + gap) * 2), 141, topW);
        topCard('Top Province', data.top_province, marginX, 161, topW);
        topCard(
            'Highest Occupancy',
            data.top_occupancy,
            marginX + topW + gap,
            161,
            topW,
            data.top_occupancy
                ? pdfPercent(data.top_occupancy.occupancy_rate) + ' | ' + (data.top_occupancy.kota || '-')
                : '-'
        );

        doc.addPage('a4', 'landscape');
        var notesY = 41;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        (data.notes && data.notes.length ? data.notes : ['Tidak ada catatan khusus.']).forEach(function (note, index) {
            var lines = doc.splitTextToSize((index + 1) + '. ' + note, usableW);
            doc.text(lines, marginX, notesY);
            notesY += (lines.length * 4) + 1;
        });
        notesY += 1;
        doc.setFont('helvetica', 'bold');
        doc.setTextColor.apply(doc, accentColor);
        doc.text('AUDIT RISK SUMMARY', marginX, notesY);
        notesY += 5;
        doc.setFont('helvetica', 'normal');
        doc.setTextColor.apply(doc, textColor);
        doc.text(
            'Kapasitas / Studio: ' + pdfNumber(audit.capacity_issues, 0)
                + '  |  Pajak Bioskop: ' + pdfNumber(audit.tax_issues, 0)
                + '  |  Occupancy > 100%: ' + pdfNumber(audit.occupancy_issues, 0)
                + '  |  Gross Variance: ' + pdfNumber(audit.gross_issues, 0),
            marginX,
            notesY
        );
        notesY += 7;

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9.5);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Top 5 Provinsi by Total PH', marginX, notesY);
        notesY += 5;

        var provinceRows = (data.province_leaderboard || []).map(function (row, index) {
            return [
                index + 1, row.provinsi || '-', pdfNumber(row.city_count, 0), pdfNumber(row.cinema_count, 0),
                pdfNumber(row.audience, 0), pdfNumber(row.gross, 2), pdfNumber(row.atp, 2),
                pdfPercent(row.occupancy_rate), pdfNumber(row.total_ph, 2)
            ];
        });

        doc.autoTable({
            startY: notesY,
            margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
            head: [['Rank', 'Provinsi', 'Kota', 'Bioskop', 'Penonton', 'Gross', 'ATP', 'Occupancy', 'Total PH']],
            body: provinceRows,
            theme: 'grid',
            showHead: 'everyPage',
            styles: { font: 'helvetica', fontSize: 7, cellPadding: 2, textColor: textColor },
            headStyles: { fillColor: brandColor, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 14 },
                1: { cellWidth: 'auto' },
                2: { halign: 'right', cellWidth: 22 },
                3: { halign: 'right', cellWidth: 22 },
                4: { halign: 'right', cellWidth: 30 },
                5: { halign: 'right', cellWidth: 42 },
                6: { halign: 'right', cellWidth: 35 },
                7: { halign: 'right', cellWidth: 28 },
                8: { halign: 'right', cellWidth: 42 }
            },
            didDrawPage: function (tableData) {
                addHeader('Detail Data - Finance Insight');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10.5);
                doc.setTextColor.apply(doc, textColor);
                doc.text(
                    tableData.pageNumber === 1
                        ? 'Management Notes & Audit Risk'
                        : 'Top 5 Provinsi by Total PH (Lanjutan)',
                    marginX,
                    35
                );
            }
        });

        doc.addPage('a4', 'landscape');
        var cinemaRows = (data.leaderboard || []).map(function (row, index) {
            return [
                index + 1, row.kota || '-', String(row.nama_bioskop || '-').toUpperCase(),
                pdfNumber(row.audience, 0), pdfNumber(row.gross, 2), pdfNumber(row.atp, 2),
                pdfPercent(row.occupancy_rate), pdfNumber(row.total_ph, 2)
            ];
        });

        doc.autoTable({
            startY: 42,
            margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
            head: [['Rank', 'Kota', 'Nama Bioskop', 'Penonton', 'Gross', 'ATP', 'Occupancy', 'Total PH']],
            body: cinemaRows,
            theme: 'grid',
            showHead: 'everyPage',
            styles: { font: 'helvetica', fontSize: 7, cellPadding: 2, textColor: textColor },
            headStyles: { fillColor: brandColor, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
            alternateRowStyles: { fillColor: [249, 250, 251] },
            columnStyles: {
                0: { halign: 'center', cellWidth: 14 },
                1: { cellWidth: 35 },
                2: { cellWidth: 'auto' },
                3: { halign: 'right', cellWidth: 30 },
                4: { halign: 'right', cellWidth: 42 },
                5: { halign: 'right', cellWidth: 35 },
                6: { halign: 'right', cellWidth: 28 },
                7: { halign: 'right', cellWidth: 42 }
            },
            didDrawPage: function () {
                addHeader('Detail Data - Finance Insight');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10.5);
                doc.setTextColor.apply(doc, textColor);
                doc.text('Top 5 Cinema by Total PH', marginX, 35);
            }
        });

        addFooter();
        var filmName = selectedText('#nama_film').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        doc.save('report-finance-insight-' + (filmName || 'semua-film') + '.pdf');
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
