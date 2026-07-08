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
                            <div class="form-group col-md-1 mb-3">
                                {{ Form::label('','',['class' => 'form-label'])}} <br>
                                <button type="button" id="search-btn" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>
                    <div id="data-summary">
                        {{-- <h4>📊 List 20 Besar Kota Dengan Penonton Tertinggi</h4> --}}
                        <table id="summary-table" class="table table-bordered table-hover table-striped w-100" style="display: none;">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kategori</th>
                                    <th>Jumlah Penonton</th>
                                    <th>Total Pendapatan</th>
                                    {{-- <th>Pajak</th> --}}
                                    <th>Net</th>
                                    <th>Share 50%</th>
                                    <th>Royalty (1.5%)</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th colspan="1">Total</th>
                                    <th id="total-summary-penonton"></th>
                                    <th id="total-summary-gross"></th>
                                    {{-- <th id="total-summary-tax"></th> --}}
                                    <th id="total-summary-net"></th>
                                    <th id="total-summary-share"></th>
                                    <th></th>
                                    <th id="total-summary-total"></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
<script>
    var sinemakuLogo = @json(file_exists(public_path('img/sinemaku.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/sinemaku.png'))) : null);

    $(document).ready(function(){
        $('#nama_film').select2();
        $('#bioskop_kategori').select2();
        $('#kota').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();

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

            if ($.fn.DataTable.isDataTable("#datatable")) {
                $('#datatable').DataTable().destroy();
            }

            if ($.fn.DataTable.isDataTable("#summary-table")) {
                $('#summary-table').DataTable().destroy();
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

            var tableSummary = $('#summary-table').DataTable({
                "processing": true,
                "serverSide": false,
                "paging": false,
                "responsive": true,
                "order": [[ 0, "asc" ]],
                dom: 'Bfrtip', // <== Tambahkan ini agar tombol tampil
                buttons: [
                    {
                        text: '<i class="fa fa-file-excel"></i> Detail',
                        className: 'btn btn-info btn-sm rounded-pill',
                        action: function (e, dt, node, config) {
                            var params = $.param({
                                nama_film: nama_film,
                                tgl_mulai: tgl_mulai,
                                tgl_akhir: tgl_akhir,
                                bioskop_kategori: bioskop_kategori,
                                kota: kota,
                                nama_bioskop: nama_bioskop,
                                type_tiket: type_tiket
                            });
                            window.location.href = '{{ route('laporan.detailExport') }}' + '?' + params;
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
                                tableNode.table.widths = [26, '*', 78, 86, 86, 82, 70, 86];
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
                                            cellObject.alignment = columnIndex === 1 ? 'left' : (columnIndex === 6 ? 'center' : 'right');

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
                    {data: 'gross', name: 'gross'},
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
                    var totalGross = api.column(3).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Total Tax
                    // var totalTax = api.column(4).data().reduce(function (a, b) {
                    //     var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                    //     var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                    //     return valA + valB;
                    // }, 0);

                    // Total Net
                    var totalNet = api.column(4).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Total Share
                    var totalShare = api.column(5).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Total (Net - Share - Royalty)
                    var totalFinal = api.column(7).data().reduce(function (a, b) {
                        var valA = parseNumber(a); // Pastikan menjadi angka, jika tidak valid maka 0
                        var valB = parseNumber(b); // Pastikan menjadi angka, jika tidak valid maka 0
                        return valA + valB;
                    }, 0);

                    // Update footer
                    $(api.column(2).footer()).html(formatCurrency(totalPenonton));
                    $(api.column(3).footer()).html(formatCurrency(totalGross));
                    // $(api.column(4).footer()).html(formatCurrency(totalTax));
                    $(api.column(4).footer()).html(formatCurrency(totalNet));
                    $(api.column(5).footer()).html(formatCurrency(totalShare));
                    $(api.column(7).footer()).html(formatCurrency(totalFinal));
                }
            });

            $('#summary-table').show();
            
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
