@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
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

    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-file-earmark-text"></i> Laporan Statistik Surat</h2>
            <p class="text-muted">Analisis dokumen, klasifikasi, dan status persetujuan</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('reports.store.document') }}" method="POST">
                        @csrf

                        <!-- Periode -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="period_start">
                                    <i class="bi bi-calendar-event"></i> Periode Mulai
                                </label>
                                <input type="date" class="form-control @error('period_start') is-invalid @enderror" 
                                       id="period_start" name="period_start" value="{{ old('period_start') }}" required>
                                @error('period_start')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="period_end">
                                    <i class="bi bi-calendar-event"></i> Periode Selesai
                                </label>
                                <input type="date" class="form-control @error('period_end') is-invalid @enderror" 
                                       id="period_end" name="period_end" value="{{ old('period_end') }}" required>
                                @error('period_end')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Klasifikasi Dokumen -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-shield-lock"></i> Klasifikasi (Pilih salah satu atau lebih)
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="class_biasa" 
                                       name="classifications[]" value="Biasa" 
                                       {{ in_array('Biasa', (array)old('classifications', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="class_biasa">
                                    Biasa (Dokumen Reguler)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="class_rahasia" 
                                       name="classifications[]" value="Rahasia" 
                                       {{ in_array('Rahasia', (array)old('classifications', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="class_rahasia">
                                    <i class="bi bi-lock-fill"></i> Rahasia (Terklasifikasi)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="class_telegram" 
                                       name="classifications[]" value="Telegram" 
                                       {{ in_array('Telegram', (array)old('classifications', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="class_telegram">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Telegram (Urgent)
                                </label>
                            </div>
                        </div>

                        <!-- Status Dokumen -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-check-circle"></i> Status Persetujuan (Opsional)
                            </label>
                            <select class="form-select" name="document_status" id="document_status">
                                <option value="">-- Semua Status --</option>
                                <option value="pending" {{ old('document_status') === 'pending' ? 'selected' : '' }}>
                                    Menunggu Persetujuan
                                </option>
                                <option value="approved" {{ old('document_status') === 'approved' ? 'selected' : '' }}>
                                    Sudah Disetujui
                                </option>
                                <option value="rejected" {{ old('document_status') === 'rejected' ? 'selected' : '' }}>
                                    Ditolak
                                </option>
                                <option value="archived" {{ old('document_status') === 'archived' ? 'selected' : '' }}>
                                    Diarsipkan
                                </option>
                            </select>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info alert-sm mb-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Info:</strong> Laporan akan menampilkan statistik dokumentasi lengkap termasuk 
                            total dokumen, distribusi klasifikasi, dan tingkat persetujuan.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-file-earmark-pdf"></i> Buat Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Sidebar -->
        <div class="col-md-4">
            <div class="card bg-light shadow-sm">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-question-circle"></i> Panduan</h6>
                    <ul class="small">
                        <li><strong>Klasifikasi:</strong> Filter berdasarkan tingkat kerahasiaan</li>
                        <li><strong>Status:</strong> Lacak dokumen dalam proses persetujuan</li>
                        <li><strong>Metrik:</strong> Lihat tingkat keberhasilan persetujuan</li>
                        <li><strong>Ekspor:</strong> Unduh laporan dalam format PDF/Excel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
