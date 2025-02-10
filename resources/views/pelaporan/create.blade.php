@extends('layouts.page')

@section('title', 'Laporan Create')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/dropzone/dropzone.css')}}">
<link rel="stylesheet" media="screen, print"
    href="{{asset('css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
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
                        {{ Form::text('nama_film',null,['placeholder' => 'Nama Film','class' => 'form-control nama_film'.($errors->has('nama_film') ? 'is-invalid':''),'required', 'style' => 'text-transform: uppercase;'])}}
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
                        {{ Form::label('type_tiket','Tipe Tiket',['class' => 'required form-label'])}}
                        {!! Form::select('type_tiket', $type_tiket, '',
                        ['id'=>'type_tiket','class'
                        => 'custom-select'.($errors->has('type_tiket') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Tipe Tiket ...'])!!}
                        @if ($errors->has('type_tiket'))
                        <div class="invalid-feedback">{{ $errors->first('type_tiket') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('studio','Studio',['class' => 'required form-label'])}}
                        {!! Form::select('studio', $studio, '',
                        ['id'=>'studio','class'
                        => 'custom-select'.($errors->has('studio') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Studio ...'])!!}
                        @if ($errors->has('studio'))
                        <div class="invalid-feedback">{{ $errors->first('studio') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('provinsi','Provinsi',['class' => 'required form-label'])}}
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
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('show','Show',['class' => 'required form-label'])}}
                        {!! Form::select('show', array('1' => 'Show - 1', '2' => 'Show - 2', '3' => 'Show - 3', '4' => 'Show - 4', '5' => 'Show - 5', '6' => 'Show - 6', '7' => 'Show - 7', '8' => 'Show - 8'), '',
                        ['id'=>'show','class'
                        => 'custom-select'.($errors->has('show') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Show ...'])!!}
                        @if ($errors->has('show'))
                        <div class="invalid-feedback">{{ $errors->first('show') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('jam_tayang','Jam',['class' => 'form-label'])}}
                        {{ Form::time('jam_tayang',null,['placeholder' => 'Jam','class' => 'form-control '.($errors->has('jam_tayang') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('jam_tayang'))
                        <div class="invalid-feedback">{{ $errors->first('jam_tayang') }}</div>
                        @endif
                    </div>
                </div>
                <hr style="border: 1px dashed: color: black">

                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('harga','Harga',['class' => 'required form-label'])}}
                        {{ Form::text('harga',null,['placeholder' => 'Harga','class' => 'form-control '.($errors->has('harga') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('harga'))
                        <div class="invalid-feedback">{{ $errors->first('harga') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('jumlah','Qty',['class' => 'required form-label'])}}
                        {{ Form::text('jumlah',null,['placeholder' => 'Qty','class' => 'form-control '.($errors->has('jumlah') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('jumlah'))
                        <div class="invalid-feedback">{{ $errors->first('jumlah') }}</div>
                        @endif
                    </div>
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('gross','Gross',['class' => 'required form-label'])}}
                    {{ Form::text('gross',null,['placeholder' => 'Gross','class' => 'form-control '.($errors->has('gross') ? 'is-invalid':''),'required', 'readonly' => 'true'])}}
                    @if ($errors->has('gross'))
                    <div class="invalid-feedback">{{ $errors->first('gross') }}</div>
                    @endif
                </div>
                <hr style="border: 1px dashed: color: black">
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('tax','Tax',['class' => 'form-label'])}}
                    {{ Form::text('tax',null,['placeholder' => 'Tax','class' => 'form-control '.($errors->has('tax') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('tax'))
                    <div class="invalid-feedback">{{ $errors->first('tax') }}</div>
                    @endif
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('net','Net',['class' => 'required form-label'])}}
                    {{ Form::text('net',null,['placeholder' => 'Net','class' => 'form-control '.($errors->has('net') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('net'))
                    <div class="invalid-feedback">{{ $errors->first('net') }}</div>
                    @endif
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
        $('#kategori').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();
        $('#kota').select2();
        $('#show').select2();
        $('#studio').select2();

        $('#kategori').change(function(){
            var kategori = $(this).val();
            $('#kota').empty();
            $('#studio').empty();

            $.ajax({
                url: "{{ route('ref.cinema') }}",
                type: 'GET',
                data: {
                    kategori: kategori
                },
                success: function(data) {
                    $("#nama_bioskop").empty();

                    $("#nama_bioskop").append('<option value="">Pilih Nama Bioskop ...</option>');

                    $.each(data, function(key, value) {
                        $("#nama_bioskop").append('<option value="' + key + '">' + value + '</option>');
                    });
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

                    $("#type_tiket").append('<option value="">Pilih Tipe Tiket ...</option>');

                    $.each(data, function(key, value) {
                        $("#type_tiket").append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#nama_bioskop').change(function(){
            var kategori = $('#kategori').val();
            var bioskop = $(this).val();

            $('#studio').empty();
            $('#type_tiket').empty();

            $.ajax({
                url: "{{ route('ref.kota') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                    bioskop: bioskop
                },
                success: function(data) {
                    $("#kota").empty();

                    $("#kota").append('<option value="">Pilih Nama Kota ...</option>');

                    $.each(data, function(key, value) {
                        $("#kota").append('<option value="' + key + '">' + value + '</option>');
                    });
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

                    $("#type_tiket").append('<option value="">Pilih Tipe Tiket ...</option>');

                    $.each(data, function(key, value) {
                        $("#type_tiket").append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#kota').change(function(){
            var kota = $(this).val();

            $.ajax({
                url: "{{ route('ref.provinsi') }}",
                type: 'GET',
                data: {
                    kota : kota
                },
                success: function(data) {
                    console.log(data);
                    
                    $("#provinsi").empty();

                    if(data == ''){
                        $("#provinsi").val('');
                    }else{
                        $("#provinsi").val(data[0]['nama']);
                    }
                }
            });
        });

        $('#type_tiket').change(function(){
            var kategori = $('#kategori').val();
            var nama_bioskop = $('#nama_bioskop').val();
            var kota = $('#kota').val();
            var type_tiket = $('#type_tiket').val();

            $.ajax({
                url: "{{ route('ref.studio') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                    nama_bioskop: nama_bioskop,
                    kota: kota,
                    type_tiket: type_tiket,
                },
                success: function(data) {
                    $("#studio").empty();

                    $("#studio").append('<option value="">Pilih Studio ...</option>');

                    $.each(data, function(key, value) {
                        $("#studio").append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#harga, #jumlah, #tax').on('input', function() {
            // Ambil nilai harga dan jumlah, hilangkan pemisah ribuan
            var harga = parseFloat($('#harga').val().replace(/,/g, '')) || 0;
            var jumlah = parseFloat($('#jumlah').val()) || 0;
            var net = parseFloat($('#net').val().replace(/,/g, '')) || 0;
            // var tax = parseFloat($('#tax').val()) || 0;
            var tax = parseFloat($('#tax').val().replace(/,/g, ''));
            var gross = parseFloat($('#gross').val().replace(/,/g, '')) || 0;
            tax = tax ? parseFloat(tax) : '';

            // Hitung gross
            var gross = harga * jumlah;
            // var total = gross - (gross * tax / 100);
            var total = gross && tax ? gross - tax : gross;
            $('#net').val(total.toLocaleString('en-US'));

            // Format harga dengan pemisah ribuan
            $('#harga').val(harga.toLocaleString('en-US'));
            
            
            // Masukkan hasil ke field gross
            $('#gross').val(gross.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        });

        // Format ulang harga saat pengguna menginput angka
        $('#harga').on('blur', function() {
            var harga = parseFloat($(this).val().replace(/,/g, '')) || 0;
            $(this).val(harga.toLocaleString('en-US'));
        });

        $('#photo').change(function(){
            
            let reader = new FileReader();
         
            reader.onload = (e) => { 
         
              $('#preview-image-before-upload').attr('src', e.target.result); 
            }
         
            reader.readAsDataURL(this.files[0]); 
           
           });

           $('.tgl_tayang').datepicker({
            orientation: "bottom left",
            format:'dd-mm-yyyy', // Notice the Extra space at the beginning
            todayHighlight:'TRUE',
            autoclose: true,
            todayBtn: "linked",
            clearBtn: true,
        });

        $('.tgl_akhir').datepicker({
            orientation: "bottom left",
            format:'yyyy-mm-dd', // Notice the Extra space at the beginning
            todayHighlight:'TRUE',
            autoclose: true,
            todayBtn: "linked",
            clearBtn: true,
        });
    });
</script>
@endsection