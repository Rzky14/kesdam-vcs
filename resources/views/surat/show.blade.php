@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Detail Surat</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('surat.index') }}">Surat</a></li>
                    <li class="breadcrumb-item active">{{ $document->number }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            @can('update', $document)
                @if(in_array($document->status, ['draft', 'rejected']))
                    <a href="{{ route('surat.edit', $document) }}" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @else
                    <!-- Edit button not shown: Status is '{{ $document->status }}' (only 'draft' or 'rejected' can be edited) -->
                @endif
                
                @if($document->isDraft())
                    <form action="{{ route('surat.submit', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Ajukan surat ini untuk persetujuan?')">
                            <i class="bi bi-send"></i> Ajukan
                        </button>
                    </form>
                @endif

                @if($document->isApproved())
                    <form action="{{ route('surat.archive', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info" onclick="return confirm('Arsipkan surat ini?')">
                            <i class="bi bi-archive"></i> Arsipkan
                        </button>
                    </form>
                @endif
            @endcan

            @can('delete', $document)
                @if($document->isDraft())
                    <form action="{{ route('surat.destroy', $document) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus surat ini?')">
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
                            {{ $document->date ? $document->date->format('d F Y') : '-' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>{{ $document->isIncoming() ? 'Pengirim' : 'Penerima' }}:</strong>
                        </div>
                        <div class="col-md-8">
                            {{ $document->isIncoming() ? ($document->sender ?? '-') : ($document->recipient ?? '-') }}
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
                                            <a href="{{ route('surat.download', [$document, $index]) }}" 
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
                            {{ $document->creator?->name ?? 'Unknown' }}
                            @if($document->created_at)
                                <small class="text-muted">({{ $document->created_at->format('d/m/Y H:i') }})</small>
                            @endif
                        </div>
                    </div>

                    @if($document->updated_by)
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Terakhir diubah:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $document->updater?->name ?? 'Unknown' }}
                                @if($document->updated_at)
                                    <small class="text-muted">({{ $document->updated_at->format('d/m/Y H:i') }})</small>
                                @endif
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
                        @php
                            $nextRole = $document->getNextApproverRole();
                            $nextRoleLabel = match($nextRole) {
                                'kaur' => 'Kepala Urusan (KAUR)',
                                'kasi' => 'Kepala Seksi (KASI)',
                                'pimpinan' => 'Pimpinan/Pejabat Tinggi',
                                default => 'Tidak diketahui',
                            };
                            $currentLevel = $document->getCurrentApprovalLevel();
                        @endphp
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-clock"></i>
                            <strong>Status Persetujuan:</strong><br>
                            <small>
                                Menunggu persetujuan dari <strong>{{ $nextRoleLabel }}</strong>
                                @if($currentLevel > 0)
                                    <br>Level saat ini: {{ $currentLevel }} dari 3
                                @endif
                            </small>
                        </div>

                        {{-- Approval Actions --}}
                        @can('approve', $document)
                            <div class="card border-success mb-3">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-shield-check"></i> Tindakan Persetujuan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Giliran Anda!</strong><br>
                                        <small>Anda berwenang untuk menyetujui dokumen ini sebagai <strong>{{ $nextRoleLabel }}</strong>.</small>
                                    </div>
                                    <div class="d-grid gap-2">
                                        {{-- Approve Button --}}
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                            <i class="bi bi-check-circle"></i> Setujui Dokumen
                                        </button>

                                        {{-- Reject Button --}}
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                            <i class="bi bi-x-circle"></i> Tolak Dokumen
                                        </button>

                                        {{-- Request Correction Button --}}
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#correctionModal">
                                            <i class="bi bi-pencil-square"></i> Minta Koreksi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="bi bi-info-circle"></i>
                                <small>Dokumen menunggu persetujuan dari <strong>{{ $nextRoleLabel }}</strong>. Anda tidak berwenang menyetujui pada level ini.</small>
                            </div>
                        @endcan
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
                        <a href="{{ route('surat.index') }}" class="btn btn-outline-secondary">
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
                            <a href="{{ route('surat.download', [$document, $index]) }}" 
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('surat.approve', $document) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="approveModalLabel">
                        <i class="bi bi-check-circle"></i> Setujui Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Konfirmasi Persetujuan</strong><br>
                        <small>Dengan menyetujui dokumen ini, Anda menyatakan bahwa dokumen telah sesuai dan layak untuk diproses ke tahap selanjutnya.</small>
                    </div>

                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Catatan Persetujuan (Opsional)</label>
                        <textarea 
                            name="notes" 
                            id="approve_notes" 
                            class="form-control" 
                            rows="3"
                            placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                        <small class="text-muted">Catatan akan dicatat dalam riwayat persetujuan.</small>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <small><strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Ya, Setujui Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('surat.reject', $document) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="bi bi-x-circle"></i> Tolak Dokumen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Konfirmasi Penolakan</strong><br>
                        <small>Dokumen yang ditolak akan dikembalikan kepada pembuat untuk diperbaiki.</small>
                    </div>

                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea 
                            name="reason" 
                            id="reject_reason" 
                            class="form-control @error('reason') is-invalid @enderror" 
                            rows="4"
                            required
                            placeholder="Jelaskan alasan penolakan secara detail (minimal 10 karakter)..."></textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 10 karakter. Alasan akan dikirimkan kepada pembuat dokumen.</small>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        <small><strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Ya, Tolak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Request Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1" aria-labelledby="correctionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('surat.request-correction', $document) }}">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="correctionModalLabel">
                        <i class="bi bi-pencil-square"></i> Minta Koreksi Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Permintaan Koreksi</strong><br>
                        <small>Dokumen akan dikembalikan kepada pembuat untuk diperbaiki sesuai catatan Anda.</small>
                    </div>

                    <div class="mb-3">
                        <label for="correction_reason" class="form-label">Catatan Koreksi <span class="text-danger">*</span></label>
                        <textarea 
                            name="reason" 
                            id="correction_reason" 
                            class="form-control @error('reason') is-invalid @enderror" 
                            rows="4"
                            required
                            placeholder="Jelaskan apa yang perlu dikoreksi secara detail (minimal 10 karakter)..."></textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 10 karakter. Berikan petunjuk yang jelas agar pembuat dokumen dapat memperbaiki dengan tepat.</small>
                    </div>

                    <div class="alert alert-secondary mb-0">
                        <i class="bi bi-lightbulb"></i>
                        <small><strong>Tips:</strong> Sebutkan bagian mana yang perlu diperbaiki dan bagaimana seharusnya.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-pencil-square"></i> Kirim Permintaan Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection



