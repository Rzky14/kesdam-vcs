@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Laporan</h2>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('reports.create.schedule') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Laporan Jadwal
                </a>
                <a href="{{ route('reports.create.document') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Laporan Dokumen
                </a>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Jenis Laporan</label>
                    <select name="type" class="form-select">
                        <option value="">Semua</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ $selectedType === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reports List -->
    <div class="row">
        @forelse($reports as $report)
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title mb-1">{{ $report->name }}</h5>
                                <small class="text-muted">
                                    {{ $report->getTypeLabel() }} - 
                                    {{ $report->period_start->format('d M Y') }} s/d {{ $report->period_end->format('d M Y') }}
                                </small>
                            </div>
                            <span class="badge bg-info">{{ $report->status }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            @if($report->type === 'schedule')
                                <div class="col-4">
                                    <div class="h6 text-muted">Total</div>
                                    <div class="h4">{{ $report->data['total_schedules'] ?? 0 }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="h6 text-muted">Rata-rata Durasi</div>
                                    <div class="h4">{{ $report->data['average_duration'] ?? 0 }}h</div>
                                </div>
                            @else
                                <div class="col-4">
                                    <div class="h6 text-muted">Total Dokumen</div>
                                    <div class="h4">{{ $report->data['total_documents'] ?? 0 }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="h6 text-muted">Tingkat Persetujuan</div>
                                    <div class="h4">{{ $report->data['approval_rate'] ?? 0 }}%</div>
                                </div>
                            @endif
                            <div class="col-4">
                                <div class="h6 text-muted">Dibuat</div>
                                <div class="small">{{ $report->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>
                        <a href="{{ route('reports.export.pdf', $report) }}" class="btn btn-sm btn-outline-danger" title="Export PDF">
                            <i class="bi bi-file-pdf"></i>
                        </a>
                        <a href="{{ route('reports.export.excel', $report) }}" class="btn btn-sm btn-outline-success" title="Export Excel">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </a>
                        <form action="{{ route('reports.destroy', $report) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tidak ada laporan. 
                    <a href="{{ route('reports.create.schedule') }}">Buat laporan baru</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
