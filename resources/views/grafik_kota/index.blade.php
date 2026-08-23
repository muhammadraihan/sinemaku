@extends('layouts.page')

@section('title', 'TOP 20 Kota dengan Penonton Terbanyak')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<style>
    #chart-container {
        min-height: 460px;
        transition: height .2s ease;
    }

    .city-ranking-table-wrap {
        width: 100%;
        max-width: 1050px;
    }

    #summary-table {
        table-layout: fixed;
    }

    #summary-table th,
    #summary-table td {
        text-align: left !important;
        vertical-align: middle;
    }

    #summary-table th:nth-child(1),
    #summary-table td:nth-child(1) {
        width: 110px;
    }

    #summary-table th:nth-child(3),
    #summary-table td:nth-child(3) {
        width: 220px;
    }

    @media (max-width: 768px) {
        #summary-table th:nth-child(1),
        #summary-table td:nth-child(1) {
            width: 72px;
        }

        #summary-table th:nth-child(3),
        #summary-table td:nth-child(3) {
            width: 140px;
        }
    }
</style>
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
            <div class="form-group col-lg-1 mb-3 filter-search-column">
                <button type="button" id="search-btn" class="btn btn-primary w-100 filter-search-btn" title="Tampilkan grafik" aria-label="Tampilkan grafik">
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
    <img id="city-report-logo" src="{{ asset('img/sinemaku.png') }}" alt="Sinemaku Pictures" style="display:none">
    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="modern-chart-card h-100">
                <div class="modern-card-title">
                    <h3>Grafik Penonton per Kota</h3>
                    <span id="chart-period-label">Semakin panjang bar, semakin banyak penonton</span>
                </div>
                <div id="chart-container" class="chart-container">
                    <canvas id="topCitiesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-12 mb-4">
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
                <div class="table-responsive city-ranking-table-wrap">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    const cityRankColors = [
        '#26225e',
        '#4b4697',
        '#716bd3'
    ];

    const cityValueLabelsPlugin = {
        id: 'cityValueLabels',
        afterDatasetsDraw: function (chart) {
            const context = chart.ctx;
            const values = chart.data.datasets[0].data || [];
            const bars = chart.getDatasetMeta(0).data || [];

            context.save();
            context.font = '700 12px Arial';
            context.textBaseline = 'middle';

            bars.forEach(function (bar, index) {
                const label = Number(values[index] || 0).toLocaleString('id-ID');
                const labelWidth = context.measureText(label).width;
                const outsideX = bar.x + 8;
                const canFitOutside = outsideX + labelWidth <= chart.chartArea.right;

                context.textAlign = canFitOutside ? 'left' : 'right';
                context.fillStyle = canFitOutside ? '#374151' : '#ffffff';
                context.fillText(label, canFitOutside ? outsideX : bar.x - 7, bar.y);
            });

            context.restore();
        }
    };

    let topCitiesChart = null;
    let latestTopCitiesData = [];

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
            latestTopCitiesData = [];

            setTimeout(function () {
                loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori);
            }, 200);
        });

        document.getElementById("download-pdf").addEventListener("click", function () {
            if (!topCitiesChart || !latestTopCitiesData.length) {
                alert('Data Grafik Kota belum tersedia. Silakan jalankan filter terlebih dahulu.');
                return;
            }

            const JsPdf = window.jspdf && window.jspdf.jsPDF;
            if (!JsPdf) {
                alert('Library PDF belum berhasil dimuat. Silakan refresh halaman dan coba kembali.');
                return;
            }

            const doc = new JsPdf('l', 'mm', 'a4');
            const pageW = doc.internal.pageSize.getWidth();
            const pageH = doc.internal.pageSize.getHeight();
            const marginX = 14;
            const usableW = pageW - (marginX * 2);
            const brandColor = [47, 69, 88];
            const accentColor = [153, 27, 27];
            const textColor = [31, 41, 55];
            const mutedColor = [107, 114, 128];
            const borderColor = [229, 231, 235];
            const namaFilm = $('#nama_film option:selected').text() || '-';
            const tanggalMulai = formatDisplayDate($('#tanggal_mulai').val());
            const tanggalAkhir = formatDisplayDate($('#tanggal_akhir').val());
            const kategoriBioskop = $('#bioskop_kategori option:selected').text() || '-';
            const totalAudience = latestTopCitiesData.reduce(function (total, item) {
                return total + Number(item.jumlah || 0);
            }, 0);
            const highestCity = latestTopCitiesData[0] || null;
            const averageAudience = latestTopCitiesData.length
                ? totalAudience / latestTopCitiesData.length
                : 0;
            const generatedDate = new Date();
            const padDatePart = function (value) {
                return String(value).padStart(2, '0');
            };
            const generatedAt = padDatePart(generatedDate.getDate())
                + '-' + padDatePart(generatedDate.getMonth() + 1)
                + '-' + generatedDate.getFullYear()
                + ' ' + padDatePart(generatedDate.getHours())
                + ':' + padDatePart(generatedDate.getMinutes());

            function pdfNumber(value) {
                return Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            }

            function addLogo(x, y, size) {
                const logo = document.getElementById('city-report-logo');
                if (logo && logo.complete && logo.naturalWidth) {
                    doc.addImage(logo, 'PNG', x, y, size, size, undefined, 'FAST');
                }
            }

            function addHeader(sectionName) {
                addLogo(marginX, 8, 14);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(15);
                doc.setTextColor(...textColor);
                doc.text('SINEMAKU PICTURES', marginX + 18, 13);
                doc.setFontSize(9.5);
                doc.setTextColor(...accentColor);
                doc.text('Audience Analytics Dashboard', marginX + 18, 18);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8.5);
                doc.setTextColor(...mutedColor);
                doc.text(sectionName, pageW - marginX, 13, { align: 'right' });
                doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });
                doc.setDrawColor(...accentColor);
                doc.setLineWidth(0.45);
                doc.line(marginX, 25, pageW - marginX, 25);
                doc.setDrawColor(209, 213, 219);
                doc.setLineWidth(0.15);
                doc.line(marginX, 27, pageW - marginX, 27);
            }

            function addFooter() {
                const pages = doc.internal.getNumberOfPages();
                for (let page = 1; page <= pages; page++) {
                    doc.setPage(page);
                    doc.setDrawColor(...borderColor);
                    doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    doc.setTextColor(...mutedColor);
                    doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                    doc.text('Halaman ' + page + ' dari ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
                }
            }

            function addFilterBox() {
                const y = 47;
                const filters = [
                    ['Nama Film', namaFilm],
                    ['Periode', tanggalMulai + ' s.d. ' + tanggalAkhir],
                    ['Kategori Bioskop', kategoriBioskop]
                ];
                const columnW = usableW / filters.length;
                doc.setFillColor(249, 250, 251);
                doc.setDrawColor(...borderColor);
                doc.roundedRect(marginX, y, usableW, 22, 2, 2, 'FD');
                filters.forEach(function (filter, index) {
                    const x = marginX + (columnW * index) + 5;
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(7.5);
                    doc.setTextColor(...mutedColor);
                    doc.text(filter[0].toUpperCase(), x, y + 8);
                    doc.setFontSize(9);
                    doc.setTextColor(...textColor);
                    doc.text(String(filter[1]), x, y + 15, { maxWidth: columnW - 10 });
                });
            }

            function addMetric(label, value, x, y, width, color) {
                doc.setFillColor(255, 255, 255);
                doc.setDrawColor(...borderColor);
                doc.roundedRect(x, y, width, 20, 2, 2, 'FD');
                doc.setFillColor(...color);
                doc.roundedRect(x, y, 3, 20, 1.5, 1.5, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(11);
                doc.setTextColor(...textColor);
                doc.text(String(value), x + 7, y + 9, { maxWidth: width - 10 });
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7.5);
                doc.setTextColor(...mutedColor);
                doc.text(label, x + 7, y + 15.5);
            }

            function addChartPanel() {
                const imageData = topCitiesChart.toBase64Image();
                const image = doc.getImageProperties(imageData);
                const x = marginX + 18;
                const y = 48;
                const width = usableW - 36;
                const height = 133;
                doc.setFillColor(255, 255, 255);
                doc.setDrawColor(...borderColor);
                doc.roundedRect(x, y, width, height, 2, 2, 'FD');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(9.5);
                doc.setTextColor(...textColor);
                doc.text('Grafik Penonton per Kota', x + 4, y + 7);
                const ratio = Math.min((width - 10) / image.width, (height - 14) / image.height);
                const imageW = image.width * ratio;
                const imageH = image.height * ratio;
                doc.addImage(
                    imageData,
                    'PNG',
                    x + ((width - imageW) / 2),
                    y + 11 + ((height - 13 - imageH) / 2),
                    imageW,
                    imageH,
                    undefined,
                    'FAST'
                );
            }

            addHeader('Executive Summary - Grafik Kota');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor(...textColor);
            doc.text('TOP 20 Kota dengan Penonton Terbanyak', marginX, 35);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(...mutedColor);
            doc.text('Analisa kota dengan performa penonton tertinggi berdasarkan filter aktif.', marginX, 41);
            addFilterBox();

            const metricGap = 4;
            const metricW = (usableW - (metricGap * 3)) / 4;
            addMetric('Kota Ditampilkan', pdfNumber(latestTopCitiesData.length), marginX, 75, metricW, [98, 91, 214]);
            addMetric('Total Penonton', pdfNumber(totalAudience), marginX + metricW + metricGap, 75, metricW, [40, 199, 162]);
            addMetric('Kota Tertinggi', highestCity ? String(highestCity.kota) : '-', marginX + ((metricW + metricGap) * 2), 75, metricW, accentColor);
            addMetric('Rata-rata per Kota', pdfNumber(averageAudience), marginX + ((metricW + metricGap) * 3), 75, metricW, [255, 159, 67]);

            doc.addPage('a4', 'landscape');
            addHeader('Chart Detail - Grafik Kota');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor(...textColor);
            doc.text('Grafik Penonton per Kota', marginX, 35);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(...mutedColor);
            doc.text('Urutan menurun; semakin panjang bar, semakin banyak jumlah penonton.', marginX, 41);
            addChartPanel();

            const detailRows = latestTopCitiesData.map(function (item, index) {
                const value = Number(item.jumlah || 0);
                const contribution = totalAudience > 0
                    ? ((value / totalAudience) * 100).toFixed(2) + '%'
                    : '0.00%';
                return [index + 1, String(item.kota || '-'), pdfNumber(value), contribution];
            });

            doc.addPage('a4', 'landscape');
            doc.autoTable({
                startY: 42,
                margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
                head: [['Rank', 'Kota', 'Jumlah Penonton', 'Kontribusi']],
                body: detailRows,
                theme: 'grid',
                showHead: 'everyPage',
                styles: {
                    font: 'helvetica',
                    fontSize: 8,
                    cellPadding: 2.5,
                    textColor: textColor,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: brandColor,
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                alternateRowStyles: { fillColor: [249, 250, 251] },
                columnStyles: {
                    0: { halign: 'left', cellWidth: 22 },
                    1: { cellWidth: 'auto' },
                    2: { halign: 'left', cellWidth: 48 },
                    3: { halign: 'left', cellWidth: 38 }
                },
                didDrawPage: function () {
                    addHeader('Detail Data - Grafik Kota');
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(11);
                    doc.setTextColor(...textColor);
                    doc.text('Rincian Data Grafik: TOP 20 Kota', marginX, 35);
                }
            });

            addFooter();
            doc.save('report-top-20-kota.pdf');
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
                latestTopCitiesData = data || [];
                $('#loading').hide();
                $('#chart-section').show();
                $('#chart-period-label').text(
                    `${formatDisplayDate(tgl_mulai)} s.d. ${formatDisplayDate(tgl_akhir)} · Semakin panjang bar, semakin banyak penonton`
                );
                $('#summary-table tbody').empty();

                let labels = data.map(item => String(item.kota || '-').toUpperCase());
                let values = data.map(item => Number(item.jumlah || 0));
                let chartHeight = Math.max(460, (labels.length * 30) + 90);

                $('#chart-container').css('height', chartHeight + 'px');

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
                    plugins: [cityValueLabelsPlugin],
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Jumlah Penonton',
                            data: values,
                            backgroundColor: values.map(function (_, index) {
                                return cityRankColors[index] || 'rgba(113, 107, 211, 0.58)';
                            }),
                            borderColor: values.map(function (_, index) {
                                return cityRankColors[index] || 'rgba(75, 70, 151, 0.72)';
                            }),
                            borderWidth: 0,
                            borderRadius: 7,
                            borderSkipped: false,
                            maxBarThickness: 24,
                            minBarLength: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 650
                        },
                        layout: {
                            padding: { top: 4, right: 18, bottom: 2, left: 4 }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grace: '14%',
                                border: { display: false },
                                grid: {
                                    color: 'rgba(126, 149, 178, 0.12)',
                                    drawTicks: false
                                },
                                ticks: {
                                    color: '#7b8aa4',
                                    padding: 8,
                                    callback: function (value) {
                                        return compactNumber(value);
                                    }
                                }
                            },
                            y: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: {
                                    color: '#374151',
                                    padding: 9,
                                    autoSkip: false,
                                    font: { size: 11, weight: '700' }
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#26225e',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    label: function (context) {
                                        return 'Jumlah Penonton: ' + Number(context.raw || 0).toLocaleString('id-ID');
                                    }
                                }
                            }
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

    function formatDisplayDate(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);

        if (!match) {
            return value || '-';
        }

        return match[3] + '-' + match[2] + '-' + match[1];
    }

    function compactNumber(value) {
        const number = Number(value || 0);

        if (Math.abs(number) >= 1000000) {
            return (number / 1000000).toFixed(1).replace('.0', '') + 'M';
        }

        if (Math.abs(number) >= 1000) {
            return (number / 1000).toFixed(1).replace('.0', '') + 'K';
        }

        return number.toLocaleString('id-ID');
    }
</script>
@endsection
