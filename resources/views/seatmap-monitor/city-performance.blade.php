@extends('layouts.page')
@section('title', 'City Performance')

@section('content')
<div class="subheader mb-3">
    <h1 class="subheader-title"><i class="subheader-icon fal fa-city"></i> City Performance <small>Showtime-based distribution estimates</small></h1>
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-3">
        <div class="panel p-4 h-100"><small class="text-muted d-block">Source</small><strong>{{ $source->name ?? 'Belum tersedia' }}</strong></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="panel p-4 h-100"><small class="text-muted d-block">Method version</small><strong>{{ $latestRun->method_version ?? 'Belum dijalankan' }}</strong></div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="panel p-4 h-100"><small class="text-muted d-block">Coverage</small><strong>{{ isset($latestRun->coverage_ratio) ? number_format($latestRun->coverage_ratio * 100, 1).'%' : 'Belum terukur' }}</strong></div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr"><h2>Status data</h2></div>
    <div class="panel-container show">
        <div class="panel-content text-center py-5">
            @if(!$latestRun)
                <i class="fal fa-calendar-times fa-3x text-muted mb-3"></i>
                <h3>Belum ada observasi showtime</h3>
                <p class="text-muted mb-0">Estimasi kota belum tersedia. Modul ini tidak mengumpulkan data eksternal pada Phase 1 dan tidak membuat kapasitas bioskop atau angka audiens fiktif.</p>
            @else
                <h3>Run lengkap terakhir: {{ $latestRun->period_date }}</h3>
                <p class="text-muted mb-0">Estimasi hanya dapat diterbitkan setelah coverage gate dan pemetaan film lolos.</p>
            @endif
        </div>
    </div>
</div>
@endsection
