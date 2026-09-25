@extends('layouts.page')

@section('title', 'Tambah Bioskop')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{ asset('css/formplugins/select2/select2.bundle.css') }}">
<style>
    .cinema-wizard { max-width: 1080px; margin: 0 auto; }
    .cinema-wizard .wizard-steps { display:flex; gap:0; border-bottom:1px solid #e5e9f2; margin-bottom:28px; }
    .cinema-wizard .wizard-step { flex:1; display:flex; align-items:center; gap:10px; padding:14px 16px; border:0; background:transparent; color:#7d8797; text-align:left; font-weight:600; }
    .cinema-wizard .wizard-step .step-no { width:30px; height:30px; border-radius:50%; display:grid; place-items:center; background:#eef1f6; color:#687385; font-size:12px; }
    .cinema-wizard .wizard-step.active { color:#2769c8; border-bottom:3px solid #2769c8; }
    .cinema-wizard .wizard-step.active .step-no { background:#2769c8; color:white; }
    .cinema-wizard .wizard-step.done .step-no { background:#18a06e; color:white; }
    .cinema-wizard .step-copy small { display:block; font-weight:400; color:#97a0ad; margin-top:2px; }
    .cinema-wizard .wizard-pane { display:none; }
    .cinema-wizard .wizard-pane.active { display:block; animation:wizardIn .18s ease; }
    .cinema-wizard .wizard-note { border-left:3px solid #4f8fe8; background:#f4f8ff; padding:14px 16px; color:#41536d; margin-bottom:22px; }
    .cinema-wizard .repeat-card { border:1px solid #e3e8f0; border-left:4px solid #4f8fe8; padding:16px; margin-bottom:12px; background:#fff; }
    .cinema-wizard .repeat-card.capacity-card { border-left-color:#18a06e; }
    .cinema-wizard .repeat-card .row-label { font-size:11px; font-weight:700; color:#8490a1; letter-spacing:.08em; text-transform:uppercase; }
    .cinema-wizard .empty-repeat { border:1px dashed #b7c3d4; padding:23px; text-align:center; color:#718096; background:#fbfcfe; }
    .cinema-wizard .wizard-footer { margin-top:28px; padding-top:20px; border-top:1px solid #e8ecf2; display:flex; justify-content:space-between; gap:12px; }
    @keyframes wizardIn { from { opacity:0; transform:translateY(4px) } to { opacity:1; transform:none } }
    @media (max-width: 650px) { .cinema-wizard .wizard-steps { overflow:auto; } .cinema-wizard .wizard-step { min-width:185px; } .cinema-wizard .step-copy small { display:none; } }
</style>
@endsection

@section('content')
<div class="col-xxl cinema-wizard">
    <div class="panel">
        <div class="panel-hdr">
            <h2>Tambah Baru <span class="fw-300"><i>Bioskop & Master Pendukung</i></span></h2>
            <div class="panel-toolbar"><a class="nav-link active" href="{{ route('masterbioskop.index') }}"><i class="fal fa-arrow-alt-left"></i><span class="nav-link-text">Kembali</span></a></div>
        </div>
        <div class="panel-container show"><div class="panel-content">
            @if ($errors->any())
                <div class="alert alert-danger"><strong>Data belum dapat disimpan.</strong><ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="wizard-steps" role="tablist">
                <button type="button" class="wizard-step active" data-step="0"><span class="step-no">1</span><span class="step-copy">Bioskop<small>Identitas dan lokasi</small></span></button>
                <button type="button" class="wizard-step" data-step="1"><span class="step-no">2</span><span class="step-copy">Tipe tiket<small>Tambah semua format tiket</small></span></button>
                <button type="button" class="wizard-step" data-step="2"><span class="step-no">3</span><span class="step-copy">Kapasitas<small>Studio dan jumlah kursi</small></span></button>
            </div>

            {!! Form::open(['route' => 'masterbioskop.store', 'method' => 'POST', 'id' => 'cinema-wizard-form']) !!}
                <div class="wizard-pane active" data-pane="0">
                    <div class="wizard-note"><strong>Mulai dari bioskop.</strong> Semua tipe tiket dan kapasitas yang Anda tambahkan pada langkah berikutnya akan otomatis terhubung ke bioskop ini.</div>
                    <div class="row">
                        <div class="form-group col-md-6"><label class="required form-label">Kategori Bioskop</label>{!! Form::select('type', $bioskop_kategori, old('type'), ['id'=>'type','class'=>'custom-select','required','placeholder'=>'Pilih kategori bioskop...']) !!}</div>
                        <div class="form-group col-md-6"><label class="required form-label">Nama Bioskop</label><input name="nama_bioskop" value="{{ old('nama_bioskop') }}" class="form-control" required placeholder="Contoh: Living Plaza Balikpapan"></div>
                        <div class="form-group col-md-4"><label class="required form-label">Kota</label><input name="kota" value="{{ old('kota') }}" class="form-control" required placeholder="Contoh: Balikpapan"></div>
                        <div class="form-group col-md-4"><label class="form-label">Pajak (%)</label><input name="pajak" value="{{ old('pajak') }}" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="Contoh: 10"></div>
                        <div class="form-group col-md-4"><label class="form-label">No. Telepon</label><input name="no_telephone" value="{{ old('no_telephone') }}" class="form-control" placeholder="Opsional"></div>
                    </div>
                </div>

                <div class="wizard-pane" data-pane="1">
                    <div class="wizard-note"><strong>Tambahkan tipe tiket yang akan digunakan.</strong> Misalnya REGULAR, VIP, PREMIERE, atau format khusus distributor. Anda dapat melewati langkah ini jika belum siap menambahkan tipe tiket.</div>
                    <div id="ticket-types-list"></div>
                    <div id="ticket-types-empty" class="empty-repeat">Belum ada tipe tiket. Klik <strong>Tambah tipe tiket</strong> untuk membuat baris pertama.</div>
                    <button type="button" id="add-ticket-type" class="btn btn-outline-primary mt-3"><i class="fal fa-plus mr-1"></i>Tambah tipe tiket</button>
                </div>

                <div class="wizard-pane" data-pane="2">
                    <div class="wizard-note"><strong>Atur kapasitas setiap studio.</strong> Pilih tipe tiket yang ditambahkan pada langkah sebelumnya, isi nomor studio dan jumlah kursi. Anda dapat menambahkan sebanyak yang diperlukan.</div>
                    <div id="capacity-list"></div>
                    <div id="capacity-empty" class="empty-repeat">Belum ada kapasitas. Tambahkan tipe tiket terlebih dahulu, lalu klik <strong>Tambah kapasitas</strong>.</div>
                    <button type="button" id="add-capacity" class="btn btn-outline-success mt-3"><i class="fal fa-plus mr-1"></i>Tambah kapasitas</button>
                </div>

                <div class="wizard-footer">
                    <button type="button" id="wizard-prev" class="btn btn-outline-secondary d-none"><i class="fal fa-arrow-left mr-1"></i>Kembali</button>
                    <span></span>
                    <button type="button" id="wizard-next" class="btn btn-primary">Lanjut ke tipe tiket<i class="fal fa-arrow-right ml-1"></i></button>
                    <button type="submit" id="wizard-submit" class="btn btn-success d-none"><i class="fal fa-save mr-1"></i>Simpan semua data</button>
                </div>
            {!! Form::close() !!}
        </div></div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/formplugins/select2/select2.bundle.js') }}"></script>
<script>
$(function () {
    $('#type').select2();
    var current = 0, ticketIndex = 0, capacityIndex = 0;
    var oldTickets = @json(old('ticket_types', [])), oldCapacities = @json(old('capacities', []));
    var categoryTickets = {};
    function escapeHtml(value) { return $('<div>').text(value || '').html(); }
    function ticketOptions(selected) {
        var options = '<option value="">Pilih tipe tiket...</option>';
        $.each(categoryTickets, function (uuid, name) { options += '<option value="'+escapeHtml(uuid)+'" '+(String(uuid)===String(selected)?'selected':'')+'>'+escapeHtml(name)+'</option>'; });
        $('#ticket-types-list input.ticket-name').each(function () { var i=$(this).data('index'), name=$(this).val().trim(); if (name) { var value='new:'+i; options += '<option value="'+value+'" '+(value===String(selected)?'selected':'')+'>'+escapeHtml(name)+' (baru)</option>'; } });
        return options;
    }
    function syncCapacityTickets() { $('#capacity-list select.ticket-select').each(function(){ var selected=$(this).val(); $(this).html(ticketOptions(selected)); }); $('#capacity-empty').toggle($('#capacity-list .capacity-card').length === 0); }
    function loadCategoryTickets() {
        var kategori = $('#type').val();
        if (!kategori) { categoryTickets = {}; syncCapacityTickets(); return; }
        $.get("{{ route('ref.type') }}", { kategori: kategori }, function (data) { categoryTickets = data || {}; syncCapacityTickets(); });
    }
    function addTicket(value) { var i=ticketIndex++; $('#ticket-types-empty').hide(); $('#ticket-types-list').append('<div class="repeat-card ticket-card"><div class="d-flex justify-content-between align-items-center mb-2"><span class="row-label">Tipe tiket '+(i+1)+'</span><button type="button" class="btn btn-sm btn-outline-danger remove-ticket"><i class="fal fa-times"></i> Hapus</button></div><input data-index="'+i+'" name="ticket_types['+i+'][name]" value="'+escapeHtml(value || '')+'" class="form-control ticket-name" placeholder="Opsional — contoh: REGULAR" style="text-transform:uppercase"></div>'); syncCapacityTickets(); }
    function addCapacity(row) { row=row||{}; var i=capacityIndex++; $('#capacity-empty').hide(); $('#capacity-list').append('<div class="repeat-card capacity-card"><div class="d-flex justify-content-between align-items-center mb-2"><span class="row-label">Kapasitas '+(i+1)+'</span><button type="button" class="btn btn-sm btn-outline-danger remove-capacity"><i class="fal fa-times"></i> Hapus</button></div><div class="row"><div class="col-md-4 form-group mb-md-0"><label class="form-label">Tipe tiket</label><select name="capacities['+i+'][ticket_type_ref]" class="custom-select ticket-select" required>'+ticketOptions(row.ticket_type_ref || '')+'</select></div><div class="col-md-4 form-group mb-md-0"><label class="form-label">Studio</label><input name="capacities['+i+'][studio]" value="'+escapeHtml(row.studio || '')+'" class="form-control" required placeholder="Contoh: 1 atau CINEMA 01"></div><div class="col-md-4 form-group mb-md-0"><label class="form-label">Kapasitas kursi</label><input name="capacities['+i+'][kapasitas]" value="'+escapeHtml(row.kapasitas || '')+'" type="number" min="0" class="form-control" required placeholder="Contoh: 120"></div></div></div>'); }
    (oldTickets.length ? oldTickets : []).forEach(function(row){ addTicket(row.name); });
    (oldCapacities.length ? oldCapacities : []).forEach(addCapacity);
    loadCategoryTickets();
    $('#type').on('change', loadCategoryTickets);
    $('#add-ticket-type').on('click', function(){ addTicket(''); });
    $('#add-capacity').on('click', function(){ addCapacity({}); });
    $(document).on('input', '.ticket-name', syncCapacityTickets).on('click', '.remove-ticket', function(){ $(this).closest('.ticket-card').remove(); $('#ticket-types-empty').toggle(!$('#ticket-types-list .ticket-card').length); syncCapacityTickets(); }).on('click', '.remove-capacity', function(){ $(this).closest('.capacity-card').remove(); $('#capacity-empty').toggle(!$('#capacity-list .capacity-card').length); });
    function validateStep(step) { var valid=true; $('[data-pane="'+step+'"]').find('[required]').each(function(){ if (!this.checkValidity()) { this.reportValidity(); valid=false; return false; } }); return valid; }
    function showStep(step) { current=step; $('.wizard-pane').removeClass('active'); $('[data-pane="'+step+'"]').addClass('active'); $('.wizard-step').removeClass('active').each(function(){ $(this).toggleClass('done', Number($(this).data('step')) < step); }); $('.wizard-step[data-step="'+step+'"]').addClass('active'); $('#wizard-prev').toggleClass('d-none', step===0); $('#wizard-next').toggleClass('d-none', step===2).html(step===0?'Lanjut ke tipe tiket<i class="fal fa-arrow-right ml-1"></i>':'Lanjut ke kapasitas<i class="fal fa-arrow-right ml-1"></i>'); $('#wizard-submit').toggleClass('d-none', step!==2); }
    $('#wizard-next').on('click', function(){ if (validateStep(current)) showStep(Math.min(2,current+1)); }); $('#wizard-prev').on('click', function(){ showStep(Math.max(0,current-1)); }); $('.wizard-step').on('click', function(){ var target=Number($(this).data('step')); if (target<=current || validateStep(current)) showStep(target); });
});
</script>
@endsection
