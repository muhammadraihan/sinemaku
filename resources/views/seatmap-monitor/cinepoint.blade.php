@extends('layouts.page')
@section('title','Audience Estimate · Cinepoint Daily Ranking')

@section('css')
<style>
    .cinepoint-page { max-width: 1360px; margin: 0 auto; }
    .cinepoint-page .panel { border-radius: 12px !important; overflow: hidden; }
    .cinepoint-hero .panel-content { padding: 1.25rem 1.5rem; }
    .cinepoint-kicker { color: #6c757d; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .cinepoint-title { color: #1b2a4e; font-size: 1.3rem; font-weight: 600; margin: .25rem 0; }
    .cinepoint-source { color: #6c757d; font-size: .88rem; }
    .cinepoint-source a { font-weight: 600; }
    .cinepoint-status { border-left: 4px solid #1dc9b7; border-radius: .2rem; padding: .8rem 1rem; margin-bottom: 1rem; }
    .cinepoint-status strong { color: #0f766e; }
    .cinepoint-attempt-error { border-left: 4px solid #e67e22; border-radius: .2rem; font-size: .82rem; line-height: 1.35; margin: -0.25rem 0 1rem; overflow-wrap: anywhere; padding: .65rem .85rem; }
    .cinepoint-attempt-error strong { color: #9a4d00; }
    .cinepoint-attempt-error span { display: inline; }
    .cinepoint-status { overflow-wrap: anywhere; }
    .cinepoint-ranking .panel-hdr { min-height: 52px !important; padding: 0 1.25rem !important; }
    .cinepoint-ranking .panel-hdr h2 { color: #1b2a4e; font-size: 1rem !important; font-weight: 600; line-height: 1.25; margin: 0; padding: 0; }
    .cinepoint-table { table-layout: fixed; margin-bottom: 0; }
    .cinepoint-table th { height: auto !important; background: #eef5fb; border-top: 0; border-radius: 0 !important; color: #38547d; font-size: .72rem !important; font-weight: 700; letter-spacing: .055em; line-height: 1.25 !important; padding: .7rem 1rem !important; text-transform: uppercase; vertical-align: middle; white-space: normal; }
    .cinepoint-table td { height: auto !important; border-top-color: #edf1f5; font-size: .875rem !important; line-height: 1.35 !important; padding: .6rem 1rem !important; vertical-align: middle; }
    .cinepoint-table .rank-col { width: 64px !important; color: #6c757d; font-weight: 700; text-align: center; }
    .cinepoint-table .movie-col { width: auto !important; }
    .cinepoint-table .metric-col { width: 148px !important; text-align: right; }
    .cinepoint-poster { width: 40px; height: 56px; border: 1px solid #e9ecef; border-radius: .25rem; flex: 0 0 auto; object-fit: cover; }
    .cinepoint-film-title { color: #1b2a4e; font-size: .95rem; font-weight: 600; line-height: 1.35; }
    .cinepoint-metric { color: #1b2a4e; font-variant-numeric: tabular-nums; font-weight: 600; }
    .cinepoint-status-card .panel-content { padding: 1.25rem 1.4rem; }
    .cinepoint-status-card h3 { color: #1b2a4e; font-size: 1rem; font-weight: 600; margin-bottom: 1.15rem; }
    .cinepoint-meta { margin: 0; }
    .cinepoint-meta div { border-bottom: 1px solid #edf1f5; padding: .7rem 0; }
    .cinepoint-meta div:first-child { padding-top: 0; }
    .cinepoint-meta div:last-child { border-bottom: 0; padding-bottom: 0; }
    .cinepoint-meta dt { color: #6c757d; font-size: .73rem; font-weight: 700; letter-spacing: .04em; margin-bottom: .2rem; text-transform: uppercase; }
    .cinepoint-meta dd { color: #1b2a4e; font-size: .88rem; line-height: 1.45; margin: 0; }
    @media (max-width: 991.98px) {
        .cinepoint-page { max-width: none; }
        .cinepoint-status-card { margin-top: 1rem; }
    }
    @media (max-width: 575.98px) {
        .cinepoint-page { width: 100%; }
        .cinepoint-page .table-responsive { max-width: 100%; overflow-x: auto; }
        .cinepoint-hero .panel-content { padding: 1.15rem; }
        .cinepoint-hero .btn { margin-top: 1rem; width: 100%; }
        .cinepoint-ranking .panel-hdr { padding: 0 1rem !important; }
        .cinepoint-table { min-width: 680px; }
        .cinepoint-table th, .cinepoint-table td { padding-left: .85rem; padding-right: .85rem; }
        .cinepoint-table .metric-col { width: 150px; }
    }
</style>
@endsection

@section('content')
<div class="cinepoint-page">
    <div class="subheader mb-3">
        <h1 class="subheader-title"><i class="subheader-icon fal fa-chart-line"></i> Audience Estimate <small>Cinepoint Daily Ranking</small></h1>
    </div>

    <div class="panel cinepoint-hero mb-3">
        <div class="panel-container show">
            <div class="panel-content d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <div class="cinepoint-kicker">Public market intelligence</div>
                    <h2 class="cinepoint-title">Ranking penonton harian</h2>
                    <div class="cinepoint-source">Sumber: <a href="{{ $source_url }}" target="_blank" rel="noopener">Cinepoint</a> · {{ $attribution }}</div>
                </div>
                <form method="post" action="{{ route('seatmap-monitor.cinepoint.sync') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit"><i class="fal fa-sync mr-1"></i> Sync sekarang</button>
                </form>
            </div>
        </div>
    </div>

    @if($snapshot)
        <div class="alert alert-success cinepoint-status" role="status">
            <strong>Snapshot lengkap</strong> · Periode harian {{ \Carbon\Carbon::parse($snapshot->period_date)->format('d M Y') }} · {{ number_format($snapshot->collected_count) }}/{{ number_format($snapshot->source_total) }} film · Disinkronkan {{ \Carbon\Carbon::parse($snapshot->finished_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
        </div>
    @else
        <div class="alert alert-info cinepoint-status">Belum ada snapshot sukses. Jalankan sync untuk memulai.</div>
    @endif

    @if($latest_attempt && $latest_attempt->status === 'failed' && $latest_attempt->error_message)
        <div class="alert alert-warning cinepoint-attempt-error" role="status">
            <strong>Sinkronisasi terakhir gagal.</strong> {{ $latest_attempt->error_message }}
            <span>Snapshot sukses tetap ditampilkan. Gunakan <strong>Sync sekarang</strong> untuk mencoba lagi.</span>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <div class="panel cinepoint-ranking">
                <div class="panel-hdr">
                    <h2><i class="fal fa-film mr-2"></i>Film ranking</h2>
                </div>
                <div class="panel-container show">
                    <div class="table-responsive">
                        <table class="table table-hover cinepoint-table">
                            <thead>
                                <tr><th class="rank-col">Rank</th><th class="movie-col">Movie</th><th class="metric-col">Daily admissions</th><th class="metric-col">Total admissions</th></tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td class="rank-col">{{ $entry->rank }}</td>
                                        <td class="movie-col"><div class="d-flex align-items-center"><img src="{{ $entry->poster_url ?: asset('img/logo.png') }}" alt="Poster {{ $entry->title }}" class="cinepoint-poster mr-3"><span class="cinepoint-film-title">{{ $entry->title }}</span></div></td>
                                        <td class="metric-col"><span class="cinepoint-metric">{{ number_format($entry->daily_admissions) }}</span></td>
                                        <td class="metric-col"><span class="cinepoint-metric">{{ number_format($entry->total_admissions) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data tersimpan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel cinepoint-status-card">
                <div class="panel-container show"><div class="panel-content">
                    <h3><i class="fal fa-clock mr-2"></i>Refresh & status</h3>
                    <dl class="cinepoint-meta">
                        <div><dt>Jadwal refresh</dt><dd>07:00 · 12:00 · 18:00 WIB</dd></div>
                        <div><dt>Sinkronisasi berikutnya</dt><dd>{{ $next_scheduled_at }}</dd></div>
                        <div><dt>Status cron</dt><dd>{{ $scheduler_heartbeat ? 'Heartbeat: '.$scheduler_heartbeat : 'Belum terverifikasi; scheduler host harus menjalankan schedule:run.' }}</dd></div>
                        <div><dt>Percobaan terakhir</dt><dd>{{ $latest_attempt && $latest_attempt->finished_at ? \Carbon\Carbon::parse($latest_attempt->finished_at)->timezone('Asia/Jakarta')->format('d M Y H:i').' WIB · '.$latest_attempt->status : 'Belum ada' }}</dd></div>
                    </dl>
                </div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
@if(session('cinepoint_success') || session('cinepoint_error'))
<script src="{{ asset('js/notifications/sweetalert2/sweetalert2.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var text = {!! json_encode(session('cinepoint_success') ?: session('cinepoint_error')) !!};
    var ok = {!! session('cinepoint_success') ? 'true' : 'false' !!};
    Swal.fire({
        icon: ok ? 'success' : 'error',
        title: ok ? 'Sinkronisasi berhasil' : 'Sinkronisasi gagal',
        text: text,
        confirmButtonText: ok ? 'Tutup' : 'Coba lagi',
        showCancelButton: !ok,
        cancelButtonText: 'Tutup'
    }).then(function (result) {
        if (!ok && result.value) document.querySelector('form[action="{{ route('seatmap-monitor.cinepoint.sync') }}"]').submit();
    });
});
</script>
@endif
@endsection
