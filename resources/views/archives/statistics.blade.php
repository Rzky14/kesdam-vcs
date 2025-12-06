@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Statistik Arsip</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('archives.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h1 text-primary mb-2">{{ $stats['total_archives'] ?? 0 }}</div>
                    <p class="text-muted mb-0">Total Arsip</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h1 text-success mb-2">{{ $stats['indexed_archives'] ?? 0 }}</div>
                    <p class="text-muted mb-0">Arsip Terindeks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h1 text-warning mb-2">{{ $stats['expiring_soon'] ?? 0 }}</div>
                    <p class="text-muted mb-0">Akan Kadaluarsa (30 hari)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="h1 text-info mb-2">{{ count($stats['by_category'] ?? []) }}</div>
                    <p class="text-muted mb-0">Kategori</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
