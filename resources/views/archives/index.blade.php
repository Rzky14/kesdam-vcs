@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Arsip</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('archives.statistics') }}" class="btn btn-info">
                <i class="bi bi-bar-chart"></i> Statistik
            </a>
            <a href="{{ route('archives.search') }}" class="btn btn-outline-primary">
                <i class="bi bi-search"></i> Cari Arsip
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Arsip</h6>
                    <h3 class="mb-0">{{ $stats['total_archives'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Arsip Terindeks</h6>
                    <h3 class="mb-0">{{ $stats['indexed_archives'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Akan Kadaluarsa</h6>
                    <h3 class="mb-0 text-warning">{{ $stats['expiring_soon'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Kategori</h6>
                    <h3 class="mb-0">{{ count($stats['by_category'] ?? []) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cari</label>
                    <input type="text" name="search" class="form-control" placeholder="Cari arsip..." 
                           value="{{ $searchTerm }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archives List -->
    <div class="row">
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
                            <span class="badge bg-{{ $archive->is_indexed ? 'success' : 'secondary' }}">
                                {{ $archive->is_indexed ? 'Terindeks' : 'Belum' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text">{{ Str::limit($archive->description, 100) }}</p>
                        @if(!empty($archive->tags))
                            <div class="mb-3">
                                @foreach($archive->tags as $tag)
                                    <span class="badge bg-light text-dark">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                        <small class="text-muted">
                            Disimpan hingga: {{ $archive->retention_until?->format('d M Y') ?? 'Permanen' }}
                        </small>
                    </div>
                    <div class="card-footer bg-light">
                        <a href="{{ route('archives.show', $archive) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Lihat Detail
                        </a>
                        <form action="{{ route('archives.destroy', $archive) }}" method="POST" class="d-inline">
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
                    <i class="bi bi-info-circle"></i> Tidak ada arsip. 
                    <a href="{{ route('archives.search') }}">Cari arsip</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

