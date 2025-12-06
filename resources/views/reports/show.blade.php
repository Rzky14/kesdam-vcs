@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $report->name }}</h2>
            <small class="text-muted">
                {{ $report->getTypeLabel() }} - 
                {{ $report->period_start->format('d M Y') }} s/d {{ $report->period_end->format('d M Y') }}
            </small>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('reports.export.pdf', $report) }}" class="btn btn-danger">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('reports.export.excel', $report) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan Data</h5>
                </div>
                <div class="card-body">
                    @if($report->type === 'schedule')
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Total Jadwal</div>
                                    <div class="stat-value">{{ $report->data['total_schedules'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Rata-rata Durasi</div>
                                    <div class="stat-value">{{ $report->data['average_duration'] ?? 0 }}h</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Total Dokumen</div>
                                    <div class="stat-value">{{ $report->data['total_documents'] ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item">
                                    <div class="stat-label">Tingkat Persetujuan</div>
                                    <div class="stat-value">{{ $report->data['approval_rate'] ?? 0 }}%</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-item {
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
    }
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
        text-transform: uppercase;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #0d6efd;
        margin-top: 10px;
    }
</style>
@endsection
