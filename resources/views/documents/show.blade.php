@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Detail Dokumen</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Dokumen</a></li>
                    <li class="breadcrumb-item active">{{ $document->number }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            @can('update', $document)
                @if(in_array($document->status, ['draft', 'rejected']))
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
                
                @if($document->isDraft())
                    <form action="{{ route('documents.submit', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Ajukan dokumen ini untuk persetujuan?')">
                            <i class="bi bi-send"></i> Ajukan
                        </button>
                    </form>
                @endif

                @if($document->isApproved())
                    <form action="{{ route('documents.archive', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info" onclick="return confirm('Arsipkan dokumen ini?')">
                            <i class="bi bi-archive"></i> Arsipkan
                        </button>
                    </form>
                @endif
            @endcan

            @can('delete', $document)
                @if($document->isDraft())
                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus dokumen ini?')">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Document Details Card -->
            <div class="card mb-4">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);">
                    <h5 class="mb-0" style="color: #d4af37;"><i class="bi bi-file-text"></i> Informasi Dokumen</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Nomor Surat:</strong>
                        </div>
                        <div class="col-md-8">
                            <span class="badge bg-dark">{{ $document->number }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Tipe:</strong>
                        </div>
                        <div class="col-md-8">
                            <span class="badge" style="{{ $document->isIncoming() ? 'background-color: #17a2b8;' : 'background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%); color: #d4af37;' }}">
                                {{ $document->getTypeLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Tanggal:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $document->date->format('d F Y') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>{{ $document->isIncoming() ? 'Pengirim' : 'Penerima' }}:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $document->isIncoming() ? $document->sender : $document->recipient }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Klasifikasi:</strong>
                        </div>
                        <div class="col-md-8">
                            <span class="badge {{ $document->isClassified() ? 'bg-danger' : ($document->isTelegram() ? 'bg-warning' : 'bg-secondary') }}">
                                {{ $document->getClassificationLabel() }}
                            </span>
                            @if($document->isClassified())
                                <i class="bi bi-shield-lock text-danger ms-2" title="Dokumen terenkripsi"></i>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Prioritas:</strong>
                        </div>
                        <div class="col-md-8">
                            <span class="{{ $document->getPriorityBadgeClass() }}">
                                {{ $document->getPriorityLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Perihal:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $document->subject }}
                        </div>
                    </div>

                    @if($document->description)
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Keterangan:</strong>
                            </div>
                            <div class="col-md-8">
                                <p class="mb-0">{{ $document->description }}</p>
                            </div>
                        </div>
                    @endif

                    @if($document->attachments && count($document->attachments) > 0)
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Lampiran:</strong>
                            </div>
                            <div class="col-md-8">
                                <ul class="list-group">
                                    @foreach($document->attachments as $index => $attachment)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi bi-paperclip"></i>
                                                {{ basename($attachment) }}
                                            </span>
                                            <a href="{{ route('documents.download', [$document, $index]) }}" 
                                               class="btn btn-sm" style="color: #1a472a; border-color: #1a472a;">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audit Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Informasi Audit</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <strong>Dibuat oleh:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $document->creator->name }}
                            <small class="text-muted">({{ $document->created_at->format('d/m/Y H:i') }})</small>
                        </div>
                    </div>

                    @if($document->updated_by)
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Terakhir diubah:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $document->updater->name }}
                                <small class="text-muted">({{ $document->updated_at->format('d/m/Y H:i') }})</small>
                            </div>
                        </div>
                    @endif

                    @if($document->archived_at)
                        <div class="row mt-2">
                            <div class="col-md-4">
                                <strong>Diarsipkan:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $document->archived_at->format('d/m/Y') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status and Actions Card -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Status</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="{{ $document->getStatusBadgeClass() }}" style="font-size: 1.2rem; padding: 0.5rem 1rem;">
                            {{ $document->getStatusLabel() }}
                        </span>
                    </div>

                    @if($document->isDraft())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <small>Dokumen masih dalam bentuk draft. Ajukan untuk persetujuan.</small>
                        </div>
                    @endif

                    @if($document->isPendingApproval())
                        <div class="alert alert-warning">
                            <i class="bi bi-clock"></i>
                            <small>Dokumen sedang menunggu persetujuan.</small>
                        </div>
                    @endif

                    @if($document->isApproved())
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <small>Dokumen telah disetujui.</small>
                        </div>
                    @endif

                    @if($document->isRejected())
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i>
                            <small>Dokumen ditolak. Silakan edit dan ajukan kembali.</small>
                        </div>
                    @endif

                    @if($document->isArchived())
                        <div class="alert alert-secondary">
                            <i class="bi bi-archive"></i>
                            <small>Dokumen telah diarsipkan.</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                        </a>

                        @if($document->attachments && count($document->attachments) > 0)
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#attachmentsModal">
                                <i class="bi bi-paperclip"></i> Lihat Semua Lampiran
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attachments Modal -->
@if($document->attachments && count($document->attachments) > 0)
<div class="modal fade" id="attachmentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lampiran Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    @foreach($document->attachments as $index => $attachment)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark"></i>
                                <strong>{{ basename($attachment) }}</strong>
                            </div>
                            <a href="{{ route('documents.download', [$document, $index]) }}" 
                               class="btn btn-sm" style="background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%); color: #d4af37; border: 1px solid #d4af37;">
                                <i class="bi bi-download"></i> Download
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
