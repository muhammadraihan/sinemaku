@extends('layouts.page')

@section('title', 'TOP 20 Kota dengan Penonton Terbanyak')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class="subheader-icon fal fa-chart-bar"></i>
        TOP 20 Kota <span class="fw-300">dengan Penonton Terbanyak</span>
        <small>Analisa kota dengan performa penonton tertinggi berdasarkan film, periode, dan kategori bioskop.</small>
    </h1>
</div>

<div class="modern-filter-card mb-4">
    <form id="filter-form" autocomplete="off">
        <div class="modern-card-title">
            <h3>Filter Grafik</h3>
            <span>Wajib diisi sebelum menampilkan data</span>
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
                <button type="button" id="search-btn" class="btn btn-primary w-100" title="Tampilkan grafik">
                    <i class="fal fa-search"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<div id="loading" style="display: none; text-align: center;" class="mb-4">
    <img src="{{asset('img/loading.gif')}}" alt="Loading...">
    <p class="mb-0 mt-2">Memuat data kota...</p>
</div>

<section id="chart-section" style="display:none">
    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>Grafik Penonton per Kota</h3>
                    <span id="chart-period-label">Top 20</span>
                </div>
                <div id="chart-container" class="chart-container">
                    <canvas id="topCitiesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-4">
            <div id="data-summary" class="modern-table-card h-100">
                <div class="modern-card-title">
                    <h3>Ranking Kota</h3>
                    <span>Export tersedia</span>
                </div>
                <div class="d-flex flex-wrap mb-3">
                    <button id="download-pdf" class="btn btn-danger mr-2 mb-2">
                        <i class="fal fa-file-pdf mr-1"></i> PDF
                    </button>
                    <button id="download-excel" class="btn btn-success mb-2">
                        <i class="fal fa-file-excel mr-1"></i> Excel
                    </button>
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
</section>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    const cityChartPalette = [
        'rgba(98, 91, 214, 0.84)',
        'rgba(40, 199, 162, 0.82)',
        'rgba(255, 200, 87, 0.88)',
        'rgba(255, 107, 125, 0.82)',
        'rgba(141, 124, 255, 0.82)',
        'rgba(45, 196, 255, 0.82)',
        'rgba(255, 159, 67, 0.82)',
        'rgba(74, 222, 128, 0.82)'
    ];

    let topCitiesChart = null;

    $(document).ready(function(){
        $('#nama_film').select2({ width: '100%' });
        $('#bioskop_kategori').select2({ width: '100%' });

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

            $('#chart-section').hide();
            $('#loading').show();

            setTimeout(function () {
                loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori);
            }, 200);
        });

        document.getElementById("download-pdf").addEventListener("click", function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF("p", "mm", "a4");
            const chartCanvas = document.getElementById("topCitiesChart");

            const namaFilm = $('#nama_film option:selected').text();
            const tanggalMulai = $('#tanggal_mulai').val();
            const tanggalAkhir = $('#tanggal_akhir').val();
            const kategoriBioskop = $('#bioskop_kategori option:selected').text();

            html2canvas(chartCanvas).then(function (canvas) {
                const imgData = canvas.toDataURL("image/png");
                const imgProps = doc.getImageProperties(imgData);
                const pdfWidth = doc.internal.pageSize.getWidth() - 30;
                const imgHeight = (imgProps.height * pdfWidth) / imgProps.width;

                doc.setFont("helvetica", "bold");
                doc.setFontSize(16);
                doc.setTextColor(33, 37, 41);
                doc.text("TOP 20 Kota Dengan Penonton Terbanyak", 15, 20);

                doc.setFont("helvetica", "normal");
                doc.setFontSize(11);
                doc.setTextColor(80, 80, 80);
                let y = 28;
                doc.text(`Nama Film : ${namaFilm}`, 15, y);
                y += 6;
                doc.text(`Periode : ${tanggalMulai} s.d. ${tanggalAkhir}`, 15, y);
                y += 6;
                doc.text(`Kategori Bioskop : ${kategoriBioskop}`, 15, y);
                y += 10;

                doc.addImage(imgData, 'PNG', 15, y, pdfWidth, imgHeight);

                doc.autoTable({
                    html: '#summary-table',
                    startY: y + imgHeight + 12,
                    margin: { left: 15, right: 15 },
                    styles: { font: 'helvetica', fontSize: 10, textColor: [33, 37, 41] },
                    headStyles: { fillColor: [98, 91, 214], textColor: [255, 255, 255], fontStyle: 'bold' },
                    alternateRowStyles: { fillColor: [245, 250, 255] },
                    tableLineColor: [220, 231, 243],
                    tableLineWidth: 0.1,
                    theme: 'grid'
                });

                doc.save("top20-kota-penonton.pdf");
            });
        });

        document.getElementById("download-excel").addEventListener("click", async function () {
            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet("Top Kota");

            const namaFilm = $('#nama_film option:selected').text();
            const tanggalMulai = $('#tanggal_mulai').val();
            const tanggalAkhir = $('#tanggal_akhir').val();
            const kategoriBioskop = $('#bioskop_kategori option:selected').text();

            worksheet.mergeCells('A1:C1');
            worksheet.getCell('A1').value = 'TOP 20 Kota Dengan Penonton Terbanyak';
            worksheet.getCell('A1').font = { size: 16, bold: true };

            worksheet.addRow([]);
            worksheet.addRow(['Nama Film', namaFilm]);
            worksheet.addRow(['Periode', tanggalMulai + ' s.d. ' + tanggalAkhir]);
            worksheet.addRow(['Kategori Bioskop', kategoriBioskop]);
            worksheet.addRow([]);

            let headerRow = worksheet.addRow(['Rank', 'Kota', 'Penonton']);
            headerRow.eachCell((cell) => {
                cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: '2D8CFF' } };
            });

            $('#summary-table tbody tr').each(function () {
                const rank = $(this).find('td:eq(0)').text();
                const kota = $(this).find('td:eq(1)').text();
                const penonton = $(this).find('td:eq(2)').text();

                worksheet.addRow([
                    rank,
                    kota,
                    parseInt(penonton.replace(/,/g, ''))
                ]);
            });

            worksheet.columns.forEach(column => {
                let maxLength = 0;
                column.eachCell({ includeEmpty: true }, function(cell) {
                    const columnLength = cell.value ? cell.value.toString().length : 10;
                    if (columnLength > maxLength) {
                        maxLength = columnLength;
                    }
                });
                column.width = maxLength + 5;
            });

            const canvas = document.getElementById("topCitiesChart");
            if (canvas) {
                const imageBase64 = canvas.toDataURL("image/png");
                const imageId = workbook.addImage({
                    base64: imageBase64,
                    extension: 'png',
                });
                worksheet.addImage(imageId, {
                    tl: { col: 4, row: 1 },
                    ext: { width: 600, height: 300 }
                });
            }

            const buffer = await workbook.xlsx.writeBuffer();
            saveAs(
                new Blob([buffer], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                }),
                "top20-kota-penonton.xlsx"
            );
        });
    });

    function loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori) {
        $.ajax({
            url: "{{ route('getTopCities') }}",
            method: "GET",
            data: {
                nama_film: nama_film,
                tgl_mulai: tgl_mulai,
                tgl_akhir: tgl_akhir,
                bioskop_kategori: bioskop_kategori
            },
            success: function (data) {
                $('#loading').hide();
                $('#chart-section').show();
                $('#chart-period-label').text(`${tgl_mulai} s.d. ${tgl_akhir}`);
                $('#summary-table tbody').empty();

                let labels = data.map(item => item.kota);
                let values = data.map(item => Number(item.jumlah || 0));

                labels.forEach(function(label, index) {
                    $('#summary-table tbody').append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${label}</td>
                            <td>${values[index].toLocaleString()}</td>
                        </tr>
                    `);
                });

                if (topCitiesChart) {
                    topCitiesChart.destroy();
                }

                topCitiesChart = new Chart(document.getElementById('topCitiesChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Penonton',
                            data: values,
                            backgroundColor: values.map((_, index) => cityChartPalette[index % cityChartPalette.length]),
                            borderColor: values.map((_, index) => cityChartPalette[index % cityChartPalette.length].replace('0.82', '1').replace('0.88', '1')),
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false,
                            maxBarThickness: 34
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(126, 149, 178, 0.14)' },
                                ticks: { color: '#7b8aa4' }
                            },
                            x: {
                                grid: { color: 'rgba(126, 149, 178, 0.08)' },
                                ticks: { color: '#7b8aa4' }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            },
            error: function (xhr, status, error) {
                $('#loading').hide();
                console.error("Error:", error);
                alert("Terjadi kesalahan saat mengambil data.");
            }
        });
    }
</script>
@endsection
