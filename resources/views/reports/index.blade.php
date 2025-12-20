@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-file-earmark-text"></i> Laporan</h2>
            <p class="text-muted mb-0">Laporan dan statistik kegiatan</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Laporan Bulanan -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1"><i class="bi bi-calendar-month"></i> Laporan Bulanan</h6>
                            <p class="text-muted small mb-0">Rekap laporan dan data bulanan</p>
                        </div>
                    </div>
                    <a href="{{ route('reports.create.schedule') }}" class="btn btn-dark btn-sm w-100">
                        <i class="bi bi-plus-circle"></i> Buat Laporan
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistik Surat -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1"><i class="bi bi-envelope"></i> Statistik Surat</h6>
                            <p class="text-muted small mb-0">Statistik surat masuk dan keluar</p>
                        </div>
                    </div>
                    <a href="{{ route('reports.create.document') }}" class="btn btn-outline-dark btn-sm w-100">
                        Lihat Detail
                    </a>
                </div>
            </div>
        </div>

        <!-- Efektivitas Jadwal -->
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="text-muted mb-1"><i class="bi bi-calendar-check"></i> Efektivitas Jadwal</h6>
                            <p class="text-muted small mb-0">Evaluasi pelaksanaan jadwal</p>
                        </div>
                    </div>
                    <a href="{{ route('reports.create.effectiveness') }}" class="btn btn-outline-dark btn-sm w-100">
                        <i class="bi bi-bar-chart-line"></i> Analisis
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Laporan Terbaru -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Laporan Terbaru</h5>
        </div>
        <div class="card-body">
            @if($latestReports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-text" style="font-size: 3rem;"></i>
                    <p class="mt-3">Belum ada laporan. Buat laporan pertama Anda!</p>
                    <a href="{{ route('reports.create.schedule') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Buat Laporan
                    </a>
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach($latestReports as $report)
                        <div class="list-group-item px-0 py-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            @if($report->type === 'schedule')
                                                <div class="bg-info bg-opacity-10 text-info rounded p-2">
                                                    <i class="bi bi-calendar-event fs-5"></i>
                                                </div>
                                            @else
                                                <div class="bg-success bg-opacity-10 text-success rounded p-2">
                                                    <i class="bi bi-file-text fs-5"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-1">{{ $report->name }}</h6>
                                            <small class="text-muted">{{ $report->created_at->format('d M Y') }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-{{ $report->type === 'schedule' ? 'primary' : 'success' }}">
                                        {{ $report->type === 'schedule' ? 'Jadwal' : 'Dokumen' }}
                                    </span>
                                </div>
                                <div class="col-md-2 text-center">
                                    @if($report->status === 'completed')
                                        <span class="badge bg-success">Selesai</span>
                                    @elseif($report->status === 'in_progress')
                                        <span class="badge bg-warning">Proses</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('reports.show', $report) }}" 
                                           class="btn btn-outline-secondary" 
                                           title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('reports.export.pdf', $report) }}" 
                                           class="btn btn-outline-danger" 
                                           title="Export PDF">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .list-group-item {
        transition: background-color 0.2s;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
