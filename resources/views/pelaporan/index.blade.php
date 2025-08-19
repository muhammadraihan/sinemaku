@extends('layouts.page')

@section('title', 'Laporan Management')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<style>
.custom-dropdown {
    position: relative;
    display: inline-block;
    width: 130px; /* biar bentuknya sama */
}

.custom-dropdown-btn {
    background-color: #17a2b8; /* warna btn-info */
    color: white;
    padding: 10px 15px;
    font-size: 16px;
    border: none;
    cursor: pointer;
    width: 100%;
    text-align: left;
    border-radius: 6px;
}

.custom-dropdown-btn i {
    margin-right: 8px;
}

.custom-dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    min-width: 100%;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 99;
}

.custom-dropdown-menu a {
    color: black;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
}

.custom-dropdown-menu a:hover {
    background-color: #f1f1f1;
}

/* Backdrop + container */
.upload-modal {
  position: fixed; inset: 0; display: none;
  z-index: 1000;
}
.upload-modal.is-open { display: block; }

.upload-modal__backdrop {
  position: absolute; inset: 0;
  background: rgba(0,0,0,.55);
  backdrop-filter: blur(2px);
  animation: fadeIn .15s ease;
}

/* Dialog */
.upload-modal__dialog {
  position: relative; max-width: 520px; width: calc(100% - 32px);
  margin: 8vh auto; background: #fff; border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
  padding: 20px 20px 16px;
  animation: popIn .18s ease;
}

/* Title */
.upload-modal__title { margin: 0 0 12px; font-size: 18px; font-weight: 700; }

