@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="fas fa-cog"></i> Pengaturan</h2>
        <p class="text-muted mb-0">Konfigurasi sistem dan preferensi</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Notifikasi Section -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Notifikasi</h5>
                    <small class="text-muted">Atur preferensi notifikasi</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.notifications.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Notifikasi Surat Baru</h6>
                                <small class="text-muted">Pemberitahuan saat ada surat masuk</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary">Aktif</button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Pengingat Jadwal</h6>
                                <small class="text-muted">Notifikasi sebelum jadwal dimulai</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary">Aktif</button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Notifikasi Persetujuan</h6>
                                <small class="text-muted">Update status persetujuan dokumen</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary">Aktif</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sistem Section -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-server"></i> Sistem</h5>
                    <small class="text-muted">Pengaturan sistem umum</small>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">Backup Otomatis</h6>
                            <small class="text-muted">Backup database harian</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary">Aktif</button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">Format Surat Otomatis</h6>
                            <small class="text-muted">Nomor surat otomatis</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary">Aktif</button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Integrasi Kepegawaian</h6>
                            <small class="text-muted">Sinkronisasi data personel</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary">Konfigurasi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Arsip Section -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="fas fa-archive"></i> Arsip</h5>
                    <small class="text-muted">Manajemen arsip dan penyimpanan</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="mb-2">Retention Policy</h6>
                                <p class="text-muted small">Atur berapa lama dokumen harus disimpan sebelum bisa dihapus permanen</p>
                                <div class="input-group">
                                    <input type="number" class="form-control" value="5" min="1">
                                    <span class="input-group-text">Tahun</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="mb-2">Auto Archive</h6>
                                <p class="text-muted small">Arsipkan otomatis dokumen yang sudah selesai</p>
                                <select class="form-select">
                                    <option>Setelah 30 hari</option>
                                    <option>Setelah 60 hari</option>
                                    <option>Setelah 90 hari</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('archives.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-folder-open"></i> Buka Arsip
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="alert('Fitur dalam pengembangan')">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
</style>
@endsection
