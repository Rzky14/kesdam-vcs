@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Buat Laporan Jadwal</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('reports.store.schedule') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="period_start">Periode Mulai</label>
                            <input type="date" class="form-control @error('period_start') is-invalid @enderror" 
                                   id="period_start" name="period_start" value="{{ old('period_start') }}" required>
                            @error('period_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="period_end">Periode Selesai</label>
                            <input type="date" class="form-control @error('period_end') is-invalid @enderror" 
                                   id="period_end" name="period_end" value="{{ old('period_end') }}" required>
                            @error('period_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-text"></i> Buat Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
