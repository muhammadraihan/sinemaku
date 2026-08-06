@extends('layouts.page')

@section('title', 'Master Film')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class="subheader-icon fal fa-video"></i> Modul: <span class="fw-300">Master Film</span>
        <small>Kelola referensi nama film dan tanggal tayang.</small>
    </h1>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>Master Film <span class="fw-300"><i>List</i></span></h2>
                <div class="panel-toolbar">
                    <a class="nav-link active" href="{{route('masterfilm.create')}}">
                        <i class="fal fa-plus-circle"></i>
                        <span class="nav-link-text">Tambah Data</span>
                    </a>
                    <button class="btn btn-panel" data-action="panel-fullscreen" data-toggle="tooltip"
                        data-offset="0,10" data-original-title="Fullscreen"></button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <table id="datatable" class="table table-bordered table-hover table-striped w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Film</th>
                                <th>Tanggal Tayang</th>
                                <th width="120px">Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="" method="POST" class="delete-form">
    {{ csrf_field() }}
    <div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    Anda yakin ingin menghapus film ini? Film yang sudah digunakan di pelaporan tidak dapat dihapus.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary remove-data-from-delete-form" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Hapus Data</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script src="{{asset('js/datagrid/datatables/datatables.bundle.js')}}"></script>
<script>
    $(document).ready(function () {
        $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            order: [[2, 'desc']],
            ajax: {
                url: '{{route('masterfilm.index')}}',
                type: 'GET',
                dataType: 'json'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'tgl_tayang', name: 'tgl_tayang' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#datatable').on('click', '.delete-btn[data-url]', function (event) {
            event.preventDefault();
            const form = $('.delete-form');
            form.attr('action', $(this).data('url'));
            form.find('input[name="_method"]').remove();
            form.append('<input name="_method" type="hidden" value="DELETE">');
        });

        $('.remove-data-from-delete-form').on('click', function () {
            $('.delete-form').find('input[name="_method"]').remove();
        });
    });
</script>
@endsection
