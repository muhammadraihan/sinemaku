@extends('layouts.page')

@section('title', 'Tipe Tiket Create')

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
            <h2>Tambah Baru <span class="fw-300"><i>Tipe Tiket </i></span></h2>
            <div class="panel-toolbar">
                <a class="nav-link active" href="{{route('typetiket.index')}}"><i class="fal fa-arrow-alt-left">
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
                {!! Form::open(['route' => 'typetiket.store','id'=>'forms','method' => 'POST','class' =>
                'needs-validation','dropzone', 'forms','novalidate','enctype' => 'multipart/form-data']) !!}
                <div class="wizard-note alert alert-info"><strong>Tambah beberapa tipe sekaligus.</strong> Pilih kategori sekali, lalu tambahkan semua format tiket yang dibutuhkan.</div>
                <div class="form-group col-md-6 mb-3">
                    {{ Form::label('kategori','Kategori Bioskop',['class' => 'required form-label'])}}
                    {!! Form::select('kategori', $bioskop_kategori, old('kategori'), ['id'=>'kategori','class'=>'custom-select','required','placeholder'=>'Pilih Kategori Bioskop ...'])!!}
                </div>
                <div id="ticket-rows"></div>
                <button type="button" id="add-ticket-row" class="btn btn-outline-primary mb-3"><i class="fal fa-plus mr-1"></i>Tambah tipe tiket</button>
            <div class="panel-content border-faded border-left-0 border-right-0 border-bottom-0 d-flex flex-row align-items-center">
                <button class="btn btn-primary ml-auto" type="submit"><i class="fal fa-save mr-1"></i>Simpan tipe tiket</button>
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
        var ticketIndex = 0;
        function addTicketRow(value) {
            var index = ticketIndex++;
            $('#ticket-rows').append('<div class="card border mb-3 ticket-row"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-2"><strong>Tipe tiket '+(index+1)+'</strong><button type="button" class="btn btn-sm btn-outline-danger remove-ticket-row"><i class="fal fa-times"></i> Hapus</button></div><input name="ticket_types['+index+'][name]" value="'+$('<div>').text(value || '').html()+'" class="form-control" required placeholder="Contoh: REGULAR" style="text-transform:uppercase"></div></div>');
        }
        var oldTickets = @json(old('ticket_types', [['name' => '']]));
        oldTickets.forEach(function(row){ addTicketRow(row.name); });
        $('#add-ticket-row').on('click', function(){ addTicketRow(''); });
        $(document).on('click', '.remove-ticket-row', function(){ if ($('.ticket-row').length > 1) $(this).closest('.ticket-row').remove(); });
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