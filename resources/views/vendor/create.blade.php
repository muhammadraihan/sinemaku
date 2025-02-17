@extends('layouts.page')

@section('title', 'Vendor Tambah')

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
            <h2>Tambah Baru <span class="fw-300"><i>Vendor </i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('vendor.index')}}"><i class="fal fa-arrow-alt-left">
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
                {!! Form::open(['route' => 'vendor.store','id'=>'forms','method' => 'POST','class' =>
                'needs-validation','dropzone', 'forms','novalidate','enctype' => 'multipart/form-data']) !!}
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('name','Kategori Bioskop',['class' => 'required form-label'])}}
                        {{ Form::text('name',null,['placeholder' => 'Kategori Bioskop','class' => 'form-control '.($errors->has('name') ? 'is-invalid':''),'required', 'style' => 'text-transform: uppercase;'])}}
                        @if ($errors->has('name'))
                        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('pic','Nama PIC',['class' => 'required form-label'])}}
                        {{ Form::text('pic',null,['placeholder' => 'Nama PIC','class' => 'form-control '.($errors->has('pic') ? 'is-invalid':''),'required', 'style' => 'text-transform: uppercase;'])}}
                        @if ($errors->has('pic'))
                        <div class="invalid-feedback">{{ $errors->first('pic') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('no_handphone','No. Handphone',['class' => 'required form-label'])}}
                        {{ Form::text('no_handphone',null,['placeholder' => 'No. Handphone','class' => 'form-control '.($errors->has('no_handphone') ? 'is-invalid':''),'required'])}}
                        @if ($errors->has('no_handphone'))
                        <div class="invalid-feedback">{{ $errors->first('no_handphone') }}</div>
                        @endif
                    </div>
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('alamat','alamat',['class' => 'required form-label'])}}
                    {{ Form::textarea('alamat',null,['placeholder' => 'alamat','class' => 'form-control '.($errors->has('alamat') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('alamat'))
                    <div class="invalid-feedback">{{ $errors->first('alamat') }}</div>
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
        $('.kategori').select2();
        $('#type').select2();
        $('#photo').change(function(){
            
            let reader = new FileReader();
         
            reader.onload = (e) => { 
         
              $('#preview-image-before-upload').attr('src', e.target.result); 
            }
         
            reader.readAsDataURL(this.files[0]); 
           
           });

           $('.tgl_awal').datepicker({
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
    });
</script>
@endsection