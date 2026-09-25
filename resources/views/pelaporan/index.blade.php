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
.cinepolis-preview-modal .modal-dialog { max-width: 96vw; }
.cinepolis-preview-table { font-size: 11px; white-space: nowrap; }
.cinepolis-preview-table th { background: #1f2937; color: #fff; }
.cinepolis-preview-table .is-blocked { background: #fff1f2; color: #991b1b; }
.cinepolis-preview-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 8px; }
.cinepolis-preview-summary .metric { padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f8fafc; }
.cinepolis-preview-summary .label { display: block; color: #6b7280; font-size: 11px; }
.cinepolis-preview-summary .value { display: block; font-weight: 700; margin-top: 3px; }
.cinepolis-preview-issues-list { margin: 8px 0 0; padding-left: 20px; list-style: disc; }
.cinepolis-preview-issues-list > li { margin: 8px 0; padding-left: 2px; }
.cinepolis-preview-issues-list .quick-master-action { flex: 0 0 auto; }
/* Target Swal hanya menjadi layer ketika SweetAlert benar-benar terbuka. */
.cinepolis-preview-modal .cinepolis-preview-swal-target:empty { display: none; }
.cinepolis-preview-modal .cinepolis-preview-swal-target { position: absolute; inset: 0; z-index: 1060; pointer-events: none; }
.cinepolis-preview-modal .cinepolis-preview-swal-target .swal2-container { position: absolute; inset: 0; pointer-events: auto; }
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
                                    <a href="javascript:void(0);" class="open-upload-modal" data-bioskop="SAMS STUDIOS">SAMS STUDIOS</a>
                                    <a href="javascript:void(0);" class="open-upload-modal" data-bioskop="CINEPOLIS PDF">CINEPOLIS PDF</a>
                                    <a href="javascript:void(0);" class="open-upload-modal" data-bioskop="PLATINUM PDF">PLATINUM PDF</a>
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
                            <div class="form-group col-md-2 mb-3 filter-search-column">
                                <button type="button" id="search-btn" class="btn btn-primary w-100 filter-search-btn" title="Tampilkan data pelaporan" aria-label="Tampilkan data pelaporan">
                                    <i class="fal fa-search"></i>
                                </button>
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

<div class="modal fade cinepolis-preview-modal" id="modal-legacy-preview" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header"><h4 class="modal-title">Preview Import Excel <small class="m-0 text-muted">Periksa mapping sebelum menyimpan</small></h4><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fal fa-times"></i></span></button></div>
    <div class="modal-body"><div id="legacy-preview-summary" class="cinepolis-preview-summary mb-3"></div><div id="legacy-preview-issues" class="alert alert-danger d-none"></div><div id="legacy-preview-warnings" class="alert alert-warning d-none"></div><div class="table-responsive"><table id="legacy-preview-table" class="table table-bordered table-hover cinepolis-preview-table w-100"><thead><tr><th>Status</th><th>Baris</th><th>Tanggal</th><th>Film</th><th>Bioskop</th><th>Kota</th><th>Studio</th><th>Jam</th><th>Show</th><th>Tipe Tiket</th><th>Harga</th><th>Jumlah</th></tr></thead><tbody></tbody></table></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="button" id="btn-confirm-legacy-import" class="btn btn-primary" disabled>Konfirmasi Import</button></div>
    <div class="cinepolis-preview-swal-target"></div>
  </div></div>
</div>

<div class="modal fade cinepolis-preview-modal" id="modal-cinepolis-preview" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Preview Import Cinepolis PDF <small class="m-0 text-muted">Periksa mapping sebelum menyimpan</small></h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"><i class="fal fa-times"></i></span></button>
      </div>
      <div class="modal-body">
        <div id="cinepolis-preview-summary" class="cinepolis-preview-summary mb-3"></div>
        <div id="cinepolis-preview-cinema-mapping" class="alert alert-info d-none"></div>
        <div id="cinepolis-preview-issues" class="alert alert-danger d-none"></div>
        <div id="cinepolis-preview-warnings" class="alert alert-warning d-none"></div>
        <div class="table-responsive">
          <table id="cinepolis-preview-table" class="table table-bordered table-hover cinepolis-preview-table w-100">
            <thead><tr><th>Status</th><th>Tanggal</th><th>Jam</th><th>Kategori</th><th>Bioskop</th><th>Kota</th><th>Film</th><th>Studio</th><th>Tipe Tiket</th><th>Harga</th><th>Admits</th><th>Gross</th><th>Tax Amount</th><th>Tax Rate</th><th>Net</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" id="btn-confirm-cinepolis-import" class="btn btn-primary" disabled>Konfirmasi Import</button>
      </div>
      <div class="cinepolis-preview-swal-target"></div>
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
        var isPdfReport = bioskop === 'CINEPOLIS PDF' || bioskop === 'PLATINUM PDF';
        var pdfProvider = bioskop === 'PLATINUM PDF' ? 'Platinum' : 'Cinepolis';
        $('#uploadFile').attr('accept', isPdfReport ? '.pdf,application/pdf' : '.xlsx,.xls');
        $('#modal-upload .modal-title').html(isPdfReport
            ? 'Upload ' + pdfProvider + ' PDF <small class="m-0 text-muted">File akan diparse dan ditampilkan terlebih dahulu untuk review</small>'
            : 'Upload File <small class="m-0 text-muted">Pilih file untuk diunggah</small>');
        $('#modal-upload .form-text').text(isPdfReport ? 'Format: .pdf (maks. 20MB). Data belum disimpan sebelum Konfirmasi Import.' : 'Format: .xlsx / .xls');
        $(".custom-dropdown-menu").hide();
        $('#modal-upload').appendTo('body');
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

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function openPreviewAfterUploadModal(callback) {
        var completed = false;
        var finish = function () {
            if (completed) return;
            completed = true;
            $('#modal-upload').off('hidden.bs.modal.previewFallback');
            $('#modal-cinepolis-preview, #modal-legacy-preview').appendTo('body');
            callback();
        };
        $('#modal-upload').one('hidden.bs.modal.previewFallback', finish);
        $('#modal-upload').modal('hide');
        window.setTimeout(finish, 450);
    }

    // Must be initialized before the PDF upload handler returns to preview.
    // `var` hoists only the declaration; a later assignment leaves this undefined.
    var activePdfPreview = null;
    var activePdfProvider = 'cinepolis';
    var pdfEndpoints = {
        cinepolis: { quick: @json(route('pelaporan.upload.cinepolis.quick-master')), confirm: @json(route('pelaporan.upload.cinepolis.confirm')) },
        platinum: { quick: @json(route('pelaporan.upload.platinum.quick-master')), confirm: @json(route('pelaporan.upload.platinum.confirm')) }
    };

    $('#uploadForm').on('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    if (bioskop === 'CINEPOLIS PDF' || bioskop === 'PLATINUM PDF') {
        var isPlatinumPdf = bioskop === 'PLATINUM PDF';
        activePdfProvider = isPlatinumPdf ? 'platinum' : 'cinepolis';
        setProcessingUI(true);
        startDummyProgress();
        $.ajax({
            url: isPlatinumPdf ? @json(route('pelaporan.upload.platinum.preview')) : @json(route('pelaporan.upload.cinepolis.preview')),
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
        }).done(function (res) {
            stopDummyProgress();
            setProcessingUI(false);
            openPreviewAfterUploadModal(function () {
                bindCinepolisConfirm();
                showCinepolisPreview(res);
            });
        }).fail(function (xhr) {
            stopDummyProgress();
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'PDF Cinepolis gagal dibaca.';
            updateProgress(currentPct, 'Gagal', msg);
            setProcessingUI(false);
            Swal.fire({ icon: 'error', title: 'Preview gagal', text: msg });
        });
        return;
    }

    setProcessingUI(true);
    startDummyProgress();

    function showCinepolisPreview(res) {
        var summary = res.summary || {};
        var money = function (value) { return 'IDR ' + Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var metrics = [
            ['Kategori', summary.category], ['Bioskop', summary.cinema], ['Film', summary.film], ['Tanggal', summary.date],
            ['Admits', summary.admits], ['Gross', money(summary.gross)], ['Tax', money(summary.tax_amount)], ['Net', money(summary.net)]
        ];
        $('#cinepolis-preview-summary').html(metrics.map(function (item) { return '<div class="metric"><span class="label">' + escapeHtml(item[0]) + '</span><span class="value">' + escapeHtml(item[1]) + '</span></div>'; }).join(''));
        var cinemaMapping = res.cinema_mapping || {};
        var mappingConfirmation = !!cinemaMapping.requires_confirmation;
        var ambiguousCinema = !!cinemaMapping.ambiguous;
        var cinemaOptions = (cinemaMapping.candidates || []).map(function (candidate) {
            return '<option value="' + escapeHtml(candidate.uuid) + '">' + escapeHtml(candidate.name) + ' — ' + escapeHtml(candidate.city || 'Kota belum diisi') + '</option>';
        }).join('');
        $('#cinepolis-preview-cinema-mapping')
            .toggleClass('d-none', !mappingConfirmation && !ambiguousCinema)
            .html(ambiguousCinema
                ? '<label class="font-weight-bold d-block mb-2" for="select-cinepolis-cinema">Nama laporan “' + escapeHtml(cinemaMapping.report_name) + '” cocok dengan beberapa Master Bioskop. Pilih bioskop yang benar:</label><select id="select-cinepolis-cinema" class="form-control"><option value="">Pilih bioskop dan kota</option>' + cinemaOptions + '</select>'
                : (mappingConfirmation
                    ? '<label class="mb-0 d-flex align-items-start"><input type="checkbox" id="confirm-cinepolis-cinema-mapping" class="mr-2 mt-1"> <span>Nama pada laporan <strong>“' + escapeHtml(cinemaMapping.report_name) + '”</strong> akan dipetakan ke Master Bioskop <strong>“' + escapeHtml(cinemaMapping.master_name) + '”</strong>. Saya sudah memeriksa dan menyetujui mapping ini.</span></label>'
                    : ''));
        var issues = res.blocking_issues || [];
        var warnings = res.warnings || [];
        activePdfPreview = res;
        var quickContext = res.quick_master_context || {};
        var issueHtml = issues.map(function (issue) {
            var action = quickMasterActionForIssue(issue, quickContext, res);
            return '<li><div class="d-flex justify-content-between align-items-center flex-wrap"><span>' + escapeHtml(issue) + '</span>' + (action ? '<button type="button" class="btn btn-sm btn-outline-danger ml-2 mt-1 quick-master-action" data-resource="' + action.resource + '" data-issue="' + escapeHtml(issue) + '">' + action.label + '</button>' : '') + '</div></li>';
        }).join('');
        $('#cinepolis-preview-issues').toggleClass('d-none', !issues.length).html(issues.length ? '<strong>Import diblokir:</strong><ul class="cinepolis-preview-issues-list">' + issueHtml + '</ul>' : '');
        $('.quick-master-action').off('click').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openQuickMaster($(this).data('resource'), $(this).data('issue'));
        });
        $('#cinepolis-preview-warnings').toggleClass('d-none', !warnings.length).html(warnings.length ? '<strong>Perhatian:</strong><ul class="mb-0">' + warnings.map(function (warning) { return '<li>' + escapeHtml(warning) + '</li>'; }).join('') + '</ul>' : '');
        var rows = (res.preview || []).map(function (row) {
            var blocked = row.mapping_status !== 'Siap';
            var studioLabel = (activePdfProvider === 'platinum' ? 'STUDIO ' : 'CINEMA ') + escapeHtml(row.studio || summary.studio || '');
            return '<tr class="' + (blocked ? 'is-blocked' : '') + '"><td>' + escapeHtml(row.mapping_status) + '</td><td>' + escapeHtml(row.tanggal) + '</td><td>' + escapeHtml(row.jam_tayang) + '</td><td>' + escapeHtml(row.kategori) + '</td><td>' + escapeHtml(row.bioskop) + '</td><td>' + escapeHtml(row.kota || '-') + '</td><td>' + escapeHtml(summary.film) + '</td><td>' + studioLabel + '</td><td>' + escapeHtml(row.type_tiket) + '</td><td>' + money(row.harga) + '</td><td>' + escapeHtml(row.jumlah) + '</td><td>' + money(row.gross) + '</td><td>' + money(row.tax_amount) + '</td><td>' + escapeHtml(row.tax_rate) + '%</td><td>' + money(row.net) + '</td></tr>';
        }).join('');
        $('#cinepolis-preview-table tbody').html(rows);
        var canImport = !!res.token && !issues.length && !mappingConfirmation && !ambiguousCinema;
        $('#btn-confirm-cinepolis-import')
            .data('token', res.token || '')
            .data('requires-cinema-confirmation', mappingConfirmation)
            .data('ambiguous-cinema', ambiguousCinema)
            .prop('disabled', !canImport);
        $('#confirm-cinepolis-cinema-mapping').off('change').on('change', function () {
            $('#btn-confirm-cinepolis-import').prop('disabled', !this.checked || !res.token || issues.length > 0);
        });
        $('#select-cinepolis-cinema').off('change').on('change', function () {
            $('#btn-confirm-cinepolis-import').prop('disabled', !this.value || !res.token || issues.length > 0);
        });
        if (ambiguousCinema) {
            $('#btn-confirm-cinepolis-import').data('selected-cinema-uuid', '');
            $('#select-cinepolis-cinema').on('change', function () {
                $('#btn-confirm-cinepolis-import').data('selected-cinema-uuid', this.value);
            });
        } else {
            $('#btn-confirm-cinepolis-import').data('selected-cinema-uuid', '');
        }
        $('#modal-cinepolis-preview').modal('show');
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function quickMasterActionForIssue(issue, context, preview) {
        if (issue.indexOf('belum terdaftar sebagai bioskop') !== -1) return { resource: 'cinema', label: 'Tambah Master Bioskop' };
        if (issue.indexOf('belum terdaftar di Master Film') !== -1) return { resource: 'film', label: 'Tambah Master Film' };
        if (issue.indexOf('Tipe tiket ') === 0) return { resource: 'ticket_type', label: 'Tambah Tipe Tiket' };
        if (issue.indexOf('Studio ') === 0 && context.cinema_uuid && ticketTypeAlreadyMapped(preview, issue)) return { resource: 'capacity', label: 'Tambah Master Kapasitas' };
        return null;
    }

    function ticketTypeAlreadyMapped(preview, issue) {
        var studio = firstMissingStudio([issue]);
        return Object.keys(preview.row_mappings || {}).some(function (key) {
            var mapping = preview.row_mappings[key];
            return key.indexOf(String(studio) + '|') === 0 && mapping.ticket_uuid;
        });
    }

    function firstMissingTicket(issues) {
        var issue = (issues || []).find(function (value) { return value.indexOf('Tipe tiket ') === 0; });
        return issue ? issue.replace(/^Tipe tiket /, '').replace(/ belum tersedia.*$/, '') : '';
    }

    function firstMissingStudio(issues) {
        var issue = (issues || []).find(function (value) { return value.indexOf('Studio ') === 0; });
        var match = issue && issue.match(/^Studio (?:CINEMA )?([^ ]+)/);
        return match ? match[1] : '';
    }

    function capacityTicketName(issues) {
        var issue = (issues || []).find(function (value) { return value.indexOf('Studio ') === 0; });
        var match = issue && issue.match(/untuk tipe tiket (.+)\.$/);
        return match ? match[1] : '';
    }

    function openQuickMaster(resource, issue) {
        var res = activePdfPreview || {};
        var ctx = res.quick_master_context || {};
        var issues = issue ? [issue] : (res.blocking_issues || []);
        var target = document.querySelector('#modal-cinepolis-preview .cinepolis-preview-swal-target');
        var title = { cinema: 'Tambah Master Bioskop', film: 'Tambah Master Film', ticket_type: 'Tambah Tipe Tiket', capacity: 'Tambah Master Kapasitas' }[resource];
        var fields = resource === 'cinema'
            ? '<input id="qm-name" class="swal2-input" value="' + escapeHtml(ctx.cinema_name || '') + '" placeholder="Nama bioskop"><input id="qm-city" class="swal2-input" placeholder="Kota">'
            : resource === 'film'
                ? '<input id="qm-name" class="swal2-input" value="' + escapeHtml(ctx.film_name || '') + '" placeholder="Nama film">'
                : resource === 'ticket_type'
                    ? '<input id="qm-name" class="swal2-input" value="' + escapeHtml(firstMissingTicket(issues)) + '" placeholder="Tipe tiket">'
                    : '<input id="qm-studio" class="swal2-input" value="' + escapeHtml(firstMissingStudio(issues)) + '" placeholder="Nomor studio"><input id="qm-capacity" type="number" min="0" class="swal2-input" placeholder="Kapasitas kursi">';
        Swal.fire({
            target: target,
            title: title,
            html: '<p class="text-muted mb-2">Data diisi dari PDF. Periksa sebelum menyimpan.</p>' + fields,
            showCancelButton: true,
            confirmButtonText: 'Simpan & Periksa Ulang',
            cancelButtonText: 'Batal',
            focusConfirm: false,
            preConfirm: function () {
                var payload = { token: res.token, resource: resource };
                if (resource === 'cinema') { payload.name = $('#qm-name').val(); payload.city = $('#qm-city').val(); }
                if (resource === 'film' || resource === 'ticket_type') payload.name = $('#qm-name').val();
                if (resource === 'capacity') {
                    payload.cinema_uuid = ctx.cinema_uuid;
                    payload.ticket_uuid = findTicketUuidForCapacity(res, firstMissingStudio(issues), capacityTicketName(issues));
                    payload.studio = $('#qm-studio').val();
                    payload.kapasitas = $('#qm-capacity').val();
                }
                if ((resource === 'capacity' && (!payload.cinema_uuid || !payload.ticket_uuid || !payload.studio || payload.kapasitas === '')) || ((resource === 'film' || resource === 'ticket_type') && !payload.name) || (resource === 'cinema' && (!payload.name || !payload.city))) {
                    Swal.showValidationMessage('Lengkapi semua field wajib.');
                    return false;
                }
                return $.post(pdfEndpoints[activePdfProvider].quick, payload)
                    .then(function (fresh) { return fresh; })
                    .catch(function (xhr) {
                        var json = xhr.responseJSON || {};
                        var message = json.message || (json.errors ? Object.values(json.errors)[0][0] : 'Master gagal disimpan.');
                        Swal.showValidationMessage(message);
                    });
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;
            showCinepolisPreview(result.value);
            Swal.fire({ target: target, icon: 'success', title: 'Master tersimpan', text: result.value.message, timer: 1200, showConfirmButton: false });
        });
    }

    function findTicketUuidForCapacity(res, studio, ticketName) {
        var row = (res.preview || []).find(function (item) { return String(item.studio) === String(studio) && (!ticketName || item.type_tiket === ticketName); });
        if (!row) return '';
        var keyPrefix = String(row.studio) + '|' + row.type_tiket + '|';
        var mappingKey = Object.keys(res.row_mappings || {}).find(function (key) { return key.indexOf(keyPrefix) === 0; });
        return mappingKey && res.row_mappings[mappingKey] ? res.row_mappings[mappingKey].ticket_uuid : '';
    }

    $(document).off('click', '.quick-master-action').on('click', '.quick-master-action', function () {
        openQuickMaster($(this).data('resource'), $(this).data('issue'));
    });

    function bindCinepolisConfirm() {
    $('#btn-confirm-cinepolis-import').off('click').on('click', function () {
        var button = $(this);
        var token = button.data('token');
        if (!token) return;
        var swalTarget = document.querySelector('#modal-cinepolis-preview .cinepolis-preview-swal-target');
        var selectedCinemaUuid = button.data('selected-cinema-uuid') || '';
        Swal.fire({
            target: swalTarget,
            title: 'Konfirmasi Import',
            text: 'Data preview akan disimpan ke laporan. Lanjutkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Import',
            cancelButtonText: 'Batal'
        }).then(function (choice) {
            if (!choice.isConfirmed) {
                button.trigger('focus');
                return;
            }
            button.prop('disabled', true);
            $.post(pdfEndpoints[activePdfProvider].confirm, {
                token: token,
                cinema_uuid: selectedCinemaUuid,
                confirm_cinema_mapping: $('#confirm-cinepolis-cinema-mapping').is(':checked') ? 1 : 0
            })
                .done(function (result) {
                    $('#modal-cinepolis-preview').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message }).then(function () { $('#datatable').DataTable().ajax.reload(null, false); });
                })
                .fail(function (xhr) {
                    button.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Import diblokir', text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Import gagal.' });
                });
        });
    });
    }

        if (bioskop !== 'CINEPOLIS PDF') {
            var legacyUrls = {
                'XXI': { preview: @json(route('pelaporan.upload.xxi')), confirm: @json(route('pelaporan.upload.xxi.confirm')), quick: @json(route('pelaporan.upload.xxi.quick-master')) },
                'CGV': { preview: @json(route('pelaporan.upload.cgv')), confirm: @json(route('pelaporan.upload.cgv.confirm')), quick: @json(route('pelaporan.upload.cgv.quick-master')) },
                'SAMS STUDIOS': { preview: @json(route('pelaporan.upload.sams')), confirm: @json(route('pelaporan.upload.sams.confirm')), quick: @json(route('pelaporan.upload.sams.quick-master')) }
            };
            $.ajax({ url: legacyUrls[bioskop].preview, method: 'POST', data: formData, contentType: false, processData: false })
                .done(function (res) {
                    stopDummyProgress(); setProcessingUI(false);
                    openPreviewAfterUploadModal(function () { showLegacyPreview(res, bioskop, legacyUrls[bioskop]); });
                }).fail(function (xhr) {
                    stopDummyProgress(); setProcessingUI(false);
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Preview gagal.';
                    updateProgress(currentPct, 'Gagal', msg); Swal.fire({icon:'error',title:'Preview gagal',text:msg});
                });
            return;
        }

        if(false && bioskop === 'XXI'){
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
        }else if (bioskop === 'SAMS STUDIOS'){
            $.ajax({
            // url: $(this).attr('action'),
            url: '{{route('pelaporan.upload.sams')}}',
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

    var activeLegacyPreview = null;

    function legacyRowForIssue(preview, issue) {
        var rows = preview.preview || [];
        var match = issue.match(/^Film (.+) belum/) || issue.match(/^Bioskop (.+) belum/) || issue.match(/^Tipe tiket (.+) belum/);
        if (match) {
            return rows.find(function (row) {
                return [row.nama_film, row.bioskop, row.ticket_name].some(function (value) { return String(value) === match[1]; });
            }) || null;
        }
        var capacity = issue.match(/^Studio (.+) belum memiliki mapping kapasitas untuk tipe tiket (.+) di bioskop (.+) \(baris (\d+)\)\.$/);
        return capacity ? rows.find(function (row) {
            return String(row.source_row) === capacity[4]
                && String(row.studio) === capacity[1]
                && String(row.ticket_name) === capacity[2]
                && String(row.bioskop) === capacity[3];
        }) || null : null;
    }

    function openLegacyQuickMaster(button) {
        var state = activeLegacyPreview;
        if (!state) return;
        var resource = button.data('resource'), issue = button.data('issue'), issueRow = legacyRowForIssue(state.res, issue) || {};
        var field = resource === 'cinema' ? '<input id="legacy-qm-name" class="swal2-input" value="'+escapeHtml(issueRow.bioskop || '')+'" placeholder="Nama bioskop"><input id="legacy-qm-city" class="swal2-input" value="'+escapeHtml(issueRow.kota || '')+'" placeholder="Kota">' : resource === 'film' ? '<input id="legacy-qm-name" class="swal2-input" value="'+escapeHtml(issueRow.nama_film || '')+'" placeholder="Nama film">' : resource === 'ticket_type' ? '<input id="legacy-qm-name" class="swal2-input" value="'+escapeHtml(issueRow.ticket_name || '')+'" placeholder="Tipe tiket">' : '<input id="legacy-qm-studio" class="swal2-input" value="'+escapeHtml(issueRow.studio || '')+'" placeholder="Studio"><input id="legacy-qm-capacity" type="number" min="0" class="swal2-input" placeholder="Kapasitas">';
        var target = document.querySelector('#modal-legacy-preview .cinepolis-preview-swal-target');
        Swal.fire({target:target,title:'Tambah Master',html:field,showCancelButton:true,confirmButtonText:'Simpan & Periksa Ulang',cancelButtonText:'Batal',showLoaderOnConfirm:true,preConfirm:function(){ var p={token:state.res.token,resource:resource}; if(resource==='cinema'){p.name=$('#legacy-qm-name').val();p.city=$('#legacy-qm-city').val();} else if(resource==='film'||resource==='ticket_type'){p.name=$('#legacy-qm-name').val();} else { var capacityRow=legacyRowForIssue(state.res,issue)||{}; p.source_row=capacityRow.source_row||''; p.ticket_name=capacityRow.ticket_name||''; p.studio=$('#legacy-qm-studio').val() || capacityRow.studio || '';p.kapasitas=$('#legacy-qm-capacity').val(); }
            if ((resource==='capacity' && (!p.source_row || !p.ticket_name || !p.studio || p.kapasitas==='')) || ((resource==='film'||resource==='ticket_type') && !p.name) || (resource==='cinema' && (!p.name || !p.city))) { Swal.showValidationMessage('Lengkapi semua field wajib.'); return false; }
            return $.post(state.urls.quick,p).then(function(response){ if (!response || response.status !== 'success') { throw new Error(response && response.message ? response.message : 'Master gagal disimpan.'); } return response; }).catch(function(xhr){ var json=xhr.responseJSON||{}; var message=json.message||xhr.message||((json.errors&&Object.values(json.errors)[0]) ? Object.values(json.errors)[0][0] : 'Master gagal disimpan.'); Swal.showValidationMessage(message); return false; }); }}).then(function(result){if(result.isConfirmed&&result.value){showLegacyPreview(result.value,state.provider,state.urls);Swal.fire({target:target,icon:'success',title:'Master tersimpan',text:result.value.message,timer:1200,showConfirmButton:false});}});
    }

    function showLegacyPreview(res, provider, urls) {
        activeLegacyPreview = { res: res, provider: provider, urls: urls };
        var summary = res.summary || {};
        $('#legacy-preview-summary').html([
            ['Provider', summary.provider], ['Baris sumber', summary.rows], ['Siap import', summary.ready], ['Diblokir', summary.blocked]
        ].map(function (item) { return '<div class="metric"><span class="label">' + escapeHtml(item[0]) + '</span><span class="value">' + escapeHtml(item[1]) + '</span></div>'; }).join(''));
        var issues = res.blocking_issues || [];
        var actionFor = function (issue) {
            if (issue.indexOf('belum terdaftar sebagai bioskop') !== -1) return ['cinema', 'Tambah Master Bioskop'];
            if (issue.indexOf('belum terdaftar di Master Film') !== -1) return ['film', 'Tambah Master Film'];
            if (issue.indexOf('Tipe tiket ') === 0) return ['ticket_type', 'Tambah Tipe Tiket'];
            if (issue.indexOf('Studio ') === 0) {
                var capacityRow = legacyRowForIssue(res, issue);
                return capacityRow && capacityRow.cinema_uuid && capacityRow.ticket_uuid ? ['capacity', 'Tambah Master Kapasitas'] : null;
            }
            return null;
        };
        $('#legacy-preview-issues').toggleClass('d-none', !issues.length).html(issues.length ? '<strong>Import diblokir:</strong><ul class="cinepolis-preview-issues-list">' + issues.map(function(issue) { var action=actionFor(issue); return '<li><div class="d-flex justify-content-between align-items-center flex-wrap"><span>' + escapeHtml(issue) + '</span>' + (action ? '<button type="button" class="btn btn-sm btn-outline-danger ml-2 mt-1 legacy-quick-master" data-resource="'+action[0]+'" data-issue="'+escapeHtml(issue)+'">'+action[1]+'</button>' : '') + '</div></li>'; }).join('') + '</ul>' : '');
        $('#legacy-preview-issues .legacy-quick-master').off('click').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openLegacyQuickMaster($(this));
        });
        $('#legacy-preview-warnings').toggleClass('d-none', !(res.warnings || []).length).html((res.warnings || []).join('<br>'));
        $('#legacy-preview-table tbody').html((res.preview || []).map(function(row) { return '<tr class="'+(row.mapping_status !== 'Siap' ? 'is-blocked' : '')+'"><td>'+escapeHtml(row.mapping_status)+'</td><td>'+escapeHtml(row.source_row)+'</td><td>'+escapeHtml(row.tgl_tayang)+'</td><td>'+escapeHtml(row.nama_film)+'</td><td>'+escapeHtml(row.bioskop)+'</td><td>'+escapeHtml(row.kota || '-')+'</td><td>'+escapeHtml(row.studio)+'</td><td>'+escapeHtml(row.jam_tayang)+'</td><td>'+escapeHtml(row.show)+'</td><td>'+escapeHtml(row.ticket_name)+'</td><td>'+escapeHtml(row.harga)+'</td><td>'+escapeHtml(row.jumlah)+'</td></tr>'; }).join(''));
        $('#btn-confirm-legacy-import').data('token', res.token).prop('disabled', !res.token || issues.length > 0);
        $('#modal-legacy-preview').modal('show');
    }
    $('#btn-confirm-legacy-import').off('click').on('click', function () { var state=activeLegacyPreview, token=$(this).data('token'), button=$(this); if(!token)return; Swal.fire({target:document.querySelector('#modal-legacy-preview .cinepolis-preview-swal-target'),title:'Konfirmasi Import',text:'Data preview akan disimpan ke laporan. Lanjutkan?',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Import'}).then(function(choice){if(!choice.isConfirmed)return;button.prop('disabled',true);$.post(state.urls.confirm,{token:token}).done(function(result){$('#modal-legacy-preview').modal('hide');Swal.fire({icon:'success',title:'Berhasil',text:result.message}).then(function(){$('#datatable').DataTable().ajax.reload(null,false);});}).fail(function(xhr){button.prop('disabled',false);Swal.fire({icon:'error',title:'Import diblokir',text:(xhr.responseJSON||{}).message||'Import gagal.'});});}); });

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
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
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
            "responsive": false,
            "scrollX": true,
            "autoWidth": false,
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
