@extends('layouts.page')

@section('title', 'Rekap Omset')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<!-- DataTables core -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- Buttons extension -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .finance-waterfall {
        display: none;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        padding: 18px;
        margin: 18px 0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .finance-waterfall__header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .finance-waterfall__header h3 {
        margin: 0;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
    }

    .finance-waterfall__header span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
    }

    .finance-waterfall__badge {
        border-radius: 999px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 11px;
        font-weight: 700;
        padding: 7px 12px;
        white-space: nowrap;
    }

    .finance-waterfall__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .finance-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .finance-kpi {
        border: 1px solid #e5e7eb;
        border-left: 4px solid #2f4558;
        border-radius: 8px;
        padding: 10px 12px;
        background: #f9fafb;
    }

    .finance-kpi small {
        display: block;
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 5px;
    }

    .finance-kpi strong {
        display: block;
        color: #111827;
        font-size: 16px;
        line-height: 1.25;
    }

    .finance-kpi--deduction {
        border-left-color: #991b1b;
    }

    .finance-kpi--final {
        border-left-color: #059669;
        background: #ecfdf5;
    }

    .waterfall-flow {
        display: grid;
        gap: 10px;
    }

    .waterfall-row {
        display: grid;
        grid-template-columns: 150px 1fr 145px;
        gap: 12px;
        align-items: center;
    }

    .waterfall-label {
        color: #374151;
        font-weight: 700;
        font-size: 12px;
    }

    .performance-panel {
        display: none;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #ffffff;
        padding: 18px;
        margin: 18px 0;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }

    .performance-panel__header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .performance-panel__header h3 {
        margin: 0;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
    }

    .performance-panel__header span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
    }

    .performance-panel__badge {
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 700;
        padding: 7px 12px;
        white-space: nowrap;
    }

    .performance-panel .nav-tabs {
        border-bottom-color: #e5e7eb;
        margin-bottom: 14px;
    }

    .performance-panel .nav-tabs .nav-link {
        color: #6b7280;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px 8px 0 0;
    }

    .performance-panel .nav-tabs .nav-link.active {
        color: #111827;
        border-color: #e5e7eb #e5e7eb #ffffff;
    }

    .detail-export-dropdown {
        position: absolute;
        z-index: 2050;
        display: none;
        width: 210px;
        padding: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(31, 41, 55, 0.16);
    }

    .detail-export-dropdown__label {
        display: block;
        padding: 5px 9px 7px;
        color: #6b7280;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .detail-export-dropdown__item {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 9px;
        padding: 8px 9px;
        border: 0;
        border-radius: 7px;
        color: #374151;
        background: transparent;
        font-size: 12px;
        font-weight: 700;
        text-align: left;
    }

    .detail-export-dropdown__item:hover,
    .detail-export-dropdown__item:focus {
        color: #26225e;
        background: #f3f2ff;
        outline: none;
    }

    .detail-export-dropdown__item i {
        width: 16px;
        font-size: 14px;
        text-align: center;
    }

    .detail-export-dropdown__item--excel i {
        color: #198754;
    }

    .detail-export-dropdown__item--pdf i {
        color: #dc3545;
    }

    #report-tabs .dataTables_scrollBody {
        border-bottom: 1px solid #e5e7eb;
    }

    #report-tabs table.dataTable th,
    #report-tabs table.dataTable td {
        white-space: nowrap;
    }

    .waterfall-track {
        height: 26px;
        border-radius: 999px;
        background: #f3f4f6;
        overflow: hidden;
    }

    .waterfall-bar {
        min-width: 2px;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #2f4558, #4f6f8b);
    }

    .waterfall-bar--deduction {
        background: linear-gradient(90deg, #991b1b, #ef4444);
    }

    .waterfall-bar--net {
        background: linear-gradient(90deg, #2563eb, #60a5fa);
    }

    .waterfall-bar--final {
        background: linear-gradient(90deg, #047857, #34d399);
    }

    .waterfall-value {
        color: #111827;
        font-weight: 700;
        font-size: 12px;
        text-align: right;
    }

    .finance-waterfall__note {
        margin: 14px 0 0;
        color: #6b7280;
        font-size: 12px;
    }

    @media (max-width: 992px) {
        .finance-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .waterfall-row {
            grid-template-columns: 1fr;
            gap: 5px;
        }

        .waterfall-value {
            text-align: left;
        }

        .finance-waterfall__actions {
            justify-content: flex-start;
        }
    }
</style>

@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-users'></i> Module: <span class='fw-300'>Rekap Omset </span>
        <small>
            Modul untul Rekap Omset.
        </small>
    </h1>
</div>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>
                    Rekap Omset  <span class="fw-300"><i>List</i></span>
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
                            <div class="form-group col-md-3 mb-3">
                                {{ Form::label('kota','Kota',['class' => 'required form-label'])}}
                                {!! Form::select('kota', $kota, '',
                                ['id'=>'kota','class'
                                => 'custom-select'.($errors->has('kota') ? 'is-invalid':'') ,'required'
                                => '', 'placeholder' => 'Pilih Kota ...'])!!}
                                @if ($errors->has('kota'))
                                <div class="invalid-feedback">{{ $errors->first('kota') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3 mb-3">
                                {{ Form::label('nama_bioskop','Nama Bioskop',['class' => 'required form-label'])}}
                                {!! Form::select('nama_bioskop', $nama_bioskop, '',
                                ['id'=>'nama_bioskop','class'
                                => 'custom-select'.($errors->has('nama_bioskop') ? 'is-invalid':'') ,'required'
                                => '', 'placeholder' => 'Pilih Nama Bioskop ...'])!!}
                                @if ($errors->has('nama_bioskop'))
                                <div class="invalid-feedback">{{ $errors->first('nama_bioskop') }}</div>
                                @endif
                            </div>
                            <div class="form-group col-md-3 mb-3">
                                {{ Form::label('type_tiket','Tipe Tiket',['class' => 'required form-label'])}}
                                {!! Form::select('type_tiket', $type_tiket, '',
                                ['id'=>'type_tiket','class'
                                => 'custom-select'.($errors->has('type_tiket') ? 'is-invalid':'') ,'required'
                                => '', 'placeholder' => 'Pilih Tipe Tiket ...'])!!}
                                @if ($errors->has('type_tiket'))
                                <div class="invalid-feedback">{{ $errors->first('type_tiket') }}</div>
                                @endif
                            </div>
                            <div class="form-group col-md-1 mb-3 filter-search-column">
                                <button type="button" id="search-btn" class="btn btn-primary w-100 filter-search-btn" title="Tampilkan Rekap Omset" aria-label="Tampilkan Rekap Omset">
                                    <i class="fal fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <section id="revenue-waterfall" class="finance-waterfall">
                        <div class="finance-waterfall__header">
                            <div>
                                <h3>Revenue Waterfall</h3>
                                <span>Alur perhitungan dari Gross Box Office sampai estimasi Total PH.</span>
                            </div>
                            <div class="finance-waterfall__actions">
                                <button type="button" id="download-waterfall-pdf" class="btn btn-danger btn-sm" style="display:none">
                                    <i class="fal fa-file-pdf mr-1"></i> Download PDF
                                </button>
                                <div class="finance-waterfall__badge">Finance View</div>
                            </div>
                        </div>
                        <div class="finance-kpi-grid">
                            <div class="finance-kpi">
                                <small>Gross Box Office</small>
                                <strong id="wf-gross">0.00</strong>
                            </div>
                            <div class="finance-kpi">
                                <small>Average Ticket Price</small>
                                <strong id="wf-atp">0.00</strong>
                            </div>
                            <div class="finance-kpi">
                                <small>Occupancy Rate</small>
                                <strong id="wf-occupancy">0.00%</strong>
                            </div>
                            <div class="finance-kpi">
                                <small>Effective Tax Rate</small>
                                <strong id="wf-etr">0.00%</strong>
                            </div>
                            <div class="finance-kpi finance-kpi--deduction">
                                <small>Pajak</small>
                                <strong id="wf-tax">0.00</strong>
                            </div>
                            <div class="finance-kpi">
                                <small>Net Box Office</small>
                                <strong id="wf-net">0.00</strong>
                            </div>
                            <div class="finance-kpi finance-kpi--final">
                                <small>Total PH</small>
                                <strong id="wf-total">0.00</strong>
                            </div>
                        </div>
                        <div id="waterfall-flow" class="waterfall-flow"></div>
                        <p class="finance-waterfall__note">
                            Formula: Occupancy = Penonton / Kapasitas Tersedia; Gross - Pajak = Net; Net - Share 50% - Royalty 1.5% dari share = Total PH.
                        </p>
                    </section>
                    <section id="report-tabs" class="performance-panel">
                        <div class="performance-panel__header">
                            <div>
                                <h3>Report Tables</h3>
                                <span>Pilih tampilan Omset atau Cinema Leaderboard sesuai kebutuhan analisa.</span>
                            </div>
                            <div class="performance-panel__badge">Accounting View</div>
                        </div>
                        <ul class="nav nav-tabs" id="laporan-report-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="omset-tab" data-toggle="tab" href="#omset-pane" role="tab" aria-controls="omset-pane" aria-selected="true">Omset</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="leaderboard-tab" data-toggle="tab" href="#leaderboard-pane" role="tab" aria-controls="leaderboard-pane" aria-selected="false">Cinema Leaderboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="province-tab" data-toggle="tab" href="#province-pane" role="tab" aria-controls="province-pane" aria-selected="false">Provinsi Leaderboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="audit-tab" data-toggle="tab" href="#audit-pane" role="tab" aria-controls="audit-pane" aria-selected="false">Audit Checks</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="laporan-report-tabs-content">
                            <div class="tab-pane fade show active" id="omset-pane" role="tabpanel" aria-labelledby="omset-tab">
                                <div id="data-summary">
                                    {{-- <h4>📊 List 20 Besar Kota Dengan Penonton Tertinggi</h4> --}}
                                    <div id="detail-export-dropdown" class="detail-export-dropdown" role="menu" aria-label="Pilihan export detail">
                                        <span class="detail-export-dropdown__label">Pilih format</span>
                                        <button type="button" id="detail-export-excel" class="detail-export-dropdown__item detail-export-dropdown__item--excel" role="menuitem">
                                            <i class="fa fa-file-excel"></i> Excel Detail
                                        </button>
                                        <button type="button" id="detail-export-pdf" class="detail-export-dropdown__item detail-export-dropdown__item--pdf" role="menuitem">
                                            <i class="fa fa-file-pdf"></i> PDF Detail
                                        </button>
                                    </div>
                                    <table id="summary-table" class="table table-bordered table-hover table-striped w-100">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Kategori</th>
                                                <th>Jumlah Penonton</th>
                                                <th>Kapasitas Tersedia</th>
                                                <th>Occupancy Rate</th>
                                                <th>Total Pendapatan</th>
                                                <th>ATP</th>
                                                <th>Effective Tax Rate</th>
                                                {{-- <th>Pajak</th> --}}
                                                <th>Net</th>
                                                <th>Share 50%</th>
                                                <th>Royalty (1.5%)</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th>Total</th>
                                                <th></th>
                                                <th id="total-summary-penonton"></th>
                                                <th id="total-summary-seats"></th>
                                                <th id="total-summary-occupancy"></th>
                                                <th id="total-summary-gross"></th>
                                                <th id="total-summary-atp"></th>
                                                <th id="total-summary-etr"></th>
                                                {{-- <th id="total-summary-tax"></th> --}}
                                                <th id="total-summary-net"></th>
                                                <th id="total-summary-share"></th>
                                                <th></th>
                                                <th id="total-summary-total"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="leaderboard-pane" role="tabpanel" aria-labelledby="leaderboard-tab">
                                <table id="performance-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Kota</th>
                                            <th>Nama Bioskop</th>
                                            <th>Penonton</th>
                                            <th>Kapasitas Tersedia</th>
                                            <th>Occupancy</th>
                                            <th>Gross</th>
                                            <th>ATP</th>
                                            <th>Net</th>
                                            <th>Total PH</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th></th>
                                            <th></th>
                                            <th id="total-performance-penonton"></th>
                                            <th id="total-performance-seats"></th>
                                            <th id="total-performance-occupancy"></th>
                                            <th id="total-performance-gross"></th>
                                            <th id="total-performance-atp"></th>
                                            <th id="total-performance-net"></th>
                                            <th id="total-performance-ph"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="province-pane" role="tabpanel" aria-labelledby="province-tab">
                                <table id="province-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Provinsi</th>
                                            <th>Kota Covered</th>
                                            <th>Bioskop</th>
                                            <th>Penonton</th>
                                            <th>Kapasitas Tersedia</th>
                                            <th>Occupancy</th>
                                            <th>Gross</th>
                                            <th>ATP</th>
                                            <th>Effective Tax Rate</th>
                                            <th>Net</th>
                                            <th>Total PH</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th></th>
                                            <th id="total-province-city"></th>
                                            <th id="total-province-cinema"></th>
                                            <th id="total-province-penonton"></th>
                                            <th id="total-province-seats"></th>
                                            <th id="total-province-occupancy"></th>
                                            <th id="total-province-gross"></th>
                                            <th id="total-province-atp"></th>
                                            <th id="total-province-etr"></th>
                                            <th id="total-province-net"></th>
                                            <th id="total-province-ph"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="audit-pane" role="tabpanel" aria-labelledby="audit-tab">
                                <table id="audit-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Issue</th>
                                            <th>Tanggal</th>
                                            <th>Kategori</th>
                                            <th>Kota</th>
                                            <th>Nama Bioskop</th>
                                            <th>Studio</th>
                                            <th>Show</th>
                                            <th>Tipe Tiket</th>
                                            <th>Penonton</th>
                                            <th>Kapasitas</th>
                                            <th>Harga</th>
                                            <th>Gross</th>
                                            <th>Expected Gross</th>
                                            <th>Selisih</th>
                                            <th>Pajak</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </section>
                    <hr style="border: 1px dashed: color: black">
                    <!-- datatable start -->
                    {{-- <table id="datatable" class="table table-bordered table-hover table-striped w-100" style="display: none">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kategori Bioskop</th>
                                <th>Kota</th>
                                <th>Nama Bioskop</th>
                                <th>Nama Film</th>
                                <th>Show</th>
                                <th>Jam Tayang</th>
                                <th>Tipe Tiket</th>
                                <th>Harga (/pcs)</th>
                                <th>Total Tiket</th>
                                <th>Gross</th>
                                <th>Tax</th>
                                <th>Net</th>
                                </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="9">Total</th>
                                <th id="total-tiket"></th>
                                <th id="total-gross"></th>
                                <th id="total-tax"></th>
                                <th id="total-net"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table> --}}
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script>
    var sinemakuLogo = @json(file_exists(public_path('img/sinemaku.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/sinemaku.png'))) : null);
    var latestRevenueWaterfall = null;
    var currentDetailExportParams = null;
    var currentDetailExportFilters = null;
    var currentDetailExportButton = null;

    function hideDetailExportDropdown() {
        $('#detail-export-dropdown').stop(true, true).fadeOut(100);
        if (currentDetailExportButton) {
            $(currentDetailExportButton).attr('aria-expanded', 'false');
        }
        currentDetailExportButton = null;
    }

    function toggleDetailExportDropdown(buttonNode) {
        var $dropdown = $('#detail-export-dropdown');
        var $button = $(buttonNode);

        if ($dropdown.is(':visible') && currentDetailExportButton === buttonNode) {
            hideDetailExportDropdown();
            return;
        }

        var buttonOffset = $button.offset();
        var dropdownWidth = $dropdown.outerWidth() || 210;
        var left = buttonOffset.left;
        var maxLeft = $(window).scrollLeft() + $(window).width() - dropdownWidth - 12;

        currentDetailExportButton = buttonNode;
        $button.attr('aria-expanded', 'true');
        $dropdown
            .css({
                top: buttonOffset.top + $button.outerHeight() + 6,
                left: Math.max($(window).scrollLeft() + 12, Math.min(left, maxLeft))
            })
            .stop(true, true)
            .fadeIn(120);
    }

    $(document).ready(function(){
        $('#detail-export-dropdown').appendTo(document.body);

        $('#nama_film').select2();
        $('#bioskop_kategori').select2();
        $('#kota').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();

        $('#nama_film').on('change.rekapFilterDefaults', function () {
            if (!$(this).val()) {
                return;
            }

            // Setiap pemilihan film memulai filter turunannya dari cakupan penuh.
            // Trigger change pada kategori tetap menjalankan pemuatan ulang opsi
            // kota, bioskop, dan tipe tiket yang sudah ada.
            $('#bioskop_kategori').val('ALL').trigger('change');
            $('#kota, #nama_bioskop, #type_tiket').val('ALL').trigger('change.select2');
        });

        $('#download-waterfall-pdf').on('click', function () {
            downloadRevenueWaterfallPdf();
        });

        $('#detail-export-excel').on('click', function () {
            if (!currentDetailExportParams) {
                alert('Silakan jalankan filter terlebih dahulu.');
                return;
            }

            hideDetailExportDropdown();
            window.location.href = '{{ route('laporan.detailExport') }}' + '?' + $.param(currentDetailExportParams);
        });

        $('#detail-export-pdf').on('click', function () {
            if (!currentDetailExportParams || !currentDetailExportFilters) {
                alert('Silakan jalankan filter terlebih dahulu.');
                return;
            }

            hideDetailExportDropdown();
            downloadDetailPdf(currentDetailExportParams, currentDetailExportFilters, this);
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#detail-export-dropdown, .detail-export-trigger').length) {
                hideDetailExportDropdown();
            }
        });

        $(window).on('resize scroll', function () {
            hideDetailExportDropdown();
        });

        $('#bioskop_kategori').change(function(){
            var kategori = $(this).val();

            $.ajax({
                url: "{{ route('ref.city') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                },
                success: function(data) {
                    $("#kota").empty();

                    $("#kota").append('<option value="ALL">Semua ...</option>');

                    $.each(data, function(key, value) {
                        $("#kota").append('<option value="' + key + '">' + value + '</option>');
                    });

                    $("#nama_bioskop").empty();

                    $("#nama_bioskop").append('<option value="ALL">Semua ...</option>');
                    $("#kota, #nama_bioskop").val('ALL').trigger('change.select2');
                }
            });

            $.ajax({
                url: "{{ route('ref.type') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                },
                success: function(data) {
                    $("#type_tiket").empty();

                    $("#type_tiket").append('<option value="ALL">Semua ...</option>');

                    $.each(data, function(key, value) {
                        $("#type_tiket").append('<option value="' + key + '">' + value + '</option>');
                    });

                    $("#type_tiket").val('ALL').trigger('change.select2');
                }
            });
        });

        $('#kota').change(function(){
            var kategori = $('#bioskop_kategori').val();
            var kota = $(this).val();

            $.ajax({
                url: "{{ route('ref.cinema') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                    kota: kota
                },
                success: function(data) {
                    $("#nama_bioskop").empty();

                    $("#nama_bioskop").append('<option value="ALL">Semua ...</option>');

                    $.each(data, function(key, value) {
                        $("#nama_bioskop").append('<option value="' + key + '">' + value + '</option>');
                    });

                    $("#nama_bioskop").val('ALL').trigger('change.select2');
                }
            });
        });

        $(document).delegate("#search-btn", "click", function (event) {
            event.preventDefault();

            var nama_film = $('#nama_film').val();
            var tgl_mulai = $('#tanggal_mulai').val();
            var tgl_akhir = $('#tanggal_akhir').val();
            var bioskop_kategori = $('#bioskop_kategori').val();
            var kota = $('#kota').val();
            var nama_bioskop = $('#nama_bioskop').val();
            var type_tiket = $('#type_tiket').val();

            function selectedText(selector) {
                var text = $(selector).find('option:selected').text();
                return text && text.trim() ? text.trim() : '-';
            }

            function reportPeriod() {
                if (tgl_mulai && tgl_akhir) {
                    return tgl_mulai + ' s/d ' + tgl_akhir;
                }

                return 'Semua Periode';
            }

            var detailExportParams = {
                nama_film: nama_film,
                tgl_mulai: tgl_mulai,
                tgl_akhir: tgl_akhir,
                bioskop_kategori: bioskop_kategori,
                kota: kota,
                nama_bioskop: nama_bioskop,
                type_tiket: type_tiket
            };

            var detailExportFilters = {
                namaFilm: selectedText('#nama_film'),
                periode: reportPeriod(),
                kategori: selectedText('#bioskop_kategori'),
                kota: selectedText('#kota'),
                bioskop: selectedText('#nama_bioskop'),
                tipeTiket: selectedText('#type_tiket')
            };

            currentDetailExportParams = detailExportParams;
            currentDetailExportFilters = detailExportFilters;
            hideDetailExportDropdown();

            if ($.fn.DataTable.isDataTable("#datatable")) {
                $('#datatable').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#summary-table")) {
                $('#summary-table').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#performance-table")) {
                $('#performance-table').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#province-table")) {
                $('#province-table').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#audit-table")) {
                $('#audit-table').DataTable().destroy();
            }

            $('#revenue-waterfall').hide();
            $('#waterfall-flow').empty();
            $('#download-waterfall-pdf').hide();
            latestRevenueWaterfall = null;
            $('#report-tabs').hide();
            if ($.fn.tab) {
                $('#omset-tab').tab('show');
            }

            // var table = $('#datatable').DataTable({
            //     "processing": true,
            //     "serverSide": true,
            //     "responsive": true,
            //     "order": [[ 0, "asc" ]],
            //     "ajax":{
            //         url:'{{route('laporan.search')}}',
            //         type : "GET",
            //         data: {
            //             nama_film: nama_film,
            //             tgl_mulai : tgl_mulai,
            //             tgl_akhir : tgl_akhir,
            //             bioskop_kategori : bioskop_kategori,
            //             kota : kota,
            //             nama_bioskop : nama_bioskop,
            //             type_tiket : type_tiket,
            //         }
            //     },
            //         "columns": [
            //         {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            //         {data: 'tgl_tayang', name: 'tgl_tayang'},
            //         {data: 'kategori', name: 'kategori'},
            //         {data: 'kota', name: 'kota'},
            //         {data: 'nama_bioskop', name: 'nama_bioskop'},
            //         {data: 'nama_film', name: 'nama_film'},
            //         {data: 'show', name: 'show'},
            //         {data: 'jam_tayang', name: 'jam_tayang'},
            //         {data: 'type_tiket', name: 'type_tiket'},
            //         {data: 'harga', name: 'harga'},
            //         {data: 'jumlah', name: 'jumlah'},
            //         {data: 'gross', name: 'gross'},
            //         {data: 'tax', name: 'tax'},
            //         {data: 'net', name: 'net'}
            //     ],
            //     "footerCallback": function (row, data, start, end, display) {
            //         var api = this.api();
                    
            //         var totalTiket = api.column(10).data().reduce(function (a, b) {
            //             var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
            //             var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
            //             return valA + valB;
            //         }, 0);
            
            //         // Total Gross
            //         var totalGross = api.column(11).data().reduce(function (a, b) {
            //             var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
            //             var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
            //             return valA + valB;
            //         }, 0);

            //         // Total Tax
            //         var totalTax = api.column(12).data().reduce(function (a, b) {
            //             var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
            //             var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
            //             return valA + valB;
            //         }, 0);

            //         // Total Net
            //         var totalNet = api.column(13).data().reduce(function (a, b) {
            //             var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
            //             var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
            //             return valA + valB;
            //         }, 0);

            //         // Update footer
            //         $(api.column(10).footer()).html(formatCurrency(totalTiket));
            //         $(api.column(11).footer()).html(formatCurrency(totalGross));
            //         $(api.column(12).footer()).html(formatCurrency(totalTax));
            //         $(api.column(13).footer()).html(formatCurrency(totalNet));
            //     }
            // });

            $('#datatable').show();
            $('#summary-table').show();
            $('#report-tabs').show();

            var tablePerformance = $('#performance-table').DataTable({
                "processing": true,
                "serverSide": false,
                "paging": true,
                "pageLength": 10,
                "lengthChange": false,
                "searching": false,
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
                "order": [],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Excel Ranking',
                        className: 'btn btn-success btn-sm me-2 rounded-pill',
                        title: 'Performance Ranking',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> PDF Ranking',
                        className: 'btn btn-danger btn-sm rounded-pill',
                        title: 'Performance Ranking',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        footer: true,
                        exportOptions: { columns: ':visible' }
                    }
                ],
                "ajax":{
                    url:'{{route('laporan.performance')}}',
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
                    {data: 'kota', name: 'kota'},
                    {data: 'nama_bioskop', name: 'nama_bioskop'},
                    {data: 'jumlah', name: 'jumlah'},
                    {data: 'seats_available', name: 'seats_available'},
                    {data: 'occupancy_rate', name: 'occupancy_rate'},
                    {data: 'gross', name: 'gross'},
                    {data: 'atp', name: 'atp'},
                    {data: 'net', name: 'net'},
                    {data: 'total_ph', name: 'total_ph'},
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();

                    var totalPenonton = data.reduce(function (total, item) {
                        return total + parseNumber(item.jumlah);
                    }, 0);

                    var totalSeats = data.reduce(function (total, item) {
                        return total + parseNumber(item.seats_available);
                    }, 0);

                    var totalGross = data.reduce(function (total, item) {
                        return total + parseNumber(item.gross);
                    }, 0);

                    var totalNet = data.reduce(function (total, item) {
                        return total + parseNumber(item.net);
                    }, 0);

                    var totalPh = data.reduce(function (total, item) {
                        return total + parseNumber(item.total_ph);
                    }, 0);

                    var occupancyRate = totalSeats ? (totalPenonton / totalSeats) * 100 : 0;
                    var totalAtp = totalPenonton ? totalGross / totalPenonton : 0;

                    $(api.column(3).footer()).html(formatCurrency(totalPenonton));
                    $(api.column(4).footer()).html(formatCurrency(totalSeats));
                    $(api.column(5).footer()).html(formatPercent(occupancyRate));
                    $(api.column(6).footer()).html(formatCurrency(totalGross));
                    $(api.column(7).footer()).html(formatCurrency(totalAtp));
                    $(api.column(8).footer()).html(formatCurrency(totalNet));
                    $(api.column(9).footer()).html(formatCurrency(totalPh));
                },
                "initComplete": function () {
                    var performanceApi = this.api();
                    if ($('#leaderboard-pane').hasClass('active')) {
                        performanceApi.columns.adjust();
                    }
                }
            });

            var tableProvince = $('#province-table').DataTable({
                "processing": true,
                "serverSide": false,
                "paging": true,
                "pageLength": 10,
                "lengthChange": false,
                "searching": false,
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
                "order": [],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Excel Provinsi',
                        className: 'btn btn-success btn-sm me-2 rounded-pill',
                        title: 'Provinsi Leaderboard',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> PDF Provinsi',
                        className: 'btn btn-danger btn-sm rounded-pill',
                        title: 'Provinsi Leaderboard',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        footer: true,
                        exportOptions: { columns: ':visible' }
                    }
                ],
                "ajax":{
                    url:'{{route('laporan.province')}}',
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
                    {data: 'provinsi', name: 'provinsi'},
                    {data: 'city_count', name: 'city_count'},
                    {data: 'cinema_count', name: 'cinema_count'},
                    {data: 'jumlah', name: 'jumlah'},
                    {data: 'seats_available', name: 'seats_available'},
                    {data: 'occupancy_rate', name: 'occupancy_rate'},
                    {data: 'gross', name: 'gross'},
                    {data: 'atp', name: 'atp'},
                    {data: 'effective_tax_rate', name: 'effective_tax_rate'},
                    {data: 'net', name: 'net'},
                    {data: 'total_ph', name: 'total_ph'},
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();

                    var totalCity = data.reduce(function (total, item) {
                        return total + parseNumber(item.city_count);
                    }, 0);

                    var totalCinema = data.reduce(function (total, item) {
                        return total + parseNumber(item.cinema_count);
                    }, 0);

                    var totalPenonton = data.reduce(function (total, item) {
                        return total + parseNumber(item.jumlah);
                    }, 0);

                    var totalSeats = data.reduce(function (total, item) {
                        return total + parseNumber(item.seats_available);
                    }, 0);

                    var totalGross = data.reduce(function (total, item) {
                        return total + parseNumber(item.gross);
                    }, 0);

                    var totalTax = data.reduce(function (total, item) {
                        return total + parseNumber(item.tax);
                    }, 0);

                    var totalNet = data.reduce(function (total, item) {
                        return total + parseNumber(item.net);
                    }, 0);

                    var totalPh = data.reduce(function (total, item) {
                        return total + parseNumber(item.total_ph);
                    }, 0);

                    var occupancyRate = totalSeats ? (totalPenonton / totalSeats) * 100 : 0;
                    var totalAtp = totalPenonton ? totalGross / totalPenonton : 0;
                    var effectiveTaxRate = totalGross ? (totalTax / totalGross) * 100 : 0;

                    $(api.column(2).footer()).html(formatNumber(totalCity));
                    $(api.column(3).footer()).html(formatNumber(totalCinema));
                    $(api.column(4).footer()).html(formatNumber(totalPenonton));
                    $(api.column(5).footer()).html(formatNumber(totalSeats));
                    $(api.column(6).footer()).html(formatPercent(occupancyRate));
                    $(api.column(7).footer()).html(formatCurrency(totalGross));
                    $(api.column(8).footer()).html(formatCurrency(totalAtp));
                    $(api.column(9).footer()).html(formatPercent(effectiveTaxRate));
                    $(api.column(10).footer()).html(formatCurrency(totalNet));
                    $(api.column(11).footer()).html(formatCurrency(totalPh));
                },
                "initComplete": function () {
                    var provinceApi = this.api();
                    if ($('#province-pane').hasClass('active')) {
                        provinceApi.columns.adjust();
                    }
                }
            });

            var tableAudit = $('#audit-table').DataTable({
                "processing": true,
                "serverSide": false,
                "paging": true,
                "pageLength": 10,
                "lengthChange": false,
                "searching": false,
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
                "order": [],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Excel Audit',
                        className: 'btn btn-success btn-sm me-2 rounded-pill',
                        title: 'Audit Checks',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> PDF Audit',
                        className: 'btn btn-danger btn-sm rounded-pill',
                        title: 'Audit Checks',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: { columns: ':visible' }
                    }
                ],
                "ajax":{
                    url:'{{route('laporan.audit')}}',
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
                    {data: 'issue', name: 'issue'},
                    {data: 'tgl_tayang', name: 'tgl_tayang'},
                    {data: 'kategori', name: 'kategori'},
                    {data: 'kota', name: 'kota'},
                    {data: 'nama_bioskop', name: 'nama_bioskop'},
                    {data: 'studio', name: 'studio'},
                    {data: 'show', name: 'show'},
                    {data: 'type_tiket', name: 'type_tiket'},
                    {data: 'jumlah', name: 'jumlah'},
                    {data: 'kapasitas', name: 'kapasitas'},
                    {data: 'harga', name: 'harga'},
                    {data: 'gross', name: 'gross'},
                    {data: 'expected_gross', name: 'expected_gross'},
                    {data: 'selisih', name: 'selisih'},
                    {data: 'pajak', name: 'pajak'},
                ],
                "initComplete": function () {
                    var auditApi = this.api();
                    if ($('#audit-pane').hasClass('active')) {
                        auditApi.columns.adjust();
                    }
                }
            });

            var tableSummary = $('#summary-table').DataTable({
                "processing": true,
                "serverSide": false,
                "paging": false,
                "responsive": false,
                "scrollX": true,
                "autoWidth": false,
                "order": [[ 0, "asc" ]],
                dom: 'Bfrtip', // <== Tambahkan ini agar tombol tampil
                buttons: [
                    {
                        text: '<i class="fa fa-download"></i> Detail <i class="fa fa-caret-down ml-1"></i>',
                        className: 'btn btn-info btn-sm rounded-pill detail-export-trigger',
                        attr: {
                            'aria-haspopup': 'true',
                            'aria-expanded': 'false'
                        },
                        action: function (event, dataTable, buttonNode) {
                            event.stopPropagation();
                            toggleDetailExportDropdown(buttonNode[0]);
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm me-2 rounded-pill',
                        title: 'Laporan Summary',
                        exportOptions: { columns: ':visible' }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm rounded-pill',
                        title: 'Laporan Summary',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        footer: true,
                        exportOptions: { columns: ':visible' },
                        customize: function (doc) {
                            var generatedAt = new Date().toLocaleString('id-ID', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            var tableNode = doc.content.find(function (node) {
                                return node.table;
                            });

                            if (doc.content.length && doc.content[0].text === 'Laporan Summary') {
                                doc.content.splice(0, 1);
                            }

                            doc.pageMargins = [30, 96, 30, 52];
                            doc.defaultStyle = {
                                fontSize: 8,
                                color: '#1f2937'
                            };

                            doc.header = function () {
                                return {
                                    margin: [30, 22, 30, 0],
                                    stack: [
                                        {
                                            columns: [
                                                sinemakuLogo ? {
                                                    image: sinemakuLogo,
                                                    width: 48,
                                                    margin: [0, 0, 12, 0]
                                                } : {
                                                    text: 'SINEMAKU',
                                                    bold: true,
                                                    color: '#b91c1c',
                                                    fontSize: 16,
                                                    margin: [0, 10, 12, 0]
                                                },
                                                {
                                                    width: '*',
                                                    stack: [
                                                        { text: 'SINEMAKU PICTURES', style: 'companyName' },
                                                        { text: 'Laporan Rekap Omset', style: 'reportTitle' },
                                                        { text: 'Generated: ' + generatedAt, style: 'mutedText' }
                                                    ]
                                                },
                                                {
                                                    width: 190,
                                                    stack: [
                                                        { text: 'ACCOUNTING REPORT', style: 'reportBadge' },
                                                        { text: 'Summary by Cinema Category', alignment: 'right', style: 'mutedText', margin: [0, 6, 0, 0] }
                                                    ]
                                                }
                                            ]
                                        },
                                        {
                                            canvas: [
                                                { type: 'line', x1: 0, y1: 12, x2: 782, y2: 12, lineWidth: 1.2, lineColor: '#991b1b' },
                                                { type: 'line', x1: 0, y1: 15, x2: 782, y2: 15, lineWidth: 0.4, lineColor: '#d1d5db' }
                                            ]
                                        }
                                    ]
                                };
                            };

                            doc.footer = function (currentPage, pageCount) {
                                return {
                                    margin: [30, 0, 30, 18],
                                    columns: [
                                        { text: 'Sinemaku Pictures - Confidential', style: 'footerText' },
                                        { text: 'Halaman ' + currentPage + ' dari ' + pageCount, alignment: 'right', style: 'footerText' }
                                    ]
                                };
                            };

                            doc.content.unshift({
                                margin: [0, 0, 0, 14],
                                table: {
                                    widths: ['*', '*', '*'],
                                    body: [
                                        [
                                            { text: 'Nama Film\n' + selectedText('#nama_film'), style: 'filterBox' },
                                            { text: 'Periode\n' + reportPeriod(), style: 'filterBox' },
                                            { text: 'Kategori Bioskop\n' + selectedText('#bioskop_kategori'), style: 'filterBox' }
                                        ],
                                        [
                                            { text: 'Kota\n' + selectedText('#kota'), style: 'filterBox' },
                                            { text: 'Nama Bioskop\n' + selectedText('#nama_bioskop'), style: 'filterBox' },
                                            { text: 'Tipe Tiket\n' + selectedText('#type_tiket'), style: 'filterBox' }
                                        ]
                                    ]
                                },
                                layout: {
                                    hLineColor: function () { return '#e5e7eb'; },
                                    vLineColor: function () { return '#e5e7eb'; },
                                    fillColor: function () { return '#f9fafb'; },
                                    paddingLeft: function () { return 8; },
                                    paddingRight: function () { return 8; },
                                    paddingTop: function () { return 6; },
                                    paddingBottom: function () { return 6; }
                                }
                            });

                            if (tableNode) {
                                tableNode.margin = [0, 0, 0, 0];
                                tableNode.table.widths = [22, '*', 48, 54, 48, 58, 44, 48, 58, 56, 48, 58];
                                tableNode.layout = {
                                    hLineWidth: function (i, node) {
                                        return (i === 0 || i === 1 || i === node.table.body.length) ? 0.8 : 0.35;
                                    },
                                    vLineWidth: function () { return 0.35; },
                                    hLineColor: function (i) {
                                        return i === 1 ? '#991b1b' : '#d1d5db';
                                    },
                                    vLineColor: function () { return '#e5e7eb'; },
                                    fillColor: function (rowIndex, node) {
                                        if (rowIndex === 0) {
                                            return '#2f4558';
                                        }

                                        if (rowIndex === node.table.body.length - 1) {
                                            return '#2f4558';
                                        }

                                        return rowIndex % 2 === 0 ? '#f9fafb' : null;
                                    },
                                    paddingLeft: function () { return 6; },
                                    paddingRight: function () { return 6; },
                                    paddingTop: function () { return 5; },
                                    paddingBottom: function () { return 5; }
                                };

                                tableNode.table.body.forEach(function (row, rowIndex) {
                                    row.forEach(function (cell, columnIndex) {
                                        var cellObject = typeof cell === 'object' ? cell : { text: cell };

                                        if (rowIndex === 0) {
                                            cellObject.color = '#ffffff';
                                            cellObject.bold = true;
                                            cellObject.fillColor = '#2f4558';
                                            cellObject.alignment = columnIndex === 1 ? 'left' : 'center';
                                            cellObject.fontSize = 8;
                                        } else {
                                            cellObject.fontSize = 8;
                                            cellObject.alignment = columnIndex === 1 ? 'left' : ((columnIndex === 4 || columnIndex === 7 || columnIndex === 10) ? 'center' : 'right');

                                            if (rowIndex === tableNode.table.body.length - 1) {
                                                cellObject.bold = true;
                                                cellObject.color = '#ffffff';
                                                cellObject.fillColor = '#2f4558';
                                            }
                                        }

                                        row[columnIndex] = cellObject;
                                    });
                                });
                            }

                            doc.styles = $.extend(true, {}, doc.styles, {
                                companyName: {
                                    fontSize: 15,
                                    bold: true,
                                    color: '#111827'
                                },
                                reportTitle: {
                                    fontSize: 10,
                                    bold: true,
                                    color: '#991b1b',
                                    margin: [0, 2, 0, 1]
                                },
                                reportBadge: {
                                    alignment: 'right',
                                    color: '#991b1b',
                                    bold: true,
                                    fontSize: 9
                                },
                                mutedText: {
                                    fontSize: 7,
                                    color: '#6b7280'
                                },
                                filterBox: {
                                    fontSize: 8,
                                    color: '#374151',
                                    lineHeight: 1.25
                                },
                                footerText: {
                                    fontSize: 7,
                                    color: '#6b7280'
                                }
                            });
                        }
                    }
                ],
                "ajax":{
                    url:'{{route('laporan.summary')}}',
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
                    {data: 'kategori', name: 'kategori'},
                    {data: 'jumlah', name: 'jumlah'},
                    {data: 'seats_available', name: 'seats_available'},
                    {data: 'occupancy_rate', name: 'occupancy_rate'},
                    {data: 'gross', name: 'gross'},
                    {data: 'atp', name: 'atp'},
                    {data: 'effective_tax_rate', name: 'effective_tax_rate'},
                    // {data: 'tax', name: 'tax'},
                    {data: 'net', name: 'net'},
                    {data: 'share', name: 'share'},
                    {data: 'royalty', name: 'royalty'},
                    {data: 'total', name: 'total'},
                ],
                "footerCallback": function (row, data, start, end, display) {
                    var api = this.api();
                    
                    var totalPenonton = api.column(2).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);
	            
                    // Total Gross
                    var totalSeats = data.reduce(function (total, item) {
                        return total + parseNumber(item.seats_available);
                    }, 0);

                    var occupancyRate = totalSeats ? (totalPenonton / totalSeats) * 100 : 0;

                    var totalGross = api.column(5).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    var totalAtp = totalPenonton ? totalGross / totalPenonton : 0;

                    // Total Net
                    var totalNet = api.column(8).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Total Share
                    var totalShare = api.column(9).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Total (Net - Share - Royalty)
                    var totalFinal = api.column(11).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    var totalTax = data.reduce(function (total, item) {
                        return total + parseNumber(item.tax);
                    }, 0);

                    var totalRoyalty = totalShare * 0.015;
                    var effectiveTaxRate = totalGross ? (totalTax / totalGross) * 100 : 0;

                    // Update footer
                    $(api.column(2).footer()).html(formatCurrency(totalPenonton));
                    $(api.column(3).footer()).html(formatCurrency(totalSeats));
                    $(api.column(4).footer()).html(formatPercent(occupancyRate));
                    $(api.column(5).footer()).html(formatCurrency(totalGross));
                    $(api.column(6).footer()).html(formatCurrency(totalAtp));
                    $(api.column(7).footer()).html(formatPercent(effectiveTaxRate));
                    $(api.column(8).footer()).html(formatCurrency(totalNet));
                    $(api.column(9).footer()).html(formatCurrency(totalShare));
                    $(api.column(11).footer()).html(formatCurrency(totalFinal));

                    renderRevenueWaterfall({
                        gross: totalGross,
                        atp: totalAtp,
                        occupancyRate: occupancyRate,
                        effectiveTaxRate: effectiveTaxRate,
                        tax: totalTax,
                        net: totalNet,
                        share: totalShare,
                        royalty: totalRoyalty,
                        total: totalFinal
                    });
                },
                "initComplete": function () {
                    this.api().columns.adjust();
                }
            });

            tableSummary.columns.adjust();
            
        });

        $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });

        $('#laporan-report-tabs a[data-toggle="tab"]').on('shown.bs.tab', function () {
            var tabTarget = $(this).attr('href');
            var activeTable = '#summary-table';

            if (tabTarget === '#leaderboard-pane') {
                activeTable = '#performance-table';
            } else if (tabTarget === '#province-pane') {
                activeTable = '#province-table';
            } else if (tabTarget === '#audit-pane') {
                activeTable = '#audit-table';
            }

            if ($.fn.DataTable.isDataTable(activeTable)) {
                var tableApi = $(activeTable).DataTable();
                tableApi.columns.adjust();
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

    function parseNumber(value) {
        value = String(value);
        var number = value.replace(/[^0-9.-]+/g, ''); // Hapus karakter selain angka
        var parsed = parseFloat(number);
        return !isNaN(parsed) ? parsed : 0; // Kembalikan angka jika valid, atau 0 jika tidak
    }

    // Helper function to format number with commas (e.g., 1000 -> 1,000)
    function formatNumber(value) {
        return value.toFixed(0).replace(/\d(?=(\d{3})+$)/g, '$&,');
    }

    function formatCurrency(value) {
        return value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function formatPercent(value) {
        return value.toFixed(2) + '%';
    }

    function downloadDetailPdf(params, filters, triggerNode) {
        var JsPdf = window.jspdf && window.jspdf.jsPDF;
        if (!JsPdf || !JsPdf.API.autoTable) {
            alert('Library PDF belum berhasil dimuat. Silakan refresh halaman dan coba kembali.');
            return;
        }

        var $trigger = $(triggerNode);
        var originalHtml = $trigger.html();
        $trigger.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyiapkan PDF...');

        $.ajax({
            url: '{{ route('laporan.detailExport') }}',
            type: 'GET',
            data: $.extend({}, params, { format: 'json' }),
            success: function (response) {
                var rows = response.rows || [];
                var totals = response.totals || {};

                if (!rows.length) {
                    alert('Tidak ada data detail untuk filter yang dipilih.');
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
                var generatedDate = new Date();
                var padDatePart = function (value) {
                    return String(value).padStart(2, '0');
                };
                var generatedAt = padDatePart(generatedDate.getDate())
                    + '-' + padDatePart(generatedDate.getMonth() + 1)
                    + '-' + generatedDate.getFullYear()
                    + ' ' + padDatePart(generatedDate.getHours())
                    + ':' + padDatePart(generatedDate.getMinutes());

                function pdfNumber(value, decimals) {
                    return Number(parseNumber(value)).toLocaleString('id-ID', {
                        minimumFractionDigits: decimals || 0,
                        maximumFractionDigits: decimals || 0
                    });
                }

                function pdfPercent(value) {
                    return pdfNumber(value, 2) + '%';
                }

                function displayDate(value) {
                    var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
                    return match ? match[3] + '-' + match[2] + '-' + match[1] : (value || '-');
                }

                function displayPeriod(value) {
                    return String(value || '-').replace(/\b(\d{4})-(\d{2})-(\d{2})\b/g, function (date, year, month, day) {
                        return day + '-' + month + '-' + year;
                    });
                }

                function addHeader(sectionName) {
                    if (sinemakuLogo) {
                        doc.addImage(sinemakuLogo, 'PNG', marginX, 8, 14, 14, undefined, 'FAST');
                    }
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(15);
                    doc.setTextColor.apply(doc, textColor);
                    doc.text('SINEMAKU PICTURES', marginX + 18, 13);
                    doc.setFontSize(9.5);
                    doc.setTextColor.apply(doc, accentColor);
                    doc.text('Audience Analytics Dashboard', marginX + 18, 18);
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8.5);
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
                        doc.setFontSize(8);
                        doc.setTextColor.apply(doc, mutedColor);
                        doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
                        doc.text('Halaman ' + page + ' dari ' + pages, pageW - marginX, pageH - 8, { align: 'right' });
                    }
                }

                function addFilterBox() {
                    var items = [
                        ['Nama Film', filters.namaFilm], ['Periode', displayPeriod(filters.periode)], ['Kategori Bioskop', filters.kategori],
                        ['Kota', filters.kota], ['Nama Bioskop', filters.bioskop], ['Tipe Tiket', filters.tipeTiket]
                    ];
                    var y = 47;
                    var columnW = usableW / 3;
                    doc.setFillColor(249, 250, 251);
                    doc.setDrawColor.apply(doc, borderColor);
                    doc.roundedRect(marginX, y, usableW, 34, 2, 2, 'FD');
                    items.forEach(function (item, index) {
                        var x = marginX + ((index % 3) * columnW) + 5;
                        var itemY = y + 7 + (Math.floor(index / 3) * 15);
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(7.5);
                        doc.setTextColor.apply(doc, mutedColor);
                        doc.text(item[0].toUpperCase(), x, itemY);
                        doc.setFontSize(9);
                        doc.setTextColor.apply(doc, textColor);
                        doc.text(String(item[1] || '-'), x, itemY + 5, { maxWidth: columnW - 10 });
                    });
                }

                function addMetric(label, value, x, y, width, color) {
                    doc.setFillColor(255, 255, 255);
                    doc.setDrawColor.apply(doc, borderColor);
                    doc.roundedRect(x, y, width, 20, 2, 2, 'FD');
                    doc.setFillColor.apply(doc, color);
                    doc.roundedRect(x, y, 3, 20, 1.5, 1.5, 'F');
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(11);
                    doc.setTextColor.apply(doc, textColor);
                    doc.text(String(value), x + 7, y + 9, { maxWidth: width - 10 });
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(7.5);
                    doc.setTextColor.apply(doc, mutedColor);
                    doc.text(label, x + 7, y + 15.5);
                }

                addHeader('Executive Summary - Detail Rekap Omset');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(15);
                doc.setTextColor.apply(doc, textColor);
                doc.text('Laporan Detail Rekap Omset', marginX, 35);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text('Detail operasional dan finansial berdasarkan filter laporan aktif.', marginX, 41);
                addFilterBox();

                var metricGap = 4;
                var metricW = (usableW - (metricGap * 3)) / 4;
                addMetric('Baris Detail', pdfNumber(response.row_count, 0), marginX, 87, metricW, [98, 91, 214]);
                addMetric('Total Penonton', pdfNumber(totals.Total, 0), marginX + metricW + metricGap, 87, metricW, [37, 99, 235]);
                addMetric('Total Gross', pdfNumber(totals.gross, 2), marginX + ((metricW + metricGap) * 2), 87, metricW, [40, 199, 162]);
                addMetric('Total Akhir / PH', pdfNumber(totals.total_akhir, 2), marginX + ((metricW + metricGap) * 3), 87, metricW, [4, 120, 87]);

                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8);
                doc.setTextColor.apply(doc, accentColor);
                doc.text('STRUKTUR PDF', marginX, 119);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8.5);
                doc.setTextColor.apply(doc, textColor);
                doc.text(
                    'Tabel dipisahkan menjadi Detail Operasional dan Detail Finansial agar setiap kolom tetap terbaca pada ukuran A4 landscape.',
                    marginX,
                    125,
                    { maxWidth: usableW }
                );
                doc.setTextColor.apply(doc, mutedColor);
                doc.text(
                    'Kolom yang tidak disertakan: Kota, Kapasitas, ATP, Effective Tax Rate, dan Share.',
                    marginX,
                    132,
                    { maxWidth: usableW }
                );

                var operationalRows = rows.map(function (row, index) {
                    return [
                        index + 1,
                        displayDate(row.tgl_tayang),
                        row.name || '-',
                        String(row.nama_bioskop || '-').toUpperCase(),
                        row.studio || '-',
                        pdfNumber(row.S1, 0), pdfNumber(row.S2, 0), pdfNumber(row.S3, 0), pdfNumber(row.S4, 0),
                        pdfNumber(row.S5, 0), pdfNumber(row.S6, 0), pdfNumber(row.S7, 0),
                        pdfNumber(row.Total, 0), pdfNumber(row.seats_available, 2), pdfPercent(row.occupancy_rate)
                    ];
                });
                operationalRows.push([
                    '', 'TOTAL', '', '', '',
                    pdfNumber(totals.S1, 0), pdfNumber(totals.S2, 0), pdfNumber(totals.S3, 0), pdfNumber(totals.S4, 0),
                    pdfNumber(totals.S5, 0), pdfNumber(totals.S6, 0), pdfNumber(totals.S7, 0),
                    pdfNumber(totals.Total, 0), pdfNumber(totals.seats_available, 2),
                    totals.seats_available ? pdfPercent((totals.Total / totals.seats_available) * 100) : '0,00%'
                ]);

                doc.addPage('a4', 'landscape');
                doc.autoTable({
                    startY: 42,
                    margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
                    head: [['No', 'Tanggal', 'Kategori', 'Nama Bioskop', 'Studio', 'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'Total', 'Kapasitas Tersedia', 'Occupancy']],
                    body: operationalRows,
                    theme: 'grid',
                    showHead: 'everyPage',
                    styles: { font: 'helvetica', fontSize: 6.8, cellPadding: 1.5, textColor: textColor, overflow: 'linebreak' },
                    headStyles: { fillColor: brandColor, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
                    alternateRowStyles: { fillColor: [249, 250, 251] },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 8 },
                        1: { halign: 'center', cellWidth: 18 },
                        2: { cellWidth: 32 },
                        3: { cellWidth: 59 },
                        4: { cellWidth: 15 },
                        5: { halign: 'right', cellWidth: 10 }, 6: { halign: 'right', cellWidth: 10 },
                        7: { halign: 'right', cellWidth: 10 }, 8: { halign: 'right', cellWidth: 10 },
                        9: { halign: 'right', cellWidth: 10 }, 10: { halign: 'right', cellWidth: 10 },
                        11: { halign: 'right', cellWidth: 10 }, 12: { halign: 'right', cellWidth: 16 },
                        13: { halign: 'right', cellWidth: 28 }, 14: { halign: 'right', cellWidth: 23 }
                    },
                    didParseCell: function (tableData) {
                        if (tableData.section === 'body' && tableData.row.index === operationalRows.length - 1) {
                            tableData.cell.styles.fontStyle = 'bold';
                            tableData.cell.styles.fillColor = brandColor;
                            tableData.cell.styles.textColor = [255, 255, 255];
                        }
                    },
                    didDrawPage: function () {
                        addHeader('Detail Data - Rekap Omset');
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(11);
                        doc.setTextColor.apply(doc, textColor);
                        doc.text('Detail Operasional', marginX, 35);
                    }
                });

                var financialRows = rows.map(function (row, index) {
                    return [
                        index + 1,
                        displayDate(row.tgl_tayang),
                        row.name || '-',
                        String(row.nama_bioskop || '-').toUpperCase(),
                        row.studio || '-',
                        pdfNumber(row.harga, 2),
                        pdfNumber(row.gross, 2),
                        pdfPercent(row.pajak_persen),
                        pdfNumber(row.pajak, 2),
                        pdfNumber(row.net, 2),
                        pdfNumber(row.share_ph, 2),
                        pdfNumber(row.royalty, 2),
                        pdfNumber(row.total_akhir, 2)
                    ];
                });
                financialRows.push([
                    '', 'TOTAL', '', '', '', '',
                    pdfNumber(totals.gross, 2), '', pdfNumber(totals.pajak, 2), pdfNumber(totals.net, 2),
                    pdfNumber(totals.share_ph, 2), pdfNumber(totals.royalty, 2), pdfNumber(totals.total_akhir, 2)
                ]);

                doc.addPage('a4', 'landscape');
                doc.autoTable({
                    startY: 42,
                    margin: { top: 42, left: marginX, right: marginX, bottom: 18 },
                    head: [['No', 'Tanggal', 'Kategori', 'Nama Bioskop', 'Studio', 'Harga', 'Gross', 'Pajak %', 'Pajak', 'Net', 'Share PH', 'Royalty 1.5%', 'Total Akhir']],
                    body: financialRows,
                    theme: 'grid',
                    showHead: 'everyPage',
                    styles: { font: 'helvetica', fontSize: 6.5, cellPadding: 1.5, textColor: textColor, overflow: 'linebreak' },
                    headStyles: { fillColor: brandColor, textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center' },
                    alternateRowStyles: { fillColor: [249, 250, 251] },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 7 },
                        1: { halign: 'center', cellWidth: 18 },
                        2: { cellWidth: 27 },
                        3: { cellWidth: 40 },
                        4: { cellWidth: 12 },
                        5: { halign: 'right', cellWidth: 18 },
                        6: { halign: 'right', cellWidth: 24 },
                        7: { halign: 'right', cellWidth: 11 },
                        8: { halign: 'right', cellWidth: 21 },
                        9: { halign: 'right', cellWidth: 22 },
                        10: { halign: 'right', cellWidth: 22 },
                        11: { halign: 'right', cellWidth: 21 },
                        12: { halign: 'right', cellWidth: 26 }
                    },
                    didParseCell: function (tableData) {
                        if (tableData.section === 'body' && tableData.row.index === financialRows.length - 1) {
                            tableData.cell.styles.fontStyle = 'bold';
                            tableData.cell.styles.fillColor = brandColor;
                            tableData.cell.styles.textColor = [255, 255, 255];
                        }
                    },
                    didDrawPage: function () {
                        addHeader('Detail Data - Rekap Omset');
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(11);
                        doc.setTextColor.apply(doc, textColor);
                        doc.text('Detail Finansial', marginX, 35);
                    }
                });

                addFooter();
                var filmName = String(filters.namaFilm || 'semua-film').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                doc.save('laporan-detail-' + (filmName || 'semua-film') + '.pdf');
            },
            error: function () {
                alert('Gagal mengambil data detail PDF. Silakan coba kembali.');
            },
            complete: function () {
                $trigger.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function renderRevenueWaterfall(values) {
        latestRevenueWaterfall = {
            values: $.extend({}, values),
            filters: {
                namaFilm: $('#nama_film option:selected').text() || '-',
                periode: ($('#tanggal_mulai').val() && $('#tanggal_akhir').val())
                    ? $('#tanggal_mulai').val() + ' s/d ' + $('#tanggal_akhir').val()
                    : 'Semua Periode',
                kategori: $('#bioskop_kategori option:selected').text() || '-',
                kota: $('#kota option:selected').text() || '-',
                bioskop: $('#nama_bioskop option:selected').text() || '-',
                tipeTiket: $('#type_tiket option:selected').text() || '-'
            }
        };

        var gross = values.gross || 0;
        var maxValue = Math.max(gross, values.net || 0, values.total || 0, 1);
        var steps = [
            { label: 'Gross Box Office', value: values.gross, type: 'gross' },
            { label: 'Pajak', value: values.tax, type: 'deduction', prefix: '-' },
            { label: 'Net Box Office', value: values.net, type: 'net' },
            { label: 'Share 50%', value: values.share, type: 'deduction', prefix: '-' },
            { label: 'Royalty 1.5%', value: values.royalty, type: 'deduction', prefix: '-' },
            { label: 'Total PH', value: values.total, type: 'final' }
        ];

        $('#wf-gross').text(formatCurrency(values.gross || 0));
        $('#wf-atp').text(formatCurrency(values.atp || 0));
        $('#wf-occupancy').text(formatPercent(values.occupancyRate || 0));
        $('#wf-etr').text(formatPercent(values.effectiveTaxRate || 0));
        $('#wf-tax').text('-' + formatCurrency(values.tax || 0));
        $('#wf-net').text(formatCurrency(values.net || 0));
        $('#wf-total').text(formatCurrency(values.total || 0));

        var html = steps.map(function (step) {
            var value = step.value || 0;
            var width = Math.max((Math.abs(value) / maxValue) * 100, value ? 2 : 0);
            var barClass = 'waterfall-bar';

            if (step.type === 'deduction') {
                barClass += ' waterfall-bar--deduction';
            } else if (step.type === 'net') {
                barClass += ' waterfall-bar--net';
            } else if (step.type === 'final') {
                barClass += ' waterfall-bar--final';
            }

            return [
                '<div class="waterfall-row">',
                    '<div class="waterfall-label">' + step.label + '</div>',
                    '<div class="waterfall-track">',
                        '<div class="' + barClass + '" style="width: ' + width + '%"></div>',
                    '</div>',
                    '<div class="waterfall-value">' + (step.prefix || '') + formatCurrency(value) + '</div>',
                '</div>'
            ].join('');
        }).join('');

        $('#waterfall-flow').html(html);
        $('#revenue-waterfall').show();
        $('#download-waterfall-pdf').show();
    }

    function downloadRevenueWaterfallPdf() {
        if (!latestRevenueWaterfall || !latestRevenueWaterfall.values) {
            alert('Data Revenue Waterfall belum tersedia. Silakan jalankan filter terlebih dahulu.');
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
        var values = latestRevenueWaterfall.values;
        var filters = latestRevenueWaterfall.filters;
        var generatedAt = new Date().toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        function pdfNumber(value, decimals) {
            return Number(value || 0).toLocaleString('id-ID', {
                minimumFractionDigits: decimals || 0,
                maximumFractionDigits: decimals || 0
            });
        }

        function addHeader() {
            if (sinemakuLogo) {
                doc.addImage(sinemakuLogo, 'PNG', marginX, 8, 14, 14, undefined, 'FAST');
            }
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
            doc.text('Revenue Waterfall Report', pageW - marginX, 13, { align: 'right' });
            doc.text('Generated: ' + generatedAt, pageW - marginX, 18, { align: 'right' });
            doc.setDrawColor.apply(doc, accentColor);
            doc.setLineWidth(0.45);
            doc.line(marginX, 25, pageW - marginX, 25);
            doc.setDrawColor.apply(doc, [209, 213, 219]);
            doc.setLineWidth(0.15);
            doc.line(marginX, 27, pageW - marginX, 27);
        }

        function addFooter() {
            doc.setDrawColor.apply(doc, borderColor);
            doc.line(marginX, pageH - 13, pageW - marginX, pageH - 13);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text('Sinemaku Pictures - Confidential Analytics Report', marginX, pageH - 8);
            doc.text('Halaman 1 dari 1', pageW - marginX, pageH - 8, { align: 'right' });
        }

        function addFilterBox() {
            var items = [
                ['Nama Film', filters.namaFilm], ['Periode', filters.periode], ['Kategori Bioskop', filters.kategori],
                ['Kota', filters.kota], ['Nama Bioskop', filters.bioskop], ['Tipe Tiket', filters.tipeTiket]
            ];
            var y = 46;
            var columnW = usableW / 3;
            doc.setFillColor(249, 250, 251);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(marginX, y, usableW, 34, 2, 2, 'FD');
            items.forEach(function (item, index) {
                var x = marginX + ((index % 3) * columnW) + 5;
                var itemY = y + 7 + (Math.floor(index / 3) * 15);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(6.5);
                doc.setTextColor.apply(doc, mutedColor);
                doc.text(item[0].toUpperCase(), x, itemY);
                doc.setFontSize(8.3);
                doc.setTextColor.apply(doc, textColor);
                doc.text(String(item[1] || '-'), x, itemY + 5, { maxWidth: columnW - 10 });
            });
        }

        function addMetric(label, value, x, y, width, color) {
            doc.setFillColor(255, 255, 255);
            doc.setDrawColor.apply(doc, borderColor);
            doc.roundedRect(x, y, width, 18, 2, 2, 'FD');
            doc.setFillColor.apply(doc, color);
            doc.roundedRect(x, y, 3, 18, 1.5, 1.5, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9.5);
            doc.setTextColor.apply(doc, textColor);
            doc.text(String(value), x + 7, y + 8, { maxWidth: width - 10 });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(6.5);
            doc.setTextColor.apply(doc, mutedColor);
            doc.text(label, x + 7, y + 14);
        }

        addHeader();
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.setTextColor.apply(doc, textColor);
        doc.text('Revenue Waterfall', marginX, 35);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor.apply(doc, mutedColor);
        doc.text('Alur perhitungan dari Gross Box Office sampai estimasi Total PH.', marginX, 41);
        addFilterBox();

        var gap = 4;
        var metricW = (usableW - (gap * 3)) / 4;
        addMetric('Gross Box Office', pdfNumber(values.gross, 2), marginX, 85, metricW, brandColor);
        addMetric('Average Ticket Price', pdfNumber(values.atp, 2), marginX + metricW + gap, 85, metricW, [98, 91, 214]);
        addMetric('Occupancy Rate', pdfNumber(values.occupancyRate, 2) + '%', marginX + ((metricW + gap) * 2), 85, metricW, [37, 99, 235]);
        addMetric('Effective Tax Rate', pdfNumber(values.effectiveTaxRate, 2) + '%', marginX + ((metricW + gap) * 3), 85, metricW, accentColor);

        var steps = [
            { label: 'Gross Box Office', value: values.gross, color: brandColor },
            { label: 'Pajak', value: values.tax, prefix: '-', color: accentColor },
            { label: 'Net Box Office', value: values.net, color: [37, 99, 235] },
            { label: 'Share 50%', value: values.share, prefix: '-', color: accentColor },
            { label: 'Royalty 1.5%', value: values.royalty, prefix: '-', color: accentColor },
            { label: 'Total PH', value: values.total, color: [4, 120, 87] }
        ];
        var maxValue = Math.max(Number(values.gross || 0), Number(values.net || 0), Number(values.total || 0), 1);
        var labelW = 36;
        var valueW = 45;
        var trackX = marginX + labelW;
        var trackW = usableW - labelW - valueW;
        var flowY = 109;

        steps.forEach(function (step, index) {
            var rowY = flowY + (index * 11.5);
            var value = Number(step.value || 0);
            var barW = Math.max((Math.abs(value) / maxValue) * trackW, value ? 2 : 0);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(7.5);
            doc.setTextColor.apply(doc, textColor);
            doc.text(step.label, marginX, rowY + 5.5, { maxWidth: labelW - 3 });
            doc.setFillColor(243, 244, 246);
            doc.roundedRect(trackX, rowY, trackW, 7, 3.5, 3.5, 'F');
            if (barW > 0) {
                doc.setFillColor.apply(doc, step.color);
                var barRadius = Math.min(3.5, barW / 2);
                doc.roundedRect(trackX, rowY, barW, 7, barRadius, barRadius, 'F');
            }
            doc.setFontSize(7.5);
            doc.text((step.prefix || '') + pdfNumber(value, 2), pageW - marginX, rowY + 5.5, { align: 'right' });
        });

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor.apply(doc, mutedColor);
        doc.text(
            'Formula: Occupancy = Penonton / Kapasitas Tersedia; Gross - Pajak = Net; Net - Share 50% - Royalty 1.5% dari share = Total PH.',
            marginX,
            183,
            { maxWidth: usableW }
        );
        addFooter();

        var filmName = String(filters.namaFilm || 'semua-film').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        doc.save('report-revenue-waterfall-' + (filmName || 'semua-film') + '.pdf');
    }
</script>
@endsection
