@extends('layouts.page')

@section('title', 'Cinema Create')

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
            <h2>Add New <span class="fw-300"><i>Cinema </i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('masterbioskop.index')}}"><i class="fal fa-arrow-alt-left">
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
                {!! Form::open(['route' => 'masterbioskop.store','id'=>'forms','method' => 'POST','class' =>
                'needs-validation','dropzone', 'forms','novalidate','enctype' => 'multipart/form-data']) !!}
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('type','Cinema',['class' => 'required form-label'])}}
                    {!! Form::select('type', $bioskop_kategori, '',
                    ['id'=>'type','class'
                    => 'custom-select'.($errors->has('type') ? 'is-invalid':'') ,'required'
                    => '', 'placeholder' => 'Pilih Cinema ...'])!!}
                    @if ($errors->has('type'))
                    <div class="invalid-feedback">{{ $errors->first('type') }}</div>
                    @endif
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('nama_bioskop','Cinema Name',['class' => 'required form-label'])}}
                    {{ Form::text('nama_bioskop',null,['placeholder' => 'Cinema Name','class' => 'form-control '.($errors->has('nama_bioskop') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('nama_bioskop'))
                    <div class="invalid-feedback">{{ $errors->first('nama_bioskop') }}</div>
                    @endif
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('kota','Cities',['class' => 'required form-label'])}}
                    {{ Form::text('kota',null,['placeholder' => 'Cities','class' => 'form-control '.($errors->has('kota') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('kota'))
                    <div class="invalid-feedback">{{ $errors->first('kota') }}</div>
                    @endif
                </div>
                <div class="form-group col-md-4 mb-3">
                    {{ Form::label('no_telephone','No. Telephone',['class' => 'required form-label'])}}
                    {{ Form::text('no_telephone',null,['placeholder' => 'No. Telephone','class' => 'form-control '.($errors->has('no_telephone') ? 'is-invalid':''),'required'])}}
                    @if ($errors->has('no_telephone'))
                    <div class="invalid-feedback">{{ $errors->first('no_telephone') }}</div>
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