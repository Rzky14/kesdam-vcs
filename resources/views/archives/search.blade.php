@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Cari Arsip</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('archives.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Kata Kunci</label>
                    <input type="text" name="q" class="form-control" placeholder="Cari arsip..." 
                           value="{{ $searchTerm }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Kategori (Opsional)</label>
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    @if($searchTerm)
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Hasil Pencarian: <strong>{{ $archives->count() }}</strong> arsip ditemukan</h5>
                </div>
            </div>

            @forelse($archives as $archive)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">{{ $archive->name }}</h5>
                                    <small class="text-muted">
                                        {{ $archive->getCategoryLabel() }} - 
                                        {{ $archive->archive_date->format('d M Y') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <a href="{{ route('archives.show', $archive) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle"></i> Tidak ada arsip yang sesuai dengan pencarian Anda.
                    </div>
                </div>
            @endforelse
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Masukkan kata kunci untuk mencari arsip.
        </div>
    @endif
</div>
@endsection

