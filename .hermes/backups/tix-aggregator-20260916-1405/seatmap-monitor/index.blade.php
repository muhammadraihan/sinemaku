@extends('layouts.page')

@section('title', 'Audience Estimate Ranking')

@section('css')
<style>
    .seatmap-hero{background:linear-gradient(135deg,#071427 0%,#0f2746 58%,#12395f 100%);border-radius:22px;color:#fff;padding:28px 30px;position:relative;overflow:hidden;box-shadow:0 22px 60px rgba(7,20,39,.18)}
    .seatmap-hero:after{content:"";position:absolute;right:-80px;top:-120px;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(35,211,255,.28),rgba(35,211,255,0) 68%)}
    .seatmap-eyebrow{font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#9fe8ff;font-weight:800;margin-bottom:8px}
    .seatmap-title{font-size:30px;font-weight:900;margin:0;letter-spacing:-.04em}.seatmap-copy{max-width:780px;color:#dbeafe;margin:10px 0 0;font-size:14px;line-height:1.7}
    .seatmap-filter{background:#fff;border-radius:18px;padding:18px;border:1px solid #e7edf5;box-shadow:0 14px 40px rgba(15,23,42,.06);margin-top:-18px;position:relative;z-index:2}
    .ranking-shell{background:#fff;border-radius:20px;border:1px solid #e7edf5;box-shadow:0 16px 45px rgba(15,23,42,.06);overflow:hidden}.ranking-table{width:100%;border-collapse:collapse}.ranking-table th{font-size:12px;color:#60718a;font-weight:800;padding:16px 18px;border-bottom:1px solid #e8eef5;white-space:nowrap}.ranking-table td{padding:18px;border-bottom:1px solid #edf2f7;vertical-align:middle}.ranking-table tbody tr:nth-child(odd){background:#f9fbfe}.rank-cell{font-size:22px;font-weight:900;color:#10233f;text-align:center}.movie-cell{display:flex;align-items:center;gap:14px;min-width:310px}.poster-fallback{width:54px;height:78px;border-radius:10px;background:linear-gradient(155deg,#10233f,#1f6f93);display:flex;align-items:center;justify-content:center;color:#9fe8ff;font-weight:900;box-shadow:inset 0 0 0 1px rgba(255,255,255,.18)}.movie-title{font-weight:900;color:#10233f;font-size:16px}.movie-meta{color:#7b8aa0;font-size:12px;margin-top:4px}.metric-number{font-weight:900;color:#10233f;font-size:16px;text-align:right}.change-pill{display:inline-flex;align-items:center;justify-content:center;min-width:64px;border-radius:999px;color:#fff;font-weight:900;font-size:12px;padding:7px 10px}.change-up{background:#1fb76e}.change-down{background:#ee4949}.change-flat{background:#8aa0b8}.score-badge{display:flex;align-items:center;gap:6px;color:#10233f;font-weight:900}.score-badge small{color:#03a9e6;font-style:italic;font-weight:900}.row-action{border:0;background:#e9f8ff;color:#00a8e8;border-radius:12px;width:38px;height:38px}.empty-state{padding:46px;text-align:center;color:#718096}.empty-state i{font-size:34px;color:#02a9e8;margin-bottom:12px}.disclaimer{background:#fff8e6;border:1px solid #f5dfaa;color:#7a5b10;border-radius:14px;padding:12px 14px;font-size:12px}.timeline-panel{display:none;background:#071427;color:#dbeafe;border-radius:18px;padding:18px;margin-top:16px}.timeline-panel.is-visible{display:block}
</style>
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title"><i class="subheader-icon fal fa-chart-line"></i> Audience <span class="fw-300">Estimate Ranking</span><small>Ranking nasional gabungan dari seluruh chain dan bioskop yang dapat dikoleksi.</small></h1>
</div>

<div class="seatmap-hero mb-4">
    <div class="seatmap-eyebrow">Competitive public seat-map signal</div>
    <h2 class="seatmap-title">Rank film dari estimasi okupansi, bukan laporan resmi.</h2>
    <p class="seatmap-copy">Ranking menggabungkan seluruh source dan bioskop yang berhasil dikoleksi—bukan satu chain. Film dengan variasi judul antar-provider dinormalisasi menjadi satu rank. Angka resmi tetap direkonsiliasi dari distributor report.</p>
</div>

<div class="seatmap-filter mb-4">
    <div class="row align-items-end">
        <div class="col-md-3">
            <label class="form-label">Tanggal</label>
            <input type="date" id="ranking-date" class="form-control" value="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Source</label>
            <select id="ranking-source" class="form-control">
                <option value="">Semua source</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="button" id="refresh-ranking" class="btn btn-primary"><i class="fal fa-sync mr-1"></i> Refresh Ranking</button>
            <a href="{{ route('seatmap-monitor.sources') }}" class="btn btn-outline-secondary ml-2"><i class="fal fa-key mr-1"></i> Source Accounts</a>
        </div>
    </div>
</div>

<div class="disclaimer mb-3" id="ranking-disclaimer">Estimasi dari ketersediaan seat map publik. Bukan admissions resmi.</div>

<div class="ranking-shell">
    <table class="ranking-table">
        <thead>
            <tr>
                <th class="text-center">Rank</th>
                <th>Title</th>
                <th class="text-right">Daily Est. Adm.</th>
                <th class="text-center">Change</th>
                <th class="text-right">Estimated Occupancy</th>
                <th class="text-right">Showtimes</th>
                <th class="text-right">Cinemas</th>
                <th>Coverage</th>
                <th>Score</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="ranking-body">
            <tr><td colspan="10"><div class="empty-state"><i class="fal fa-spinner fa-spin"></i><br>Memuat ranking...</div></td></tr>
        </tbody>
    </table>
</div>

<div id="timeline-panel" class="timeline-panel">
    <h5 id="timeline-title" class="text-white mb-2">Minute-to-minute</h5>
    <div id="timeline-content" class="small"></div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/notifications/sweetalert2/sweetalert2.bundle.js') }}"></script>
<script>
(function(){
    const rankingUrl = @json(route('seatmap-monitor.ranking'));
    const timelineTemplate = @json(route('seatmap-monitor.timeline', ['film' => '__FILM__']));
    const nf = new Intl.NumberFormat('id-ID');
    const body = document.getElementById('ranking-body');
    const dateInput = document.getElementById('ranking-date');
    const disclaimer = document.getElementById('ranking-disclaimer');

    function escapeHtml(value){ return String(value || '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
    function score(row){
        const occ = Number(row.estimated_occupancy_percent || 0);
        const delta = Number(row.delta_admissions || 0);
        return Math.min(10, Math.max(0, (occ / 12) + Math.min(3, delta / 20))).toFixed(1);
    }
    function changePill(value){
        if (value === null || value === undefined) return '<span class="change-pill change-flat">—</span>';
        const cls = value > 0 ? 'change-up' : (value < 0 ? 'change-down' : 'change-flat');
        return '<span class="change-pill '+cls+'">'+(value > 0 ? '+' : '')+value+'%</span>';
    }
    function render(rows){
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="10"><div class="empty-state"><i class="fal fa-chair"></i><h4>Belum ada snapshot seat map lintas chain.</h4><p>Hubungkan akun/source berizin untuk XXI, CGV, Cinepolis, atau jaringan lain lalu jalankan collector read-only.</p></div></td></tr>';
            return;
        }
        body.innerHTML = rows.map(row => `
            <tr>
                <td class="rank-cell">${row.rank}</td>
                <td><div class="movie-cell"><div class="poster-fallback">${escapeHtml(row.film_name).slice(0,1)}</div><div><div class="movie-title">${escapeHtml(row.film_name)}</div><div class="movie-meta">Public seat-map estimate · ${escapeHtml(row.last_captured_at || 'not captured')}</div></div></div></td>
                <td class="metric-number">${nf.format(row.daily_estimated_admissions || 0)}</td>
                <td class="text-center">${changePill(row.change_percent)}</td>
                <td class="metric-number">${row.estimated_occupancy_percent === null ? '—' : row.estimated_occupancy_percent + '%'}</td>
                <td class="metric-number">${nf.format(row.showtimes || 0)}</td>
                <td class="metric-number">${nf.format(row.cinema_count || 0)}</td>
                <td><span class="movie-meta">${escapeHtml(row.chains_label || '—')}<br>${nf.format(row.source_count || 0)} source</span></td>
                <td><div class="score-badge"><span>Momentum</span><small>Flash ⚡</small><strong>${score(row)}</strong></div></td>
                <td><button type="button" class="row-action" data-film="${escapeHtml(row.film_name)}" aria-label="Lihat timeline"><i class="fal fa-angle-double-up"></i></button></td>
            </tr>`).join('');
    }
    function loadRanking(){
        const url = new URL(rankingUrl, window.location.origin);
        url.searchParams.set('date', dateInput.value);
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(payload => { disclaimer.textContent = payload.meta.disclaimer; render(payload.data || []); })
            .catch(() => Swal.fire('Gagal', 'Ranking gagal dimuat.', 'error'));
    }
    document.getElementById('refresh-ranking').addEventListener('click', loadRanking);
    body.addEventListener('click', function(e){
        const btn = e.target.closest('.row-action');
        if (!btn) return;
        const film = btn.dataset.film;
        const url = new URL(timelineTemplate.replace('__FILM__', encodeURIComponent(film)), window.location.origin);
        url.searchParams.set('date', dateInput.value);
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(payload => {
                document.getElementById('timeline-title').textContent = 'Minute-to-minute · ' + film;
                document.getElementById('timeline-content').innerHTML = payload.data.length
                    ? payload.data.map(p => `<div>${escapeHtml(p.captured_at)} — <strong>${nf.format(p.estimated_occupied)}</strong> estimated occupied from ${nf.format(p.showtimes_observed)} showtimes</div>`).join('')
                    : 'Belum ada timeline snapshot untuk film ini.';
                document.getElementById('timeline-panel').classList.add('is-visible');
            })
            .catch(() => Swal.fire('Gagal', 'Timeline gagal dimuat.', 'error'));
    });
    loadRanking();
})();
</script>
@endsection
