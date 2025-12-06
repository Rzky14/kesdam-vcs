@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="text-dark fw-bold mb-1">Dashboard</h2>
        <p class="text-muted mb-0">Selamat datang di Sistem Manajemen KESDAM III/Siliwangi</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Jadwal Aktif -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Jadwal Aktif</p>
                            <h3 class="fw-bold mb-0">{{ $stats['active_schedules'] }}</h3>
                            <small class="text-muted">+2 dari minggu lalu</small>
                        </div>
                        <div class="bg-light rounded p-2">
                            <i class="bi bi-calendar-event text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surat Masuk -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Surat Masuk</p>
                            <h3 class="fw-bold mb-0">{{ $stats['incoming_documents'] }}</h3>
                            <small class="text-muted">Hari ini</small>
                        </div>
                        <div class="bg-light rounded p-2">
                            <i class="bi bi-envelope text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menunggu Persetujuan -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Menunggu Persetujuan</p>
                            <h3 class="fw-bold mb-0">{{ $stats['pending_approvals'] }}</h3>
                            <small class="text-muted">Surat keluar</small>
                        </div>
                        <div class="bg-light rounded p-2">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selesai Bulan Ini -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Selesai Bulan Ini</p>
                            <h3 class="fw-bold mb-0">{{ $stats['completed_this_month'] }}</h3>
                            <small class="text-muted">Dokumen</small>
                        </div>
                        <div class="bg-light rounded p-2">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Jadwal Terbaru -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-1 fw-bold">Jadwal Terbaru</h5>
                    <p class="text-muted mb-0 small">Jadwal kegiatan yang akan datang</p>
                </div>
                <div class="card-body">
                    @forelse($recentSchedules as $schedule)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">{{ $schedule->title }}</h6>
                                <small class="text-muted d-block">{{ $schedule->start_date->format('d M Y') }}</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                @if($schedule->status === 'active')
                                    <span class="badge bg-success rounded-pill px-3">Aktif</span>
                                @elseif($schedule->status === 'draft')
                                    <span class="badge bg-secondary rounded-pill px-3">Draft</span>
                                @else
                                    <span class="badge bg-info rounded-pill px-3">{{ ucfirst($schedule->status) }}</span>
                                @endif
                                
                                <span class="badge bg-light text-dark rounded-pill px-3">
                                    {{ $schedule->getTypeLabel() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x text-muted fs-1"></i>
                            <p class="text-muted mt-3 mb-0">Tidak ada jadwal terbaru</p>
                        </div>
                    @endforelse
                    
                    @if($recentSchedules->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('schedules.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua Jadwal <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Surat Terbaru -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-1 fw-bold">Surat Terbaru</h5>
                    <p class="text-muted mb-0 small">Aktivitas surat menyurat terkini</p>
                </div>
                <div class="card-body">
                    @forelse($recentDocuments as $document)
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">
                                    {{ $document->subject }}
                                    @if($document->is_encrypted)
                                        <i class="bi bi-lock-fill text-danger ms-1" title="Dokumen Terenkripsi"></i>
                                    @endif
                                </h6>
                                <small class="text-muted d-block">
                                    {{ $document->type === 'masuk' ? 'Dari' : 'Ke' }}: 
                                    {{ $document->type === 'masuk' ? $document->sender : $document->recipient }}
                                </small>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-shrink-0 ms-3">
                                @if($document->classification === 'biasa')
                                    <span class="badge bg-primary rounded-pill px-3">Biasa</span>
                                @elseif($document->classification === 'rahasia')
                                    <span class="badge bg-danger rounded-pill px-3">Rahasia</span>
                                @else
                                    <span class="badge bg-warning rounded-pill px-3">Telegram</span>
                                @endif
                                
                                @if($document->status === 'pending_approval')
                                    <span class="badge bg-warning text-dark rounded-pill px-3">Pending</span>
                                @elseif($document->status === 'approved')
                                    <span class="badge bg-success rounded-pill px-3">Approved</span>
                                @elseif($document->status === 'draft')
                                    <span class="badge bg-secondary rounded-pill px-3">Draft</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3">Rejected</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark-text text-muted fs-1"></i>
                            <p class="text-muted mt-3 mb-0">Tidak ada surat terbaru</p>
                        </div>
                    @endforelse
                    
                    @if($recentDocuments->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-primary">
                                Lihat Semua Surat <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions (Optional - for better UX) -->
    @if(Auth::user()->hasPermission('schedule.create') || Auth::user()->hasPermission('document.create'))
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="text-white mb-2 fw-bold">Aksi Cepat</h5>
                            <p class="text-white-50 mb-0">Buat jadwal atau dokumen baru dengan cepat</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            @if(Auth::user()->hasPermission('schedule.create'))
                                <a href="{{ route('schedules.create') }}" class="btn btn-light me-2">
                                    <i class="bi bi-calendar-plus me-1"></i> Buat Jadwal
                                </a>
                            @endif
                            @if(Auth::user()->hasPermission('document.create'))
                                <a href="{{ route('documents.create') }}" class="btn btn-warning">
                                    <i class="bi bi-file-earmark-plus me-1"></i> Buat Surat
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .badge {
        font-weight: 500;
        font-size: 0.75rem;
    }
    
    .text-white-50 {
        color: rgba(255, 255, 255, 0.7);
    }
</style>
@endpush
@endsection
