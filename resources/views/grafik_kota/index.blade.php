@extends('layouts.page')

@section('title', 'TOP 20 Kota dengan Penonton Terbanyak')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-users'></i> <span class='fw-300'>TOP 20 Kota dengan Penonton Terbanyak </span>
    </h1>
</div>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>
                    TOP 20 Kota dengan Penonton Terbanyak  <span class="fw-300"></span>
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
                            <div class="form-group col-md-1 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="search-btn" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>
                    <hr style="border: 1px dashed: color: black">
                    <div class="chart-container mt-4">
                        <canvas id="topCitiesChart"></canvas>
                    </div>
                    <div id="loading" style="display: none; text-align: center;">
                        <img src="https://i.gifer.com/ZZ5H.gif" width="50" alt="Loading...">
                        <p>Loading data, please wait...</p>
                    </div>
                    <br>
                    <hr style="border: 1px dashed: color: black">
                    <div id="data-summary" style="display: none;">
                        <h4>📊 List 20 Besar Kota Dengan Penonton Tertinggi</h4>
                        <table id="summary-table" class="table table-bordered table-hover table-striped w-100">
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
<script>
    $(document).ready(function(){
        $('#nama_film').select2();
        $('#bioskop_kategori').select2();
        $('#kota').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();

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
            $('#loading').show();

            // Simulasi delay (misalnya ambil data dari server)
            setTimeout(function () {
                $('#loading').hide(); // Hilangkan loading
                $('#data-summary').show();
                $('#chart-container').show(); // Tampilkan grafik

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
                    console.log("Data diterima:", data);

                    // Kosongkan chart sebelum menampilkan yang baru
                    $('#summary-table tbody').empty();
                    $("#topCitiesChart").remove(); 
                    $(".chart-container").append('<canvas id="topCitiesChart"></canvas>');

                    // Render Chart.js
                    let labels = data.map(item => item.kota);
                    let values = data.map(item => item.jumlah);

                    for (let i = 0; i < labels.length; i++) {
                        $('#summary-table tbody').append(`
                            <tr>
                                <td>${i + 1}</td>
                                <td>${labels[i]}</td>
                                <td>${values[i].toLocaleString()}</td>
                            </tr>
                        `);
                    }

                    let jumlahData = data.length;

                    // Buat array warna random untuk setiap bar
                    let backgroundColors = Array.from({ length: jumlahData }, getRandomColor);
                    let borderColors = backgroundColors.map(color => color.replace("0.7", "1"));

                    let ctx = document.getElementById('topCitiesChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Jumlah Penonton',
                                data: values,
                                backgroundColor: backgroundColors,
                                borderColor: borderColors,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.error("Error:", error);
                    alert("Terjadi kesalahan saat mengambil data.");
                }
            });
    }

    function getRandomColor() {
    return 'rgba(' + Math.floor(Math.random() * 255) + ',' +
                     Math.floor(Math.random() * 255) + ',' +
                     Math.floor(Math.random() * 255) + ', 0.7)';
    }

    function parseNumber(value) {
        value = String(value);
        var number = value.replace(/[^0-9.-]+/g, ''); // Hapus karakter selain angka
        return !isNaN(number) ? parseFloat(number) : 0; // Kembalikan angka jika valid, atau 0 jika tidak
    }

    // Helper function to format number with commas (e.g., 1000 -> 1,000)
    function formatCurrency(value) {
        return value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
</script>
@endsection