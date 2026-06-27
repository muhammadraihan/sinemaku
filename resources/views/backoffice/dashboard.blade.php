@extends('layouts.page')

@section('title', 'Dashbord Sinemaku')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
@endsection

@section('content')
<style>
  .chart-fixed { position: relative; height: 360px; } /* sesuaikan 300–420px */
</style>

<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-users'></i> <span class='fw-300'>Dashboard Sinemaku </span>
    </h1>
</div>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>
                    Dashboard Sinemaku  <span class="fw-300"></span>
                </h2>
                <div class="panel-toolbar">
                    {{-- <a class="nav-link active" href="{{route('pelaporan.create')}}"><i class="fal fa-plus-circle">
                        </i>
                        <span class="nav-link-text">Add New</span>
                    </a> --}}
                    <button class="btn btn-panel" data-action="panel-fullscreen" data-toggle="tooltip"
                        data-offset="0,10" data-original-title="Fullscreen"></button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form id="filter-form">
                        {!! Form::open(['route' => 'laporan.search','id'=>'forms','method' => 'GET','class' =>
                        'needs-validation','dropzone', 'forms','novalidate','enctype' => 'multipart/form-data']) !!}
                        <div class="row">
                            <!-- Dropdown Nama Film -->
                            <div class="form-group col-md-6 mb-3">
                                {{ Form::label('nama_film','Nama Film',['class' => 'required form-label'])}}
                                {!! Form::select('nama_film', $nama_film, '',
                                ['id'=>'nama_film','class'
                                => 'custom-select'.($errors->has('nama_film') ? 'is-invalid':'') ,'required'
                                => '', 'placeholder' => 'Pilih Nama Film ...'])!!}
                                @if ($errors->has('nama_film'))
                                <div class="invalid-feedback">{{ $errors->first('nama_film') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                {{ Form::label('tanggal_mulai','Tanggal Mulai',['class' => 'required form-label'])}}
                                <input type="date" id="tanggal_mulai" class="form-control">
                            </div>
            
                            <div class="col-md-3">
                                {{ Form::label('tanggal_akhir','Tanggal Akhir',['class' => 'required form-label'])}}
                                <input type="date" id="tanggal_akhir" class="form-control">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="form-group col-md-3 mb-3">
                                {{ Form::label('bioskop_kategori','Kategori Bioskop',['class' => 'required form-label'])}}
                                {!! Form::select('bioskop_kategori', $bioskop_kategori, '',
                                ['id'=>'bioskop_kategori','class'
                                => 'custom-select'.($errors->has('bioskop_kategori') ? 'is-invalid':'') ,'required'
                                => '', 'placeholder' => 'Pilih Kategori Bioskop ...'])!!}
                                @if ($errors->has('bioskop_kategori'))
                                <div class="invalid-feedback">{{ $errors->first('bioskop_kategori') }}</div>
                                @endif
                            </div>
                            <div class="form-group col-md-2 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="search-btn" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>
                    <img id="report-logo" src="{{ asset('img/sinemaku_full_logo.png') }}" alt="logo" style="display:none">
                    <button id="download-pdf" class="btn btn-danger mt-3">
                            <i class="fal fa-file-pdf"></i> Download PDF
                        </button>
                    <hr style="border: 1px dashed: color: black">

                    <section class="title-dashboard">
                        <div class="mt-4">
                            <h4 class="mb-2">Nama Film : {{ $last->nama_film }}</h4>
                        </div>
                        <div class="mt-4">
                            <h4 class="mb-2">Periode : {{ \Carbon\Carbon::parse($periode->tgl_awal)->format('d M Y') }} - {{ \Carbon\Carbon::parse($periode->tgl_akhir)->format('d M Y') }}</h4>
                        </div>
                        <div class="mt-4">
                            <h4 class="mb-2">Kategori Bioskop : ALL</h4>
                        </div>
                        <div class="mt-4">
                            <h4 class="mb-2">Total Penonton : {{ number_format($total_penonton->penonton) }}</h4>
                        </div>
                    </section>

                    <hr style="border: 1px dashed: color: black">

                    <section class="chart" style="display: none">
                        <div class="chart-container mt-4">
                            <h4 class="mb-2">TOP 20 Kota</h4>
                            <canvas id="topCitiesChart"></canvas>
                        </div>

                        <div class="row g-3 mt-4">
                            <div class="col-12 col-lg-6">
                                <div class="chart-container h-100">
                                    <h4 class="mb-2">Grafik Show</h4>
                                    <div class="chart-fixed">
                                        <canvas id="showsChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="chart-container h-100">
                                    <h4 class="mb-2">Penonton per Bioskop</h4>
                                    <div class="chart-fixed">
                                        <canvas id="viewersByCinemaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-4">
                            <div class="col-12 col-lg-6">
                                <div class="chart-container h-100">
                                    <h4 class="mb-2">TOP 20 Bioskop</h4>
                                    <div class="chart-fixed">
                                        <canvas id="topCinemasChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="chart-container h-100">
                                    <h4 class="mb-2">Underperforming Kota</h4>
                                    <div class="chart-fixed">
                                        <canvas id="underperfCitiesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-4">
                            <div class="col-12 col-lg-6">
                                <div class="chart-container h-100">
                                    <h4 class="mb-2">Underperforming Bioskop</h4>
                                    <div class="chart-fixed">
                                        <canvas id="underperfCinemasChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div id="loading" style="display: none; text-align: center;">
                        <img src="https://i.gifer.com/ZZ5H.gif" width="50" alt="Loading...">
                        <p>Loading data, please wait...</p>
                    </div>
                    <br>
                </div>
            </div>
        </div>
    </div>
</div>
<form action="" method="POST" class="delete-form">
    {{ csrf_field() }}
    <!-- Delete modal center -->
    <div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        Confirmation
                        <small class="m-0 text-muted">
                        </small>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure want to delete data?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary remove-data-from-delete-form"
                        data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Delete Data</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script> Chart.register(ChartDataLabels); </script>

<script>
    $(document).ready(function(){
        $('#nama_film').select2();
        $('#bioskop_kategori').select2();
        $('#kota').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();

        $('#data-summary').show();
        $('#chart-container').show(); // Tampilkan grafik
        $('.chart').show();
        $('#download-pdf').hide();

        // Panggil fungsi untuk menampilkan grafik
        var nama_film = $('#nama_film').val();
        var tgl_mulai = $('#tanggal_mulai').val();
        var tgl_akhir = $('#tanggal_akhir').val();
        var bioskop_kategori = $('#bioskop_kategori').val();
        loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori);

        // document.getElementById("download-pdf").addEventListener("click", function () {
        //     const { jsPDF } = window.jspdf;
        //     const doc = new jsPDF("p", "mm", "a4");

        //     const chartCanvas = document.getElementById("topCitiesChart");

        //     const namaFilm = $('#nama_film option:selected').text();
        //     const tanggalMulai = $('#tanggal_mulai').val();
        //     const tanggalAkhir = $('#tanggal_akhir').val();
        //     const kategoriBioskop = $('#bioskop_kategori option:selected').text();

        //     html2canvas(chartCanvas).then(function (canvas) {
        //         const imgData = canvas.toDataURL("image/png");
        //         const imgProps = doc.getImageProperties(imgData);
        //         const pdfWidth = doc.internal.pageSize.getWidth() - 30;
        //         const imgHeight = (imgProps.height * pdfWidth) / imgProps.width;

        //         // Title
        //         doc.setFont("helvetica", "bold");
        //         doc.setFontSize(16);
        //         doc.setTextColor(33, 37, 41); // Dark grey
        //         doc.text("List 10 Besar Kota Dengan Penonton Tertinggi", 15, 20);

        //         // Subtitle Info
        //         doc.setFont("helvetica", "normal");
        //         doc.setFontSize(11);
        //         doc.setTextColor(80, 80, 80); // Soft grey
        //         let y = 28;
        //         doc.text(`Nama Film         : ${namaFilm}`, 15, y);
        //         y += 6;
        //         doc.text(`Periode Tanggal   : ${tanggalMulai} s.d. ${tanggalAkhir}`, 15, y);
        //         y += 6;
        //         doc.text(`Kategori Bioskop  : ${kategoriBioskop}`, 15, y);
        //         y += 10;

        //         // Chart
        //         doc.addImage(imgData, 'PNG', 15, y, pdfWidth, imgHeight);
        //         const startTableY = y + imgHeight + 12;

        //         // Tabel
        //         doc.autoTable({
        //             html: '#summary-table',
        //             startY: startTableY,
        //             margin: { left: 15, right: 15 },
        //             styles: {
        //                 font: 'helvetica',
        //                 fontSize: 10,
        //                 textColor: [33, 37, 41],
        //             },
        //             headStyles: {
        //                 fillColor: [52, 152, 219], // Blue header
        //                 textColor: [255, 255, 255],
        //                 fontStyle: 'bold',
        //             },
        //             alternateRowStyles: { fillColor: [245, 245, 245] },
        //             tableLineColor: [200, 200, 200],
        //             tableLineWidth: 0.1,
        //             theme: 'grid'
        //         });

        //         doc.save("top10-kota-penonton.pdf");
        //     });
        // });

        document.getElementById("download-pdf").addEventListener("click", function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF("l", "mm", "a4");

            // --- layout constants ---
            const marginX = 10;
            const marginTop = 10;
            const gutter = 5; // jarak antar kolom
            const pageW = doc.internal.pageSize.getWidth();
            const pageH = doc.internal.pageSize.getHeight();
            const usableW = pageW - marginX * 2;
            const colW = (usableW - gutter) / 2;  // lebar tiap kolom
            const chartStartY = 34;
            const chartGapY = 4;
            const topChartH = 50;
            const remainingH = pageH - chartStartY - topChartH - (chartGapY * 3) - marginTop;
            const compactChartH = remainingH / 3;

            // info header
            const namaFilm = $('#nama_film option:selected').text();
            const tanggalMulai = $('#tanggal_mulai').val();
            const tanggalAkhir = $('#tanggal_akhir').val();
            const kategoriBioskop = $('#bioskop_kategori option:selected').text();

            // helpers
            function addTitle(text) {
                doc.setFont("helvetica", "bold");
                doc.setFontSize(9);
                doc.setTextColor(33,37,41);
            }
            function fitImageSize(imgProps, maxW, maxH) {
                const r = Math.min(maxW / imgProps.width, maxH / imgProps.height);
                return { w: imgProps.width * r, h: imgProps.height * r };
            }
            function addChartBox(title, dataURL, x, y, boxW, boxH) {
                if (!dataURL) return;
                addTitle(title);
                doc.text(title, x, y + 4);

                const titleH = 6;
                const innerY = y + titleH;
                const innerH = boxH - titleH;
                const imgProps = doc.getImageProperties(dataURL);
                const size = fitImageSize(imgProps, boxW, innerH);
                const imgX = x + (boxW - size.w) / 2;
                const imgY = innerY + (innerH - size.h) / 2;
                doc.addImage(dataURL, 'PNG', imgX, imgY, size.w, size.h, undefined, 'FAST');
            }

            // ===== Header dokumen =====
            doc.setFont("helvetica", "bold"); doc.setFontSize(15); doc.setTextColor(33,37,41);
            doc.text("Laporan Grafik Penonton", marginX, marginTop + 4);
            doc.setFont("helvetica","normal"); doc.setFontSize(9.5); doc.setTextColor(80,80,80);
            doc.text(`Nama Film : ${namaFilm}`, marginX, marginTop + 12);
            doc.text(`Periode : ${tanggalMulai} s.d. ${tanggalAkhir}`, marginX, marginTop + 18);
            doc.text(`Kategori Bioskop : ${kategoriBioskop}`, marginX + (usableW / 2), marginTop + 12);

            // === SISIPKAN LOGO DI KANAN ATAS ===
            const logoEl = document.getElementById('report-logo');
            if (logoEl && logoEl.complete) {
                const logoW = 28;
                const logoH = (logoEl.naturalHeight / logoEl.naturalWidth) * logoW;
                const logoX = pageW - marginX - logoW;
                const logoY = marginTop - 4;

                doc.addImage(logoEl, 'PNG', logoX, logoY, logoW, logoH, undefined, 'FAST');
            }

            // ambil dataURL dari chart instances (fallback ke canvas kalau perlu)
            const imgTopCities  = chartInstances.topCities    ? chartInstances.topCities.toBase64Image()    : document.getElementById('topCitiesChart')?.toDataURL();
            const imgShows      = chartInstances.shows        ? chartInstances.shows.toBase64Image()        : document.getElementById('showsChart')?.toDataURL();
            const imgViewCinema = chartInstances.viewersByCinema ? chartInstances.viewersByCinema.toBase64Image() : document.getElementById('viewersByCinemaChart')?.toDataURL();
            const imgTopCinemas = chartInstances.topCinemas   ? chartInstances.topCinemas.toBase64Image()   : document.getElementById('topCinemasChart')?.toDataURL();
            const imgUnderCity  = chartInstances.underCities  ? chartInstances.underCities.toBase64Image()  : document.getElementById('underperfCitiesChart')?.toDataURL();
            const imgUnderCin   = chartInstances.underCinemas ? chartInstances.underCinemas.toBase64Image() : document.getElementById('underperfCinemasChart')?.toDataURL();

            // ===== Susun sesuai layout di Blade =====
            // 1) TOP 20 Kota (full width)
            addChartBox("TOP 20 Kota", imgTopCities, marginX, chartStartY, usableW, topChartH);

            // 2) Row 1: Shows (kiri) + Viewers by Cinema (kanan)
            const row1Y = chartStartY + topChartH + chartGapY;
            addChartBox("Grafik Show (Per Tanggal)", imgShows, marginX, row1Y, colW, compactChartH);
            addChartBox("Penonton per Bioskop", imgViewCinema, marginX + colW + gutter, row1Y, colW, compactChartH);

            // 3) Row 2: TOP 20 Bioskop (kiri) + Underperforming Kota (kanan)
            const row2Y = row1Y + compactChartH + chartGapY;
            addChartBox("TOP 20 Bioskop", imgTopCinemas, marginX, row2Y, colW, compactChartH);
            addChartBox("Underperforming Kota", imgUnderCity, marginX + colW + gutter, row2Y, colW, compactChartH);

            // 4) Row 3: Underperforming Bioskop (full width)
            const row3Y = row2Y + compactChartH + chartGapY;
            addChartBox("Underperforming Bioskop", imgUnderCin, marginX, row3Y, usableW, compactChartH);

            // ===== Tabel TOP 20 Kota =====
            // if (y + 20 > pageH - 12) { doc.addPage(); y = marginTop; }
            // doc.setFont("helvetica","bold"); doc.setFontSize(13); doc.setTextColor(33,37,41);
            // doc.text("Daftar TOP 20 Kota", marginX, y); y += 4;

            // doc.autoTable({
            //     html: '#summary-table',
            //     startY: y + 2,
            //     margin: { left: marginX, right: marginX },
            //     styles: { font: 'helvetica', fontSize: 10, textColor: [33,37,41] },
            //     headStyles: { fillColor: [52,152,219], textColor: [255,255,255], fontStyle: 'bold' },
            //     alternateRowStyles: { fillColor: [245,245,245] },
            //     tableLineColor: [200,200,200],
            //     tableLineWidth: 0.1,
            //     theme: 'grid'
            // });

            doc.save("report-charts-dashboard.pdf");
            });


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

            $('#data-summary').hide();
            $("#topCitiesChart").remove(); 
            $('#chart-container').hide();
            $('.chart').hide();
            $('#loading').show();
            $('.title-dashboard').hide();

            // Simulasi delay (misalnya ambil data dari server)
            setTimeout(function () {
                $('#loading').hide(); // Hilangkan loading
                $('#data-summary').show();
                $('#chart-container').show(); // Tampilkan grafik
                $('.chart').show();
                $('#download-pdf').show();

                // Panggil fungsi untuk menampilkan grafik
                loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori);
            }, 200);
            
        });

        $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });

    // Delete Data
    $('#datatable').on('click', '.delete-btn[data-url]', function (e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            var url = $(this).attr('data-url');
            var token = $(this).attr('data-token');
            console.log(id,url,token);
            
            $(".delete-form").attr("action",url);
            $('body').find('.delete-form').append('<input name="_token" type="hidden" value="'+ token +'">');
            $('body').find('.delete-form').append('<input name="_method" type="hidden" value="DELETE">');
            $('body').find('.delete-form').append('<input name="id" type="hidden" value="'+ id +'">');
        });
        // Clear Data When Modal Close
        $('.remove-data-from-delete-form').on('click',function() {
            $('body').find('.delete-form').find("input").remove();
        });
    });

    let chartInstances = {
    topCities: null,
    shows: null,
    viewersByCinema: null,
    topCinemas: null,
    underCities: null,
    underCinemas: null
  };

  function destroyIfExists(inst) {
    if (inst && typeof inst.destroy === 'function') inst.destroy();
  }

  function loadChart(nama_film, tgl_mulai, tgl_akhir, bioskop_kategori) {
    $.ajax({
      url: "{{ route('getCharAudienceDashboard') }}", // endpoint baru
      method: "GET",
      data: {
        nama_film: nama_film,
        tgl_mulai: tgl_mulai,
        tgl_akhir: tgl_akhir,
        bioskop_kategori: bioskop_kategori
      },
      success: function (payload) {
        // fallback jika backend lama masih hanya kirim array top cities
        if (Array.isArray(payload)) {
          payload = { top_cities: payload };
        }

        // ---------- TOP 20 KOTA (chart + tabel yang sudah ada) ----------
        const topCities = payload.top_cities || [];
        $('#summary-table tbody').empty();
        $("#topCitiesChart").remove();
        $(".chart-container").first().append('<canvas id="topCitiesChart"></canvas>');

        const labelsTopCities = topCities.map(x => x.kota);
        const valuesTopCities = topCities.map(x => Number(x.jumlah || 0));

        for (let i = 0; i < labelsTopCities.length; i++) {
          $('#summary-table tbody').append(`
            <tr>
              <td>${i + 1}</td>
              <td>${labelsTopCities[i]}</td>
              <td>${Number(valuesTopCities[i]).toLocaleString()}</td>
            </tr>
          `);
        }

        destroyIfExists(chartInstances.topCities);
        chartInstances.topCities = new Chart(
          document.getElementById('topCitiesChart').getContext('2d'),
          {
            type: 'bar',
            data: {
              labels: labelsTopCities,
              datasets: [{
                label: 'Jumlah Penonton',
                data: valuesTopCities,
                backgroundColor: Array.from({length: valuesTopCities.length}, getRandomColor),
                borderWidth: 1
              }]
            },
            options: {
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                }
            }
          }
        );

        // ---------- GRAFIK SHOW (per tanggal) ----------
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
                fill: false,
                tension: 0.2,
                borderWidth: 2
              }]
            },
            options: {
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                }
            }
          }
        );

        // ---------- PENONTON BY BIOSKOP ----------
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
              datasets: [{
                label: 'Penonton',
                data: vbValues,
                backgroundColor: Array.from({length: vbValues.length}, getRandomColor)
              }]
            },
            options: {
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                }
            }
          }
        );

        // ---------- TOP 20 BIOSKOP ----------
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
              datasets: [{
                label: 'Penonton',
                data: topCValues,
                backgroundColor: Array.from({length: topCValues.length}, getRandomColor)
              }]
            },
            options: {
                indexAxis: 'y',                // <-- bikin batang horisontal
                responsive: true,
                maintainAspectRatio: false,    // biar ngikut tinggi container .chart-fixed
                scales: {
                    x: { beginAtZero: true },    // nilai di sumbu X
                    y: {                         // label kategori di sumbu Y
                        ticks: { autoSkip: false } // opsional: semua label tampil
                    }
                },
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                },
                legend: { display: false }
            }
          }
        );

        // ---------- UNDERPERFORMING KOTA ----------
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
              datasets: [{
                label: 'Penonton',
                data: ucValues,
                backgroundColor: Array.from({length: ucValues.length}, getRandomColor)
              }]
            },
            options: {
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                }
            }
          }
        );

        // ---------- UNDERPERFORMING BIOSKOP ----------
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
              datasets: [{
                label: 'Penonton',
                data: ubValues,
                backgroundColor: Array.from({length: ubValues.length}, getRandomColor)
              }]
            },
            options: {
                indexAxis: 'y',                // <-- bikin batang horisontal
                responsive: true,
                maintainAspectRatio: false,    // biar ngikut tinggi container .chart-fixed
                scales: {
                    x: { beginAtZero: true },    // nilai di sumbu X
                    y: {                         // label kategori di sumbu Y
                        ticks: { autoSkip: false } // opsional: semua label tampil
                    }
                },
              plugins: {
                datalabels: {
                    anchor: 'end',        // 'end' untuk vertikal di atas batang
                    align: 'end',         // untuk horizontal: pakai 'right'
                    formatter: (v) => Number(v).toLocaleString(),
                    color: '#000',
                    font: { weight: 'bold', size: 11 }
                    }
                },
                legend: { display: false }
            }
          }
        );
      },
      error: function (xhr, status, error) {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat mengambil data analytics.");
      }
    });
  }

  function getRandomColor() {
    return 'rgba(' + Math.floor(Math.random() * 255) + ',' +
                     Math.floor(Math.random() * 255) + ',' +
                     Math.floor(Math.random() * 255) + ', 0.7)';
  }
</script>
@endsection
