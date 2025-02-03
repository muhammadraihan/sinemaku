@extends('layouts.page')

@section('title', 'Kapasitas Edit')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<link rel="stylesheet" media="screen, print"
    href="{{asset('css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endsection

@section('content')
<div class="col-xxl">
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
        <h2>Edit <span class="fw-300"><i>Kapasitas</i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('kapasitas.index')}}"><i class="fal fa-arrow-alt-left">
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
                {!! Form::open(['route' => ['kapasitas.update',$kapasitas->uuid],'method' => 'PUT','class' =>
                'needs-validation','novalidate', 'enctype' => 'multipart/form-data']) !!}
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('kategori','Kategori Bioskop',['class' => 'required form-label'])}}
                        {!! Form::select('kategori', $bioskop_kategori, $kapasitas->kategori,
                        ['id'=>'kategori','class'
                        => 'custom-select'.($errors->has('kategori') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Kategori Bioskop ...'])!!}
                        @if ($errors->has('kategori'))
                        <div class="invalid-feedback">{{ $errors->first('kategori') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('nama_bioskop','Nama Bioskop',['class' => 'required form-label'])}}
                        {!! Form::select('nama_bioskop', $nama_bioskop, $kapasitas->nama_bioskop,
                        ['id'=>'nama_bioskop','class'
                        => 'custom-select'.($errors->has('nama_bioskop') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Nama Bioskop ...'])!!}
                        @if ($errors->has('nama_bioskop'))
                        <div class="invalid-feedback">{{ $errors->first('nama_bioskop') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('kota','Kota',['class' => 'required form-label'])}}
                        {!! Form::select('kota', $kota, $kapasitas->kota,
                        ['id'=>'kota','class'
                        => 'custom-select'.($errors->has('kota') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Kota ...'])!!}
                        @if ($errors->has('kota'))
                        <div class="invalid-feedback">{{ $errors->first('kota') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('type_tiket','Tipe Tiket',['class' => 'required form-label'])}}
                        {!! Form::select('type_tiket', $type_tiket, $kapasitas->type_tiket,
                        ['id'=>'type_tiket','class'
                        => 'custom-select'.($errors->has('type_tiket') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Tipe Tiket ...'])!!}
                        @if ($errors->has('type_tiket'))
                        <div class="invalid-feedback">{{ $errors->first('type_tiket') }}</div>
                        @endif
                    </div>
                </div>
                <hr style="border: 1px dashed: color: black">
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('studio','Studio',['class' => 'required form-label'])}}
                        {!! Form::select('studio', array('1' => 'Studio - 1', '2' => 'Studio - 2', '3' => 'Studio - 3', '4' => 'Studio - 4', '5' => 'Studio - 5', '6' => 'Studio - 6', '7' => 'Studio - 7', '8' => 'Studio - 8', '9' => 'Studio - 9', '10' => 'Studio - 10'), $kapasitas->studio,
                        ['id'=>'studio','class'
                        => 'custom-select'.($errors->has('studio') ? 'is-invalid':'') ,'required'
                        => '', 'placeholder' => 'Pilih Studio ...'])!!}
                        @if ($errors->has('studio'))
                        <div class="invalid-feedback">{{ $errors->first('studio') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('kapasitas','Kapasitas',['class' => 'required form-label'])}}
                        {{ Form::text('kapasitas',$kapasitas->kapasitas,['placeholder' => 'Kapasitas','class' => 'form-control '.($errors->has('kapasitas') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('kapasitas'))
                        <div class="invalid-feedback">{{ $errors->first('kapasitas') }}</div>
                        @endif
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
<script src="{{asset('js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js')}}"></script>
<script>
    $(document).ready(function(){
        $('#kategori').select2();
        $('#nama_bioskop').select2();
        $('#type_tiket').select2();
        $('#kota').select2();
        $('#studio').select2();

        $('#kategori').change(function(){
            var kategori = $(this).val();

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

            $.ajax({
                url: "{{ route('ref.kota') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                    bioskop: bioskop
                },
                success: function(data) {
                    $("#kota").empty();

                    // $("#kota").append('<option value="">Choose Nama Kota ...</option>');

                    $.each(data, function(key, value) {
                        $("#kota").append('<option value="' + key + '">' + value + '</option>');
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

        // $('#tax').on('input', function() {
        //     var net = parseFloat($('#net').val().replace(/,/g, '')) || 0;
        //     var tax = parseFloat($('#tax').val()) || 0;
        //     var gross = parseFloat($('#gross').val().replace(/,/g, '')) || 0;

        //     var total = gross - (gross * tax / 100);
        //     $('#net').val(total.toLocaleString('en-US'));
        // });

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
        
        // Generate a password string
        function randString(){
            var chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNP123456789";
            var string_length = 8;
            var randomstring = '';
            for (var i = 0; i < string_length; i++) {
                var rnum = Math.floor(Math.random() * chars.length);
                randomstring += chars.substring(rnum, rnum + 1);
            }
            return randomstring;
        }
        
        // Create a new password
        $(".getNewPass").click(function(){
            var field = $('#password').closest('div').find('input[name="password"]');
            field.val(randString(field));
        });

        //Enable input and button change password
        $('#enablePassChange').click(function() {
            if ($(this).is(':checked')) {
                $('#passwordForm').attr('disabled',false); //enable input
                $('#getNewPass').attr('disabled',false); //enable button
            } else {
                    $('#passwordForm').attr('disabled', true); //disable input
                    $('#getNewPass').attr('disabled', true); //disable button
            }
        });
    });
</script>
@endsection