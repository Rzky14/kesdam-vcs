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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
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
                    <form action="{{ route('settings.notifications.update') }}" method="POST" id="notificationForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Notifikasi Surat Baru</h6>
                                <small class="text-muted">Pemberitahuan saat ada surat masuk</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="newDocument" name="new_document" value="1"
                                       {{ ($settings['notification']['notification.new_document']['value'] ?? false) ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <label class="form-check-label" for="newDocument"></label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Pengingat Jadwal</h6>
                                <small class="text-muted">Notifikasi sebelum jadwal dimulai</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="scheduleReminder" name="schedule_reminder" value="1"
                                       {{ ($settings['notification']['notification.schedule_reminder']['value'] ?? false) ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <label class="form-check-label" for="scheduleReminder"></label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Notifikasi Persetujuan</h6>
                                <small class="text-muted">Update status persetujuan dokumen</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="approvalStatus" name="approval_status" value="1"
                                       {{ ($settings['notification']['notification.approval_status']['value'] ?? false) ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <label class="form-check-label" for="approvalStatus"></label>
                            </div>
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
                    <form action="{{ route('settings.system.update') }}" method="POST" id="systemForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Backup Otomatis</h6>
                                <small class="text-muted">Backup database harian</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="autoBackup" name="auto_backup" value="1"
                                       {{ ($settings['system']['system.auto_backup']['value'] ?? false) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="autoBackup"></label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <h6 class="mb-1">Format Surat Otomatis</h6>
                                <small class="text-muted">Nomor surat otomatis</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="autoDocNumber" name="auto_document_number" value="1"
                                       {{ ($settings['system']['system.auto_document_number']['value'] ?? false) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="autoDocNumber"></label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Integrasi Kepegawaian</h6>
                                <small class="text-muted">Sinkronisasi data personel</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" data-bs-target="#integrationModal"
                                    {{ !auth()->user()->isAdmin() ? 'disabled' : '' }}>
                                <i class="fas fa-cog"></i> Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Section (Admin Only) -->
    @if(auth()->user()->isAdmin())
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-database"></i> Manajemen Backup</h5>
                        <small class="text-muted">Riwayat backup database</small>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="performBackup()">
                        <i class="fas fa-save"></i> Backup Sekarang
                    </button>
                </div>
                <div class="card-body">
                    @if(count($backupHistory) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nama File</th>
                                    <th>Ukuran</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backupHistory as $backup)
                                <tr>
                                    <td><i class="fas fa-file-archive text-primary"></i> {{ $backup['filename'] }}</td>
                                    <td>{{ $backup['size'] }}</td>
                                    <td>{{ $backup['created_at'] }}</td>
                                    <td>
                                        <a href="{{ asset('storage/backups/' . $backup['filename']) }}" 
                                           class="btn btn-sm btn-outline-primary" download>
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada riwayat backup</p>
                        <button type="button" class="btn btn-primary" onclick="performBackup()">
                            <i class="fas fa-save"></i> Buat Backup Pertama
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Integration Configuration Modal -->
<div class="modal fade" id="integrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plug"></i> Konfigurasi Integrasi Kepegawaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('settings.system.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status Integrasi</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" 
                                   id="personnelIntegration" name="personnel_integration" value="1"
                                   {{ ($settings['system']['system.personnel_integration']['value'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="personnelIntegration">
                                Aktifkan Integrasi
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="apiUrl" class="form-label">URL API</label>
                        <input type="url" class="form-control" id="apiUrl" name="personnel_api_url" 
                               placeholder="https://api.example.com"
                               value="{{ $settings['system']['system.personnel_api_url']['value'] ?? '' }}">
                        <small class="text-muted">URL endpoint API sistem kepegawaian</small>
                    </div>

                    <div class="mb-3">
                        <label for="apiKey" class="form-label">API Key</label>
                        <input type="text" class="form-control" id="apiKey" name="personnel_api_key" 
                               placeholder="Masukkan API Key"
                               value="{{ $settings['system']['system.personnel_api_key']['value'] ?? '' }}">
                        <small class="text-muted">Token autentikasi untuk akses API</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Info:</strong> Integrasi ini akan menyinkronkan data personel secara otomatis setiap hari.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Konfigurasi
                    </button>
                </div>
            </form>
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

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }
</style>

<script>
function performBackup() {
    if (!confirm('Yakin ingin melakukan backup database?')) {
        return;
    }

    // Show loading
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membackup...';
    button.disabled = true;

    fetch('{{ route('settings.backup') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Backup berhasil dibuat!\n\nFile: ' + data.data.filename + '\nUkuran: ' + data.data.size);
            location.reload();
        } else {
            alert('✗ Gagal membuat backup: ' + data.message);
        }
    })
    .catch(error => {
        alert('✗ Terjadi kesalahan: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
@endsection
