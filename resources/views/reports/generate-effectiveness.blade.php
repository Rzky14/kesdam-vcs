@extends('layouts.app')

@section('title', 'Buat Laporan Efektivitas Jadwal')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Alert Messages -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Validasi Gagal!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Header -->
            <div class="mb-4">
                <h2 class="mb-1"><i class="bi bi-bar-chart-line"></i> Laporan Efektivitas Jadwal</h2>
                <p class="text-muted">Buat laporan evaluasi efektivitas jadwal kegiatan</p>
            </div>

            <!-- Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('reports.store.effectiveness') }}" method="POST">
                        @csrf

                        <!-- Period Start -->
                        <div class="mb-3">
                            <label for="period_start" class="form-label">
                                <i class="bi bi-calendar-event"></i> Tanggal Mulai
                            </label>
                            <input 
                                type="date" 
                                class="form-control @error('period_start') is-invalid @enderror" 
                                id="period_start" 
                                name="period_start"
                                value="{{ old('period_start') }}"
                                required
                            >
                            @error('period_start')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Period End -->
                        <div class="mb-3">
                            <label for="period_end" class="form-label">
                                <i class="bi bi-calendar-event"></i> Tanggal Selesai
                            </label>
                            <input 
                                type="date" 
                                class="form-control @error('period_end') is-invalid @enderror" 
                                id="period_end" 
                                name="period_end"
                                value="{{ old('period_end') }}"
                                required
                            >
                            @error('period_end')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Tanggal selesai harus setelah tanggal mulai</small>
                        </div>

                        <!-- Schedule Type -->
                        <div class="mb-4">
                            <label for="schedule_type" class="form-label">
                                <i class="bi bi-list-task"></i> Jenis Jadwal (Opsional)
                            </label>
                            <select 
                                class="form-select @error('schedule_type') is-invalid @enderror" 
                                id="schedule_type" 
                                name="schedule_type"
                            >
                                <option value="">Semua Jadwal</option>
                                <option value="dukkes" {{ old('schedule_type') === 'dukkes' ? 'selected' : '' }}>
                                    Jadwal Dukkes (Kesehatan)
                                </option>
                                <option value="jaga" {{ old('schedule_type') === 'jaga' ? 'selected' : '' }}>
                                    Jadwal Jaga
                                </option>
                                <option value="kegiatan_satuan" {{ old('schedule_type') === 'kegiatan_satuan' ? 'selected' : '' }}>
                                    Jadwal Kegiatan Satuan
                                </option>
                            </select>
                            @error('schedule_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Pilih untuk filter jadwal tertentu atau biarkan kosong untuk semua</small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-sm-flex">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Buat Laporan
                            </button>
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4" role="alert">
                <i class="bi bi-info-circle"></i>
                <strong>Informasi:</strong> Laporan efektivitas mencakup data tentang penyelesaian jadwal, status, dan metrik performa lainnya.
            </div>
        </div>
    </div>
</div>
@endsection

