@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Manajemen Dokumen</h2>
            <p class="text-muted">Kelola surat masuk dan surat keluar</p>
        </div>
        <div class="col-md-6 text-end">
            @can('create', App\Models\Document::class)
                <div class="btn-group" role="group">
                    <a href="{{ route('documents.create', ['type' => 'masuk']) }}" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Surat Masuk
                    </a>
                    <a href="{{ route('documents.create', ['type' => 'keluar']) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Surat Keluar
                    </a>
                </div>
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

    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Nomor, subjek, pengirim...">
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label">Tipe</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Semua Tipe</option>
                        <option value="masuk" {{ request('type') === 'masuk' ? 'selected' : '' }}>Surat Masuk</option>
                        <option value="keluar" {{ request('type') === 'keluar' ? 'selected' : '' }}>Surat Keluar</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="classification" class="form-label">Klasifikasi</label>
                    <select class="form-select" id="classification" name="classification">
                        <option value="">Semua Klasifikasi</option>
                        <option value="biasa" {{ request('classification') === 'biasa' ? 'selected' : '' }}>Biasa</option>
                        <option value="rahasia" {{ request('classification') === 'rahasia' ? 'selected' : '' }}>Rahasia</option>
                        <option value="telegram" {{ request('classification') === 'telegram' ? 'selected' : '' }}>Telegram</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="priority" class="form-label">Prioritas</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">Semua Prioritas</option>
                        <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Tinggi</option>
                        <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Mendesak</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>

            @if(request()->hasAny(['search', 'type', 'classification', 'status', 'priority', 'start_date', 'end_date']))
                <div class="mt-3">
                    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset Filter
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Documents Table -->
    <div class="card">
        <div class="card-body">
            @if($documents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Tipe</th>
                                <th>Tanggal</th>
                                <th>Subjek</th>
                                <th>Pengirim/Penerima</th>
                                <th>Klasifikasi</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $document)
                                <tr>
                                    <td>
                                        <strong>{{ $document->number }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge {{ $document->isIncoming() ? 'bg-info' : 'bg-primary' }}">
                                            {{ $document->getTypeLabel() }}
                                        </span>
                                    </td>
                                    <td>{{ $document->date->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;">
                                            {{ $document->isClassified() && $document->is_encrypted ? '🔒 [Terenkripsi]' : $document->subject }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $document->isIncoming() ? $document->sender : $document->recipient }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $document->isClassified() ? 'bg-danger' : ($document->isTelegram() ? 'bg-warning' : 'bg-secondary') }}">
                                            {{ $document->getClassificationLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $document->getPriorityBadgeClass() }}">
                                            {{ $document->getPriorityLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $document->getStatusBadgeClass() }}">
                                            {{ $document->getStatusLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            @can('view', $document)
                                                <a href="{{ route('documents.show', $document) }}" 
                                                   class="btn btn-outline-primary"
                                                   title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endcan

                                            @can('update', $document)
                                                @if(in_array($document->status, ['draft', 'rejected']))
                                                    <a href="{{ route('documents.edit', $document) }}" 
                                                       class="btn btn-outline-warning"
                                                       title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @endif
                                            @endcan

                                            @can('delete', $document)
                                                @if($document->isDraft())
                                                    <form action="{{ route('documents.destroy', $document) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger"
                                                                title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $documents->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Tidak ada dokumen ditemukan.</p>
                    @can('create', App\Models\Document::class)
                        <a href="{{ route('documents.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Buat Dokumen Baru
                        </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
