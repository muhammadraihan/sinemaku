@extends('layouts.page')

@section('title', 'Vendor Management')

@section('css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/datagrid/datatables/datatables.bundle.css')}}">
<style>
    .vendor-toolbar-actions {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    body.sinemaku-modern-shell .vendor-toolbar-actions .vendor-toolbar-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 16px;
        border-radius: 14px;
        font-size: 0.875rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    body.sinemaku-modern-shell .vendor-toolbar-actions .vendor-toolbar-btn i {
        margin: 0;
        font-size: 0.95rem;
    }
</style>
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-users'></i> Modul: <span class='fw-300'>Vendor </span>
        <small>
            Modul Vendor.
        </small>
    </h1>
</div>
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
            <h2>
                    Vendor  <span class="fw-300"><i>List</i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="vendor-toolbar-actions">
                        <a class="btn btn-success vendor-toolbar-btn" href="{{route('vendor.export')}}">
                            <i class="fal fa-file-excel"></i>
                            <span>Export Excel</span>
                        </a>
                        <a class="btn btn-primary vendor-toolbar-btn" href="{{route('vendor.create')}}">
                            <i class="fal fa-plus-circle"></i>
                            <span>Tambah Data</span>
                        </a>
                    </div>
                    <button class="btn btn-panel" data-action="panel-fullscreen" data-toggle="tooltip"
                        data-offset="0,10" data-original-title="Fullscreen"></button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <!-- datatable start -->
                    <table id="datatable" class="table table-bordered table-hover table-striped w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Kategori</th>
                <th>Nama Perusahaan</th>
                <th>Email</th>
                <th>PIC</th>
                <th>No. Handphone</th>
                <th>Alamat</th>
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
    <!-- Delete modal center -->
    <div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">
                        Konfirmasi
                        <small class="m-0 text-muted">
                        </small>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    Anda yakin ingin menghapus data?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary remove-data-from-delete-form"
                        data-dismiss="modal">Close</button>
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
    $(document).ready(function(){
        $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });
     
     
       var table = $('#datatable').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "order": [[ 0, "asc" ]],
            "ajax":{
                url:'{{route('vendor.index')}}',
                type : "GET",
                dataType: 'json',
                error: function(data){
                    console.log(data);
                    }
            },
            "columns": [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'name', name: 'name'},
            {data: 'nama_perusahaan', name: 'nama_perusahaan'},
            {data: 'email', name: 'email'},
            {data: 'pic', name: 'pic'},
            {data: 'no_handphone', name: 'no_handphone'},
            {data: 'alamat', name: 'alamat'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    // Delete Data
    $('#datatable').on('click', '.delete-btn[data-url]', function (e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            var url = $(this).attr('data-url');
            var token = $(this).attr('data-token');
            console.log(id,url,token);
            
            $(".delete-form").attr("action",url);
            $('body').find('.delete-form').append('<input name="_token" type="hidden" value="'+ token +'">');
            $('body').find('.delete-form').append('<input name="_method" type="hidden" value="DELETE">');
            $('body').find('.delete-form').append('<input name="id" type="hidden" value="'+ id +'">');
        });
        // Clear Data When Modal Close
        $('.remove-data-from-delete-form').on('click',function() {
            $('body').find('.delete-form').find("input").remove();
        });
    });
</script>
@endsection