/* Close button (X) */
.upload-modal__close {
  position: absolute; top: 10px; right: 10px;
  width: 32px; height: 32px; border: 0; border-radius: 50%;
  background: transparent; cursor: pointer; font-size: 20px; line-height: 1;
}
.upload-modal__close:hover { background: #f2f2f2; }

/* Form */
.upload-modal__field { display: flex; flex-direction: column; gap: 6px; margin: 14px 0 6px; }
.upload-modal__field label { font-size: 14px; font-weight: 600; color: #111; }
.upload-modal__hint { color: #6b7280; font-size: 12px; }

/* Buttons */
.btn_custom { border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
.btn--primary { background: #111; color: #fff; }
.btn--primary:hover { background: #222; }
.btn--ghost { background: transparent; color: #111; }
.btn--ghost:hover { background: #f2f2f2; }

.upload-modal__actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }

/* Animations */
@keyframes fadeIn { from {opacity:0} to {opacity:1} }
@keyframes popIn { from {opacity:0; transform: translateY(8px) scale(.98)} to {opacity:1; transform: none} }

/* Optional: lock scroll ketika modal terbuka */
.body-modal-open { overflow: hidden; }
</style>
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
                    <div class="row">
                        {{-- <div class="form-group col-md-1 mb-2">
                            <br> --}}
                            <div class="custom-dropdown">
                                <button class="btn btn_custom btn-outline-info waves-effect waves-themed import">
                                    <i class="fal fa-cloud-upload"> Upload Data</i>
                                </button>
                                <div class="custom-dropdown-menu">
                                    <a href="javascript:void(0);" class="open-upload-modal" data-bioskop="XXI">XXI</a>
                                    <a href="javascript:void(0);" class="open-upload-modal" data-bioskop="CGV">CGV</a>
                                </div>
                            </div>
                        {{-- </div> --}}
                    </div>
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
                            <div class="form-group col-md-6 mb-3">
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
                            <div class="form-group col-md-2 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="search-btn" class="btn btn-primary w-100"><i class="fal fa-search"></i>&nbsp;&nbsp;Search</button>
                            </div>
                            <div class="form-group col-md-2 mb-3">
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

<!-- Upload Modal XXI -->
<div class="modal fade" id="modal-upload" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h4 class="modal-title">
          Upload File
          <small class="m-0 text-muted">Pilih file untuk diunggah</small>
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"><i class="fal fa-times"></i></span>
        </button>
      </div>
      
      <!-- Body -->
      <div class="modal-body">
        <form id="uploadForm" action="{{ route('pelaporan.upload.xxi') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label for="uploadFile">Pilih File</label>
            <input type="file" name="file" id="uploadFile" class="form-control"
                   accept=".xlsx" required>
            <small class="form-text text-muted">
              Format: .xlsx
            </small>
          </div>
        </form>

      <!-- Box status proses -->
        <div id="upload-status" class="mt-3 d-none">
          <p class="mb-1"><strong id="status-text">Mengunggah file...</strong></p>
          <div class="progress">
            <div id="status-progress" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%">0%</div>
          </div>
          <small id="status-note" class="text-muted"></small>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="modal-footer">
        {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button> --}}
        <button type="button" class="btn btn-secondary btn-close-upload" data-dismiss="modal">Tutup</button>
        <a href="#" id="btn-download-errors" class="btn btn-outline-danger d-none" target="_blank">
            Download Excel Error
        </a>
        <button type="submit" form="uploadForm" class="btn btn-primary">Upload</button>
      </div>
    </div>
  </div>
</div>

<!-- Upload Modal XXI -->
<div class="modal fade" id="modal-upload" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h4 class="modal-title">
          Upload File
          <small class="m-0 text-muted">Pilih file untuk diunggah</small>
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true"><i class="fal fa-times"></i></span>
        </button>
      </div>
      
      <!-- Body -->
      <div class="modal-body">
        <form id="uploadForm" action="#" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label for="uploadFile">Pilih File</label>
            <input type="file" name="file" id="uploadFile" class="form-control"
                   accept=".xlsx" required>
            <small class="form-text text-muted">
              Format: .xlsx
            </small>
          </div>
        </form>

      <!-- Box status proses -->
        <div id="upload-status" class="mt-3 d-none">
          <p class="mb-1"><strong id="status-text">Mengunggah file...</strong></p>
          <div class="progress">
            <div id="status-progress" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%">0%</div>
          </div>
          <small id="status-note" class="text-muted"></small>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="modal-footer">
        {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button> --}}
        <button type="button" class="btn btn-secondary btn-close-upload" data-dismiss="modal">Tutup</button>
        <a href="#" id="btn-download-errors" class="btn btn-outline-danger d-none" target="_blank">
            Download Excel Error
        </a>
        <button type="submit" form="uploadForm" class="btn btn-primary">Upload</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
{{-- <script src="{{ asset('assets/js/sweetalert2.bundle.js') }}"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    var bioskop = [];

    $(".import").click(function(){
        $(this).next(".custom-dropdown-menu").slideToggle(200);
    });

    // Tutup dropdown kalau klik di luar
    $(document).click(function(e) {
        if (!$(e.target).closest(".custom-dropdown").length) {
            $(".custom-dropdown-menu").slideUp(200);
        }
    });

    $(document).on('click', '.open-upload-modal', function(e){
        e.preventDefault();
        bioskop = $(this).attr("data-bioskop");
        $('#modal-upload').modal('show');
    });

    const $modal = $('#modal-upload');
    const $btnUpload = $('.btn-start-upload');
    const $btnClose  = $('.btn-close-upload');
    const $statusBox = $('#upload-status');
    const $statusText = $('#status-text');
    const $statusNote = $('#status-note');
    const $statusProg = $('#status-progress');
    const $btnDownloadErr = $('#btn-download-errors');

    let progressTimer = null;
    let currentPct = 0;

    function setProcessingUI(isProcessing) {
        if (isProcessing) {
        $btnUpload.prop('disabled', true);
        $btnClose.addClass('d-none');
        $modal.find('.close.btn-close-upload').addClass('d-none');
        $statusBox.removeClass('d-none');
        $btnDownloadErr.addClass('d-none').attr('href', '#');
        } else {
        $btnUpload.prop('disabled', false);
        $btnClose.removeClass('d-none');
        $modal.find('.close.btn-close-upload').removeClass('d-none');
        }
    }

    function updateProgress(pct, text, note) {
        const clamped = Math.max(0, Math.min(100, parseInt(pct || 0, 10)));
        currentPct = clamped;
        $statusProg.css('width', clamped + '%').text(clamped + '%');
        if (text) $statusText.text(text);
        if (note !== undefined) $statusNote.text(note);
    }

    function startDummyProgress() {
        if (progressTimer) clearInterval(progressTimer);
        currentPct = 0;
        updateProgress(0, 'Mengunggah file...', '');
        progressTimer = setInterval(function () {
        if (currentPct < 90) {
            updateProgress(currentPct + 3, (currentPct < 50 ? 'Mengunggah file...' : 'Memproses...'), '');
        }
        }, 500);
    }

    function stopDummyProgress() {
        if (progressTimer) {
        clearInterval(progressTimer);
        progressTimer = null;
        }
    }

    $modal.on('show.bs.modal', function () {
        $('#uploadForm')[0].reset();
        $statusBox.addClass('d-none');
        updateProgress(0, 'Mengunggah file...', '');
        setProcessingUI(false);
        stopDummyProgress();
        $btnDownloadErr.addClass('d-none').attr('href', '#');
    });

    $('#uploadForm').on('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        setProcessingUI(true);
        startDummyProgress();

        if(bioskop === 'XXI'){
            $.ajax({
            // url: $(this).attr('action'),
            url: '{{route('pelaporan.upload.xxi')}}',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            })
            .done(function (res) {
            stopDummyProgress();

            if (res.status === 'success') {
                updateProgress(100, 'Berhasil', res.message || 'Import selesai.');
                setProcessingUI(false);

                // Tutup modal upload
                $('#modal-upload').modal('hide');

                // Saat modal selesai tertutup, baru tampilkan Swal
                $('#modal-upload').on('hidden.bs.modal', function () {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Import selesai.',
                        showConfirmButton: true
                    }).then(() => {
                        $('#datatable').DataTable().ajax.reload(null, false);
                    });

                    // Lepas listener biar nggak double kalau diupload lagi
                    $(this).off('hidden.bs.modal');
                });
            } else if (res.status === 'failed') {
                // gagal karena hasil query > 0
                updateProgress(currentPct, 'Gagal', res.message || 'Validasi gagal.');
                if (res.download_url) {
                $btnDownloadErr.attr('href', res.download_url).removeClass('d-none');
                }
                setProcessingUI(false);
            } else {
                updateProgress(currentPct, 'Gagal', res.message || 'Terjadi kesalahan.');
                setProcessingUI(false);
            }
            })
            .fail(function (xhr) {
            stopDummyProgress();
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload gagal.';
            updateProgress(currentPct, 'Gagal', msg);
            setProcessingUI(false);
            });
        }else if (bioskop === 'CGV'){
            $.ajax({
            // url: $(this).attr('action'),
            url: '{{route('pelaporan.upload.cgv')}}',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            })
            .done(function (res) {
            stopDummyProgress();

            if (res.status === 'success') {
                updateProgress(100, 'Berhasil', res.message || 'Import selesai.');
                setProcessingUI(false);

                // Tutup modal upload
                $('#modal-upload').modal('hide');

                // Saat modal selesai tertutup, baru tampilkan Swal
                $('#modal-upload').on('hidden.bs.modal', function () {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Import selesai.',
                        showConfirmButton: true
                    }).then(() => {
                        $('#datatable').DataTable().ajax.reload(null, false);
                    });

                    // Lepas listener biar nggak double kalau diupload lagi
                    $(this).off('hidden.bs.modal');
                });
            } else if (res.status === 'failed') {
                // gagal karena hasil query > 0
                updateProgress(currentPct, 'Gagal', res.message || 'Validasi gagal.');
                if (res.download_url) {
                $btnDownloadErr.attr('href', res.download_url).removeClass('d-none');
                }
                setProcessingUI(false);
            } else {
                updateProgress(currentPct, 'Gagal', res.message || 'Terjadi kesalahan.');
                setProcessingUI(false);
            }
            })
            .fail(function (xhr) {
            stopDummyProgress();
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload gagal.';
            updateProgress(currentPct, 'Gagal', msg);
            setProcessingUI(false);
            });
        }
    });

  // Jangan lupa: modal diberi data-backdrop="static" data-keyboard="false" supaya tidak tertutup sebelum final

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