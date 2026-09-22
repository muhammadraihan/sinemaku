(function () {
    'use strict';

    var form = document.getElementById('cinepoint-sync-form');
    var button = document.getElementById('cinepoint-sync-button');
    var Swal = window.Swal || window.swal || window.Sweetalert2 || window.sweetAlert;
    if (!form || !button || !Swal || typeof Swal.fire !== 'function') return;
    if (!window.Swal) window.Swal = Swal;

    var busy = false;
    var pollTimer = null;
    var deadline = 0;
    var statusUrl = null;
    var reasons = {
        browser_failed: 'Browser collector gagal mengambil data Cinepoint.',
        worker_failed: 'Worker collector gagal menyelesaikan sinkronisasi.',
        invalid_snapshot: 'Data hasil collector tidak valid.',
        max_attempts_exceeded: 'Batas percobaan worker tercapai.'
    };

    function stop() {
        if (pollTimer) clearTimeout(pollTimer);
        pollTimer = null;
        busy = false;
        button.disabled = false;
    }

    function loading(text) {
        Swal.fire({
            title: 'Sinkronisasi Cinepoint',
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            onOpen: function () { Swal.showLoading(); }
        });
    }

    function update(text) {
        Swal.update({ title: 'Sinkronisasi Cinepoint', text: text, type: undefined, showConfirmButton: false });
    }

    async function jsonRequest(url, options) {
        var response = await fetch(url, options);
        var payload;
        try { payload = await response.json(); } catch (error) { payload = {}; }
        if (!response.ok) {
            var requestError = new Error(payload.message || 'Respons server tidak dapat diproses.');
            requestError.status = response.status;
            throw requestError;
        }
        return payload;
    }

    function schedulePoll() {
        pollTimer = setTimeout(poll, 2000);
    }

    async function poll() {
        pollTimer = null;
        if (Date.now() >= deadline) {
            stop();
            await Swal.fire({
                type: 'warning', title: 'Status belum dapat dipastikan',
                text: 'Batas waktu pemantauan tercapai. Proses mungkin masih berjalan; periksa status halaman sebelum mengirim ulang.',
                confirmButtonText: 'Tutup'
            });
            return;
        }
        try {
            var payload = await jsonRequest(statusUrl, { method: 'GET', headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            var job = payload.job || {};
            if (job.status === 'queued') {
                update('Permintaan berada dalam antrean dan menunggu worker VPS.');
                schedulePoll();
            } else if (job.status === 'running') {
                update('Worker VPS sedang memproses data Cinepoint.');
                schedulePoll();
            } else if (job.status === 'success') {
                stop();
                await Swal.fire({ type: 'success', title: 'Sinkronisasi berhasil', text: 'Snapshot Cinepoint berhasil disimpan.', confirmButtonText: 'Lihat data terbaru' });
                window.location.reload();
            } else if (job.status === 'failed') {
                stop();
                var retry = await Swal.fire({
                    type: 'error', title: 'Sinkronisasi gagal',
                    text: reasons[job.failure_reason] || 'Collector gagal menyelesaikan sinkronisasi.',
                    confirmButtonText: 'Coba lagi', showCancelButton: true, cancelButtonText: 'Tutup'
                });
                if (retry.value) form.dispatchEvent(new Event('submit', { cancelable: true }));
            } else {
                throw new Error('Status sinkronisasi tidak dikenali.');
            }
        } catch (error) {
            if (!busy) return;
            if (pollTimer) clearTimeout(pollTimer);
            pollTimer = null;
            var retryStatus = await Swal.fire({
                type: 'warning', title: 'Status belum dapat dipastikan',
                text: 'Koneksi ke server terputus. Keberhasilan atau kegagalan job belum dapat dipastikan.',
                confirmButtonText: 'Periksa lagi', showCancelButton: true, cancelButtonText: 'Tutup'
            });
            if (retryStatus.value) {
                busy = true;
                button.disabled = true;
                loading('Memeriksa kembali status job yang sama...');
                await poll();
            } else stop();
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy) return;
        busy = true;
        button.disabled = true;
        deadline = Date.now() + 5 * 60 * 1000;
        loading('Mengirim permintaan sinkronisasi...');
        try {
            var token = form.querySelector('input[name="_token"]').value;
            var payload = await jsonRequest(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (payload.status === 'success') {
                stop();
                await Swal.fire({ type: 'success', title: 'Sinkronisasi berhasil', text: 'Snapshot Cinepoint berhasil disimpan.', confirmButtonText: 'Lihat data terbaru' });
                window.location.reload();
                return;
            }
            if (!payload.job || !payload.status_url) throw new Error('Identitas job tidak tersedia.');
            statusUrl = payload.status_url;
            update(payload.job.status === 'running' ? 'Worker VPS sedang memproses data Cinepoint.' : 'Permintaan berada dalam antrean dan menunggu worker VPS.');
            schedulePoll();
        } catch (error) {
            stop();
            await Swal.fire({
                type: 'warning', title: 'Permintaan belum dapat dipastikan',
                text: 'Server tidak memberi konfirmasi job. Periksa koneksi dan status halaman sebelum mencoba lagi.',
                confirmButtonText: 'Tutup'
            });
        }
    });
}());
