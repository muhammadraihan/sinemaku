@extends('layouts.page')

@section('title', 'Tambah Master Film')

@section('content')
<div class="col-xxl">
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>Tambah Baru <span class="fw-300"><i>Master Film</i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('masterfilm.index')}}">
                    <i class="fal fa-arrow-alt-left"></i>
                    <span class="nav-link-text">Kembali</span>
                </a>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="panel-tag">Field dengan <code>*</code> tidak boleh kosong.</div>

                {!! Form::open(['route' => 'masterfilm.store', 'method' => 'POST', 'class' => 'needs-validation', 'novalidate']) !!}
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        {{ Form::label('name', 'Nama Film', ['class' => 'required form-label']) }}
                        {{ Form::text('name', old('name'), [
                            'placeholder' => 'Nama Film',
                            'class' => 'form-control'.($errors->has('name') ? ' is-invalid' : ''),
                            'required',
                            'style' => 'text-transform: uppercase;'
                        ]) }}
                        @if ($errors->has('name'))
                        <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        {{ Form::label('tgl_tayang', 'Tanggal Tayang', ['class' => 'required form-label']) }}
                        <input type="date" name="tgl_tayang" value="{{old('tgl_tayang')}}"
                            class="form-control{{$errors->has('tgl_tayang') ? ' is-invalid' : ''}}" required>
                        @if ($errors->has('tgl_tayang'))
                        <div class="invalid-feedback">{{ $errors->first('tgl_tayang') }}</div>
                        @endif
                    </div>
                </div>
                <div class="panel-content border-faded border-left-0 border-right-0 border-bottom-0 d-flex flex-row align-items-center">
                    <button class="btn btn-primary ml-auto" type="submit">Simpan</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection
