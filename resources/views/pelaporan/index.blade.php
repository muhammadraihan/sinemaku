@extends('layouts.page')

@section('title', 'Laporan Management')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-users'></i> Modul: <span class='fw-300'>Laporan </span>
        <small>
            Modul Laporan.
        </small>
    </h1>
</div>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>
                    Laporan  <span class="fw-300"><i>List</i></span>
                </h2>
                <div class="panel-toolbar">
                    <a class="nav-link active" href="{{route('pelaporan.create')}}"><i class="fal fa-plus-circle">
                        </i>
                        <span class="nav-link-text">Tambah Data</span>
                    </a>
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
                        </div>
                        <div class="row">
                            <div class="form-group col-md-1 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="search-btn" class="btn btn-primary w-100"><i class="fal fa-search"></i>&nbsp;&nbsp;Search</button>
                            </div>
                            <div class="form-group col-md-1 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="reset" class="btn btn-danger w-100"><i class="fal fa-times-circle"></i>&nbsp;&nbsp;Reset</button>
                            </div>
                        </div>
                    </form>
                    <!-- datatable start -->
                    <table id="datatable" class="table table-bordered table-hover table-striped w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kategori Bioskop</th>
                <th>Provinsi</th>
                <th>Kota</th>
                <th>Nama Bioskop</th>
                <th>Nama Film</th>
                <th>Studio</th>
                <th>Show</th>
                <th>Jam</th>
                <th>Tipe Tiket</th>
                <th>Harga</th>
                <th>Total Tiket</th>
                <th>Gross</th>
                <th>Tax</th>
                <th>Net</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Edited By</th>
                <th>Edited At</th>
                <th width="120px">Aksi</th>
                </tr>
                        </thead>
                    </table>
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
                        Konfirmasi
                        <small class="m-0 text-muted">
                        </small>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    Anda yakin ingin menghapus data?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary remove-data-from-delete-form"
                        data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Hapus Data</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script>
    $(document).ready(function(){
        $('#nama_film').select2();
        $('#bioskop_kategori').select2();
        $('#kota').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();

        loadData();

        $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });

    $(document).delegate("#search-btn", "click", function (event) {
            event.preventDefault();

            var nama_film = $('#nama_film').val();
            var tgl_mulai = $('#tanggal_mulai').val();
            var tgl_akhir = $('#tanggal_akhir').val();
            var bioskop_kategori = $('#bioskop_kategori').val();
            var kota = 'ALL';
            var nama_bioskop = 'ALL';
            var type_tiket = 'ALL';

            if ($.fn.DataTable.isDataTable("#datatable")) {
                $('#datatable').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#summary-table")) {
                $('#summary-table').DataTable().destroy();
            }

            var table = $('#datatable').DataTable({
                "processing": true,
                "serverSide": true,
                "responsive": true,
                "order": [[ 0, "asc" ]],
                "ajax":{
                    url:'{{route('laporan.search')}}',
                    type : "GET",
                    data: {
                        nama_film: nama_film,
                        tgl_mulai : tgl_mulai,
                        tgl_akhir : tgl_akhir,
                        bioskop_kategori : bioskop_kategori,
                        kota : kota,
                        nama_bioskop : nama_bioskop,
                        type_tiket : type_tiket,
                    }
                },
                    "columns": [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                        {data: 'tgl_tayang', name: 'tgl_tayang'},
                        {data: 'kategori', name: 'kategori'},
                        {data: 'provinsi', name: 'provinsi'},
                        {data: 'kota', name: 'kota'},
                        {data: 'nama_bioskop', name: 'nama_bioskop'},
                        {data: 'nama_film', name: 'nama_film'},
                        {data: 'studio', name: 'studio'},
                        {data: 'show', name: 'show'},
                        {data: 'jam_tayang', name: 'jam_tayang'},
                        {data: 'type_tiket', name: 'type_tiket'},
                        {data: 'harga', name: 'harga'},
                        {data: 'jumlah', name: 'jumlah'},
                        {data: 'gross', name: 'gross'},
                        {data: 'tax', name: 'tax'},
                        {data: 'net', name: 'net'},
                        {data: 'created_by', name: 'created_by'},
                        {data: 'created_at', name: 'created_at'},
                        {data: 'edited_by', name: 'edited_by'},
                        {data: 'updated_at', name: 'updated_at'},
                        {data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });

            $('#datatable').show();
            
        });

        $(document).delegate("#reset", "click", function (event) {
            event.preventDefault();
            if ($.fn.DataTable.isDataTable("#datatable")) {
                $('#datatable').DataTable().destroy();
            }
            loadData();
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

    function loadData(){
        var table = $('#datatable').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "order": [[ 0, "asc" ]],
            "ajax":{
                url:'{{route('pelaporan.index')}}',
                type : "GET",
                dataType: 'json',
                error: function(data){
                    console.log(data);
                    }
            },
            "columns": [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'tgl_tayang', name: 'tgl_tayang'},
            {data: 'kategori', name: 'kategori'},
            {data: 'provinsi', name: 'provinsi'},
            {data: 'kota', name: 'kota'},
            {data: 'nama_bioskop', name: 'nama_bioskop'},
            {data: 'nama_film', name: 'nama_film'},
            {data: 'studio', name: 'studio'},
            {data: 'show', name: 'show'},
            {data: 'jam_tayang', name: 'jam_tayang'},
            {data: 'type_tiket', name: 'type_tiket'},
            {data: 'harga', name: 'harga'},
            {data: 'jumlah', name: 'jumlah'},
            {data: 'gross', name: 'gross'},
            {data: 'tax', name: 'tax'},
            {data: 'net', name: 'net'},
            {data: 'created_by', name: 'created_by'},
            {data: 'created_at', name: 'created_at'},
            {data: 'edited_by', name: 'edited_by'},
            {data: 'updated_at', name: 'updated_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });
    }
</script>
@endsection