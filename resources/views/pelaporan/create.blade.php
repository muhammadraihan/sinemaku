@extends('layouts.page')

@section('title', 'Laporan Create')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/dropzone/dropzone.css')}}">
<link rel="stylesheet" media="screen, print"
    href="{{asset('css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
<style>
    .report-entry-note { border-left: 4px solid #2196f3; background: #f4f9ff; }
    .data-row { position: relative; margin: 0 0 1rem; padding: 3.25rem 1rem 0; border: 1px solid #dfe6ee; border-radius: .5rem; background: #fff; box-shadow: 0 .125rem .35rem rgba(0,0,0,.04); }
    .data-row-heading { position: absolute; top: 0; left: 0; right: 0; display: flex; align-items: center; justify-content: space-between; padding: .7rem 1rem; border-bottom: 1px solid #e9ecef; background: #f8f9fa; border-radius: .5rem .5rem 0 0; }
    .data-row-title { margin: 0; font-weight: 600; }
</style>
@endsection

@section('content')
<div class="col-xxl">
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>Tambah Baru <span class="fw-300"><i>Laporan </i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('pelaporan.index')}}"><i class="fal fa-arrow-alt-left">
                    </i>
                    <span class="nav-link-text">Kembali</span>
                </a>
                <button class="btn btn-panel" data-action="panel-fullscreen" data-toggle="tooltip"
                    data-offset="0,10" data-original-title="Fullscreen"></button>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="panel-tag">
                    Field dengan <code>*</code> tidak boleh kosong.
                </div>
                @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                @endif
                {!! Form::open(['route' => 'pelaporan.store','id'=>'forms','method' => 'POST','class' =>
                'needs-validation','dropzone', 'forms','novalidate','enctype' => 'multipart/form-data']) !!}
                <div class="row">
                    <div class="form-group col-md-8 mb-3">
                        {{ Form::label('nama_film','Nama Film',['class' => 'required form-label'])}}
                        {!! Form::select('nama_film', $nama_film, old('nama_film'), [
                            'id' => 'nama_film',
                            'class' => 'custom-select'.($errors->has('nama_film') ? ' is-invalid' : ''),
                            'required' => '',
                            'placeholder' => 'Pilih Nama Film ...'
                        ]) !!}
                        @if ($errors->has('nama_film'))
                        <div class="invalid-feedback">{{ $errors->first('nama_film') }}</div>
                        @endif
                    </div>
                </div>
                <hr style="border: 1px dashed: color: black">
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('kategori','Kategori Bioskop',['class' => 'required form-label'])}}
                        {!! Form::select('kategori', $bioskop_kategori, '',
                        ['id'=>'kategori','class'
                        => 'custom-select'.($errors->has('kategori') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Kategori Bioskop ...'])!!}
                        @if ($errors->has('kategori'))
                        <div class="invalid-feedback">{{ $errors->first('kategori') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('nama_bioskop','Nama Bioskop',['class' => 'required form-label'])}}
                        {!! Form::select('nama_bioskop', $nama_bioskop, '',
                        ['id'=>'nama_bioskop','class'
                        => 'custom-select'.($errors->has('nama_bioskop') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Nama Bioskop ...'])!!}
                        @if ($errors->has('nama_bioskop'))
                        <div class="invalid-feedback">{{ $errors->first('nama_bioskop') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('kota','Kota',['class' => 'required form-label'])}}
                        {!! Form::select('kota', $kota, '',
                        ['id'=>'kota','class'
                        => 'custom-select'.($errors->has('kota') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Kota ...'])!!}
                        @if ($errors->has('kota'))
                        <div class="invalid-feedback">{{ $errors->first('kota') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('provinsi','Provinsi',['class' => 'form-label'])}}
                        {{ Form::text('provinsi',null,['id' => 'provinsi','placeholder' => 'Provinsi','class' => 'form-control provinsi'.($errors->has('provinsi') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('provinsi'))
                        <div class="invalid-feedback">{{ $errors->first('provinsi') }}</div>
                        @endif
                    </div>
                </div>
                <hr style="border: 1px dashed: color: black">
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('tgl_tayang','Tanggal Tayang',['class' => 'required form-label'])}}
                        {{ Form::text('tgl_tayang',null,['placeholder' => 'Tanggal Tayang','class' => 'form-control tgl_tayang'.($errors->has('tgl_tayang') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('tgl_tayang'))
                        <div class="invalid-feedback">{{ $errors->first('tgl_tayang') }}</div>
                        @endif
                    </div>
                </div>
                <hr style="border: 1px dashed: color: black">
                <div class="alert report-entry-note mb-4">
                    <strong>Input per kombinasi.</strong> Tambahkan satu baris untuk setiap show, tipe tiket, dan studio. Studio disaring otomatis berdasarkan master kapasitas bioskop.
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0">Rincian Laporan</h4>
                    <button type="button" id="addRow" class="btn btn-success"><i class="fal fa-plus mr-1"></i> Tambah Baris</button>
                </div>
                <div id="rowContainer">
                    <div class="row data-row">
                        <div class="data-row-heading">
                            <span class="data-row-title"><i class="fal fa-ticket-alt mr-1"></i> Baris Laporan</span>
                            <button type="button" class="btn btn-sm btn-outline-danger removeRow">Hapus</button>
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('show[]','Show',['class' => 'required form-label'])}}
                            {!! Form::select('show[]', array('1' => 'Show - 1', '2' => 'Show - 2', '3' => 'Show - 3', '4' => 'Show - 4', '5' => 'Show - 5', '6' => 'Show - 6', '7' => 'Show - 7', '8' => 'Show - 8'), '',
                            ['id'=>'show','class'
                            => 'custom-select shows'.($errors->has('show') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Pilih Show ...'])!!}
                            @if ($errors->has('show'))
                            <div class="invalid-feedback">{{ $errors->first('show') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('jam_tayang[]','Jam',['class' => 'form-label'])}}
                            {{ Form::time('jam_tayang[]',null,['placeholder' => 'Jam','class' => 'form-control '.($errors->has('jam_tayang') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('jam_tayang'))
                            <div class="invalid-feedback">{{ $errors->first('jam_tayang') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('type_tiket[]','Tipe Tiket',['class' => 'required form-label'])}}
                            {!! Form::select('type_tiket[]', [], null, ['class' => 'custom-select row-ticket'.($errors->has('type_tiket') ? ' is-invalid' : ''), 'required' => '', 'data-placeholder' => 'Pilih Tipe Tiket ...']) !!}
                            <small class="form-text text-muted">Pilih tipe tiket untuk baris ini.</small>
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('studio[]','Studio',['class' => 'required form-label'])}}
                            {!! Form::select('studio[]', [], null, ['class' => 'custom-select row-studio'.($errors->has('studio') ? ' is-invalid' : ''), 'required' => '', 'disabled' => 'disabled', 'data-placeholder' => 'Pilih tipe tiket terlebih dahulu']) !!}
                            <small class="form-text text-muted">Studio hanya muncul dari kapasitas yang sesuai.</small>
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('harga[]','Harga',['class' => 'required form-label'])}}
                            {{ Form::text('harga[]',null,['placeholder' => 'Harga','class' => 'form-control harga '.($errors->has('harga') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('harga'))
                            <div class="invalid-feedback">{{ $errors->first('harga') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('jumlah[]','Qty',['class' => 'required form-label'])}}
                            {{ Form::text('jumlah[]',null,['placeholder' => 'Qty','class' => 'form-control jumlah '.($errors->has('jumlah') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('jumlah'))
                            <div class="invalid-feedback">{{ $errors->first('jumlah') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('gross[]','Gross',['class' => 'required form-label'])}}
                            {{ Form::text('gross[]',null,['placeholder' => 'Gross','class' => 'form-control gross '.($errors->has('gross') ? 'is-invalid':''),'required', 'readonly' => 'true'])}}
                            @if ($errors->has('gross'))
                            <div class="invalid-feedback">{{ $errors->first('gross') }}</div>
                            @endif
                        </div>
                        <hr style="border: 1px dashed: color: black">
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('tax[]','Tax',['class' => 'form-label'])}}
                            {{ Form::text('tax[]',null,['placeholder' => 'Tax','class' => 'form-control tax '.($errors->has('tax') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('tax'))
                            <div class="invalid-feedback">{{ $errors->first('tax') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('net[]','Net',['class' => 'required form-label'])}}
                            {{ Form::text('net[]',null,['placeholder' => 'Net','class' => 'form-control net '.($errors->has('net') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('net'))
                            <div class="invalid-feedback">{{ $errors->first('net') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            <div
                class="panel-content border-faded border-left-0 border-right-0 border-bottom-0 d-flex flex-row align-items-center">
                <button class="btn btn-primary ml-auto" type="submit">Submit</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{asset('js/formplugins/select2/select2.bundle.js')}}"></script>
<script src="{{asset('js/formplugins/dropzone/dropzone.js')}}"></script>
<script src="{{asset('js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js')}}"></script>
<script>
    $(document).ready(function(){
        $('#nama_film').select2({ width: '100%' });
        $('#kategori').select2();
        $('#nama_bioskop').select2();
        $('#kota').select2();
        $('.row-ticket').select2({ width: '100%' });
        $('.row-studio').select2({ width: '100%' });

        var ticketOptions = {};

        function resetRowSelect($row) {
            $row.find('.row-ticket').empty().append('<option value="">Pilih Tipe Tiket ...</option>').trigger('change');
            $row.find('.row-studio').empty().append('<option value="">Pilih tipe tiket terlebih dahulu</option>').prop('disabled', true).trigger('change');
        }

        function loadTicketOptions() {
            var kategori = $('#kategori').val();
            $('.row-ticket, .row-studio').each(function () { resetRowSelect($(this).closest('.data-row')); });
            if (!kategori) return;
            $.get("{{ route('ref.type') }}", { kategori: kategori }, function (data) {
                ticketOptions = data || {};
                $('.row-ticket').each(function () {
                    var $select = $(this).empty().append('<option value="">Pilih Tipe Tiket ...</option>');
                    $.each(ticketOptions, function (key, value) { $select.append('<option value="' + key + '">' + value + '</option>'); });
                    $select.trigger('change');
                });
            });
        }

        $('#kategori').change(function(){
            var kategori = $(this).val();
            $('#kota').empty();
            loadTicketOptions();
            $.get("{{ route('ref.cinema') }}", { kategori: kategori }, function (data) {
                $('#nama_bioskop').empty().append('<option value="">Pilih Nama Bioskop ...</option>');
                $.each(data, function(key, value) { $('#nama_bioskop').append('<option value="' + key + '">' + value + '</option>'); });
                $('#nama_bioskop').trigger('change');
            });
        });

        $('#nama_bioskop').change(function(){
            var kategori = $('#kategori').val(), bioskop = $(this).val();
            $('#kota').empty();
            if (!kategori || !bioskop) return;
            $.get("{{ route('ref.kota') }}", { kategori: kategori, bioskop: bioskop }, function (data) {
                $('#kota').append('<option value="">Pilih Kota ...</option>');
                $.each(data, function(key, value) { $('#kota').append('<option value="' + key + '">' + value + '</option>'); });
                $('#kota').trigger('change');
            });
            $.get("{{ route('ref.pajak') }}", { kategori: kategori, bioskop: bioskop }, function (data) { currentTax = data.pajak || 0; $('.tax').val(currentTax).trigger('input'); });
        });

        $(document).on('change', '.row-ticket', function () {
            var $row = $(this).closest('.data-row'), ticket = $(this).val();
            var kategori = $('#kategori').val(), bioskop = $('#nama_bioskop').val(), kota = $('#kota').val();
            var $studio = $row.find('.row-studio').empty().append('<option value="">Memuat studio ...</option>').prop('disabled', true).trigger('change');
            if (!kategori || !bioskop || !kota || !ticket) { $studio.empty().append('<option value="">Pilih tipe tiket terlebih dahulu</option>').trigger('change'); return; }
            $.get("{{ route('ref.studio') }}", { kategori: kategori, nama_bioskop: bioskop, kota: kota, type_tiket: ticket }, function (data) {
                $studio.empty().append('<option value="">Pilih Studio ...</option>');
                $.each(data, function(key, value) { $studio.append('<option value="' + key + '">' + value + '</option>'); });
                $studio.prop('disabled', false).trigger('change');
            });
        });

        $('#kota').change(function () {
            var kota = $(this).val();
            if (!kota) return;
            $.get("{{ route('ref.provinsi') }}", { kota: kota }, function (data) { $('#provinsi').val(data && data[0] ? data[0].nama : ''); });
        });

        // $('.shows').select2();

        var currentTax = 0;

        $("#addRow").click(function () {
            let newRow = $(".data-row:first").clone(); // Duplikasi row pertama
            // Row controls are initialized after cloning.
            newRow.find("input, select").val(""); // Kosongkan nilai input
            newRow.find('.row-ticket, .row-studio').each(function () {
                $(this).next('.select2').remove();
            });
            newRow.appendTo("#rowContainer");

            newRow.find('[id]').removeAttr('id');
            newRow.find('.row-ticket').select2({ width: '100%' });
            newRow.find('.row-studio').select2({ width: '100%' });
            newRow.find('.row-ticket').empty().append('<option value="">Pilih Tipe Tiket ...</option>');
            $.each(ticketOptions, function (key, value) {
                newRow.find('.row-ticket').append('<option value="' + key + '">' + value + '</option>');
            });
            newRow.find('.row-ticket').val('').trigger('change');
            newRow.find('.row-studio').empty().append('<option value="">Pilih tipe tiket terlebih dahulu</option>').prop('disabled', true).trigger('change');
            newRow.find(".tax").val(currentTax).trigger('input');
        });

           $('.tgl_tayang').datepicker({
            orientation: "bottom left",
            format:'dd-mm-yyyy', // Notice the Extra space at the beginning
            todayHighlight:'TRUE',
            autoclose: true,
            todayBtn: "linked",
            clearBtn: true,
        });
    });
    
    $(document).on("click", ".removeRow", function () {
        if ($(".data-row").length > 1) {
            $(this).closest(".data-row").remove();
        }
    });

    $(document).on('input', '.harga, .jumlah, .tax', function() {
    // Cari elemen terdekat dalam baris yang sama
    var row = $(this).closest('.data-row');

    // Ambil nilai harga dan jumlah, hilangkan pemisah ribuan
    var harga = parseFloat(row.find('.harga').val().replace(/,/g, '')) || 0;
    var jumlah = parseFloat(row.find('.jumlah').val()) || 0;
    // var tax = parseFloat(row.find('.tax').val().replace(/,/g, '')) || 0;
    var taxPercent = parseFloat(row.find('.tax').val()) || 0;

    // Hitung gross
    var gross = harga * jumlah;
    // var total = gross && tax ? gross - tax : gross;
    var taxNominal = gross * (taxPercent / 100);
    var net = gross - taxNominal;

    // Masukkan hasil ke field net & gross
    // row.find('.net').val(total.toLocaleString('en-US'));
    // row.find('.gross').val(gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

    // // Format harga dengan pemisah ribuan
    // row.find('.harga').val(harga.toLocaleString('en-US'));

    row.find('.gross').val(gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    row.find('.net').val(net.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

    // Format harga dengan pemisah ribuan
    row.find('.harga').val(harga.toLocaleString('en-US'));
    });

    // Format ulang harga saat pengguna menginput angka
    $(document).on('blur', '.harga', function() {
        var harga = parseFloat($(this).val().replace(/,/g, '')) || 0;
        $(this).val(harga.toLocaleString('en-US'));
    });

</script>
@endsection
