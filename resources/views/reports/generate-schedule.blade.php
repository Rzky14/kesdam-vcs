@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-file-earmark-text"></i> Laporan Bulanan Jadwal</h2>
            <p class="text-muted">Buat laporan statistik jadwal dukkes, jaga, dan kegiatan satuan</p>
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
                    <form action="{{ route('reports.store.schedule') }}" method="POST">
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

                        <!-- Jenis Jadwal -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-list-check"></i> Jenis Jadwal (Pilih salah satu atau lebih)
                            </label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="type_dukkes" 
                                       name="schedule_types[]" value="Jadwal Dukkes" 
                                       {{ in_array('Jadwal Dukkes', (array)old('schedule_types', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_dukkes">
                                    Jadwal Dukkes (Kesehatan)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="type_jaga" 
                                       name="schedule_types[]" value="Jadwal Jaga" 
                                       {{ in_array('Jadwal Jaga', (array)old('schedule_types', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_jaga">
                                    Jadwal Jaga (Penjagaan)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="type_kegiatan" 
                                       name="schedule_types[]" value="Jadwal Kegiatan Satuan" 
                                       {{ in_array('Jadwal Kegiatan Satuan', (array)old('schedule_types', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="type_kegiatan">
                                    Jadwal Kegiatan Satuan
                                </label>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info alert-sm mb-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Info:</strong> Laporan akan mencakup statistik lengkap meliputi jumlah jadwal, 
                            status pelaksanaan, dan durasi rata-rata untuk periode yang dipilih.
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
                        <li>Pilih rentang tanggal untuk analisis</li>
                        <li>Pilih jenis jadwal yang ingin dianalisis</li>
                        <li>Sistem akan mengumpulkan data dan membuat laporan statistik</li>
                        <li>Laporan dapat diekspor ke PDF atau Excel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
