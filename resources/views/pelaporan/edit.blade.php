@extends('layouts.page')

@section('title', 'Reporting Edit')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/formplugins/select2/select2.bundle.css')}}">
<link rel="stylesheet" media="screen, print"
    href="{{asset('css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endsection

@section('content')
<div class="row">
    <div class="col-xl-6">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>Edit <span class="fw-300"><i>Reporting</i></span></h2>
                <div class="panel-toolbar">
                    <a class="nav-link active" href="{{route('pelaporan.index')}}"><i class="fal fa-arrow-alt-left">
                        </i>
                        <span class="nav-link-text">Back</span>
                    </a>
                    <button class="btn btn-panel" data-action="panel-fullscreen" data-toggle="tooltip"
                        data-offset="0,10" data-original-title="Fullscreen"></button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="panel-tag">
                        Form with <code>*</code> can not be empty.
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
                    {!! Form::open(['route' => ['pelaporan.update',$pelaporan->uuid],'method' => 'PUT','class' =>
                    'needs-validation','novalidate', 'enctype' => 'multipart/form-data']) !!}
                    <div class="row">
                        <div class="form-group col-md-8 mb-3">
                            {{ Form::label('nama_film','Film Name',['class' => 'required form-label'])}}
                            {{ Form::text('nama_film',$pelaporan->nama_film,['placeholder' => 'Film Name','class' => 'form-control nama_film'.($errors->has('nama_film') ? 'is-invalid':''),'required', 'style' => 'text-transform: uppercase;'])}}
                            @if ($errors->has('nama_film'))
                            <div class="invalid-feedback">{{ $errors->first('nama_film') }}</div>
                            @endif
                        </div>
                    </div>
                    <hr style="border: 1px dashed: color: black">
                    <div class="row">
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('kategori','Cinema Type',['class' => 'required form-label'])}}
                            {!! Form::select('kategori', $bioskop_kategori, $pelaporan->kategori,
                            ['id'=>'kategori','class'
                            => 'custom-select'.($errors->has('kategori') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Choose Cinema Type ...'])!!}
                            @if ($errors->has('kategori'))
                            <div class="invalid-feedback">{{ $errors->first('kategori') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('kota','City',['class' => 'required form-label'])}}
                            {!! Form::select('kota', $kota, $pelaporan->kota,
                            ['id'=>'kota','class'
                            => 'custom-select'.($errors->has('kota') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Choose City ...'])!!}
                            @if ($errors->has('kota'))
                            <div class="invalid-feedback">{{ $errors->first('kota') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('nama_bioskop','Cinema Name',['class' => 'required form-label'])}}
                            {!! Form::select('nama_bioskop', $nama_bioskop, $pelaporan->nama_bioskop,
                            ['id'=>'nama_bioskop','class'
                            => 'custom-select'.($errors->has('nama_bioskop') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Choose Cinema Name ...'])!!}
                            @if ($errors->has('nama_bioskop'))
                            <div class="invalid-feedback">{{ $errors->first('nama_bioskop') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('type_tiket','Ticket Type',['class' => 'required form-label'])}}
                            {!! Form::select('type_tiket', $type_tiket, $pelaporan->type_tiket,
                            ['id'=>'type_tiket','class'
                            => 'custom-select'.($errors->has('type_tiket') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Choose Ticket Type ...'])!!}
                            @if ($errors->has('type_tiket'))
                            <div class="invalid-feedback">{{ $errors->first('type_tiket') }}</div>
                            @endif
                        </div>
                    </div>
                    <hr style="border: 1px dashed: color: black">
                    <div class="row">
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('tgl_tayang','Date',['class' => 'required form-label'])}}
                            {{ Form::text('tgl_tayang', $pelaporan->tgl_tayang,['placeholder' => 'Date','class' => 'form-control tgl_tayang'.($errors->has('tgl_tayang') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('tgl_tayang'))
                            <div class="invalid-feedback">{{ $errors->first('tgl_tayang') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('show','Show',['class' => 'required form-label'])}}
                            {!! Form::select('show', array('1' => 'Show - 1', '2' => 'Show - 2', '3' => 'Show - 3', '4' => 'Show - 4', '5' => 'Show - 5', '6' => 'Show - 6', '7' => 'Show - 7', '8' => 'Show - 8'), $pelaporan->show,
                            ['id'=>'show','class'
                            => 'custom-select'.($errors->has('show') ? 'is-invalid':'') ,'required'
                            => '', 'placeholder' => 'Choose Show ...'])!!}
                            @if ($errors->has('show'))
                            <div class="invalid-feedback">{{ $errors->first('show') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('jam_tayang','Time',['class' => 'required form-label'])}}
                            {{ Form::time('jam_tayang', $pelaporan->jam_tayang,['placeholder' => 'Time','class' => 'form-control '.($errors->has('jam_tayang') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('jam_tayang'))
                            <div class="invalid-feedback">{{ $errors->first('jam_tayang') }}</div>
                            @endif
                        </div>
                    </div>
                    <hr style="border: 1px dashed: color: black">

                    <div class="row">
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('harga','Price',['class' => 'required form-label'])}}
                            {{ Form::text('harga', $pelaporan->harga,['placeholder' => 'Price','class' => 'form-control '.($errors->has('harga') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('harga'))
                            <div class="invalid-feedback">{{ $errors->first('harga') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4 mb-3">
                            {{ Form::label('jumlah','Qty',['class' => 'required form-label'])}}
                            {{ Form::text('jumlah', $pelaporan->jumlah,['placeholder' => 'Qty','class' => 'form-control '.($errors->has('jumlah') ? 'is-invalid':''),'required'])}}
                            @if ($errors->has('jumlah'))
                            <div class="invalid-feedback">{{ $errors->first('jumlah') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('gross','Gross',['class' => 'required form-label'])}}
                        {{ Form::text('gross', $pelaporan->gross,['placeholder' => 'Gross','class' => 'form-control '.($errors->has('gross') ? 'is-invalid':''),'required', 'readonly' => 'true'])}}
                        @if ($errors->has('gross'))
                        <div class="invalid-feedback">{{ $errors->first('gross') }}</div>
                        @endif
                    </div>
                    <hr style="border: 1px dashed: color: black">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('tax','Tax',['class' => 'required form-label'])}}
                        {{ Form::text('tax', $pelaporan->tax,['placeholder' => 'Tax','class' => 'form-control '.($errors->has('tax') ? 'is-invalid':''),'required', 'maxlength' => '4'])}}
                        @if ($errors->has('tax'))
                        <div class="invalid-feedback">{{ $errors->first('tax') }}</div>
                        @endif
                        <i>*Masukkan dalam bentuk %</i>
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('net','Net',['class' => 'required form-label'])}}
                        {{ Form::text('net', $pelaporan->net,['placeholder' => 'Net','class' => 'form-control '.($errors->has('net') ? 'is-invalid':''),'required'])}}
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
        $('#show').select2();

        $('#kategori').change(function(){
            var kategori = $(this).val();

            $.ajax({
                url: "{{ route('ref.city') }}",
                type: 'GET',
                data: {
                    kategori: kategori,
                },
                success: function(data) {
                    $("#kota").empty();

                    $("#kota").append('<option value="">Choose City ...</option>');

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

                    $("#type_tiket").append('<option value="">Choose Ticket Type ...</option>');

                    $.each(data, function(key, value) {
                        $("#type_tiket").append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#kota').change(function(){
            var kategori = $('#kategori').val();
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

                    $("#nama_bioskop").append('<option value="">Choose Cinema Name ...</option>');

                    $.each(data, function(key, value) {
                        $("#nama_bioskop").append('<option value="' + key + '">' + value + '</option>');
                    });
                }
            });
        });

        $('#harga, #jumlah, #tax').on('input', function() {
            // Ambil nilai harga dan jumlah, hilangkan pemisah ribuan
            var harga = parseFloat($('#harga').val().replace(/,/g, '')) || 0;
            var jumlah = parseFloat($('#jumlah').val()) || 0;
            var net = parseFloat($('#net').val().replace(/,/g, '')) || 0;
            var tax = parseFloat($('#tax').val()) || 0;
            var gross = parseFloat($('#gross').val().replace(/,/g, '')) || 0;

            // Hitung gross
            var gross = harga * jumlah;
            var total = gross - (gross * tax / 100);
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
            format:'yyyy-mm-dd', // Notice the Extra space at the beginning
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