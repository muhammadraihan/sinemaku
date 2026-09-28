/* Shared, server-authoritative inline correction editor for PDF and Excel previews. */
(function (window, $) {
    'use strict';
    function escape(value) { return $('<div>').text(value == null ? '' : value).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    window.ImportPreviewEditor = {
        destroy: function (selector) {
            if ($.fn.DataTable.isDataTable(selector)) $(selector).DataTable().destroy();
        },
        mount: function (selector, response, provider, url, refresh) {
            var table = $(selector), pdf = / PDF$/.test(provider), modal = table.closest('.modal');
            var confirm = modal.find('[id^="btn-confirm-"]');
            var fields = pdf ? [[1,'tanggal','Tanggal','date'],[2,'jam_tayang','Jam','time'],[7,'studio','Studio','text'],[8,'type_tiket','Tipe tiket','text'],[9,'harga','Harga','number'],[10,'jumlah','Admits','number']] : [[2,'tgl_tayang','Tanggal','date'],[3,'nama_film','Film','text'],[4,'source_cinema','Bioskop','text'],[5,'source_city','Kota','text'],[6,'studio','Studio','text'],[7,'jam_tayang','Jam','time'],[8,'show','Show','select'],[9,'ticket_name','Tipe tiket','text'],[10,'harga','Harga','number'],[11,'jumlah','Jumlah','number']];
            if (!table.find('thead .correction-heading').length) table.find('thead tr').append('<th class="correction-heading">Koreksi</th>');
            table.find('tbody tr').each(function (index) {
                var row = response.preview[index];
                $(this).attr('data-row-id', row.row_id).append('<td><button type="button" class="btn btn-sm btn-outline-primary preview-edit">Koreksi</button></td>');
            });
            var dt = table.DataTable({pageLength: 10, lengthMenu: [10,25,50,100], order: [], autoWidth: false, columnDefs:[{targets:-1,orderable:false,searchable:false}], language:{search:'Cari:',lengthMenu:'Tampilkan _MENU_ baris',info:'_START_–_END_ dari _TOTAL_ baris',emptyTable:'Tidak ada detail',paginate:{previous:'Sebelumnya',next:'Berikutnya'}}});
            var editing = null, busy = false, disabled = [];
            function lock() {
                disabled = [];
                modal.find('button, select, input').not(table.find('tbody :input')).each(function () { disabled.push([this, this.disabled]); this.disabled = true; });
                $(dt.table().container()).find('.dataTables_filter input, .dataTables_length select, .paginate_button').css('pointer-events','none').attr('aria-disabled','true');
                confirm.prop('disabled', true);
            }
            function unlock() {
                disabled.forEach(function (item) { item[0].disabled = item[1]; });
                $(dt.table().container()).find('.dataTables_filter input, .dataTables_length select, .paginate_button').css('pointer-events','').removeAttr('aria-disabled');
            }
            modal.off('hide.bs.modal.correction').on('hide.bs.modal.correction', function (event) { if (editing) event.preventDefault(); });
            table.off('.correction').on('click.correction', '.preview-edit', function () {
                if (editing) return;
                var tr = $(this).closest('tr'), id = tr.attr('data-row-id');
                var row = response.preview.find(function (item) { return item.row_id === id; });
                editing = {tr:tr,row:row,html:tr.html()};
                lock();
                fields.forEach(function (field) {
                    if (field[1] === 'harga' && provider === 'XXI' && (response.source_type === 'pdf' || row.price_from_master)) return;
                    var value = row[field[1]], control;
                    if (field[3] === 'select') {
                        control = '<select class="form-control form-control-sm" data-field="'+field[1]+'" aria-label="'+field[2]+'">';
                        for (var i=1; i<=50; i++) control += '<option value="'+i+'" '+(String(value)===String(i)?'selected':'')+'>'+i+'</option>';
                        control += '</select>';
                    } else {
                        control = '<input class="form-control form-control-sm" style="min-width:110px" data-field="'+field[1]+'" aria-label="'+field[2]+'" type="'+field[3]+'" '+(field[3]==='number'?'min="0" step="'+(field[1]==='harga'?'0.01':'1')+'" ':'')+'value="'+escape(value)+'">';
                    }
                    tr.children('td').eq(field[0]).html(control);
                });
                tr.children('td').last().html('<label class="d-block">Alasan koreksi<input class="form-control form-control-sm correction-reason" maxlength="500" placeholder="Wajib, minimal 3 karakter"></label><button type="button" class="btn btn-sm btn-primary preview-save">Simpan koreksi</button> <button type="button" class="btn btn-sm btn-secondary preview-cancel">Batal</button><div class="text-danger correction-error" role="alert" style="white-space:normal;min-width:220px"></div>');
            }).on('click.correction', '.preview-cancel', function () {
                if (busy || !editing) return;
                editing.tr.html(editing.html); editing = null; unlock();
            }).on('click.correction', '.preview-save', function () {
                if (busy || !editing) return;
                var changes = {}, tr = editing.tr;
                tr.find('[data-field]').each(function () { var key = $(this).data('field'), value = $(this).val(); if (String(editing.row[key] == null ? '' : editing.row[key]) !== value) changes[key] = value; });
                var reason = tr.find('.correction-reason').val().trim();
                if (!Object.keys(changes).length || reason.length < 3) { tr.find('.correction-error').text('Ubah setidaknya satu nilai dan isi alasan koreksi.'); return; }
                busy = true; tr.find(':input').prop('disabled', true); tr.find('.preview-save').text('Menyimpan...');
                $.ajax({url:url,method:'POST',contentType:'application/json',headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')},data:JSON.stringify({token:response.token,provider:provider,row_id:editing.row.row_id,changes:changes,reason:reason})}).done(function (next) {
                    busy = false; editing = null; unlock();
                    refresh(next);
                    Swal.fire({target:modal.find('.cinepolis-preview-swal-target')[0],icon:next.blocking_issues.length?'warning':'success',title:'Koreksi tersimpan',text:next.blocking_issues.length?'Periksa issue. Import tetap diblokir sampai rekonsiliasi dan mapping selesai.':'Preview diperbarui. Laporan belum diimport.'});
                }).fail(function (xhr) {
                    busy = false; tr.find(':input').prop('disabled', false); tr.find('.preview-save').text('Simpan koreksi');
                    var json = xhr.responseJSON || {}, messages = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message || 'Koreksi gagal. Periksa koneksi lalu coba lagi.');
                    tr.find('.correction-error').text(messages);
                    Swal.fire({target:modal.find('.cinepolis-preview-swal-target')[0],icon:'error',title:'Koreksi belum disimpan',text:messages});
                });
            });
            modal.off('shown.bs.modal.correction').on('shown.bs.modal.correction', function () { dt.columns.adjust(); });
        }
    };
})(window, jQuery);
