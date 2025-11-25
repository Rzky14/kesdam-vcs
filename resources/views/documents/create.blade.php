@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Buat {{ $type === 'masuk' ? 'Surat Masuk' : 'Surat Keluar' }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Dokumen</a></li>
                    <li class="breadcrumb-item active">Buat Dokumen</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="type" value="{{ $type }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="number" class="form-label">Nomor Surat <small class="text-muted">(Kosongkan untuk auto-generate)</small></label>
                        <input type="text" 
                               class="form-control @error('number') is-invalid @enderror" 
                               id="number" 
                               name="number"
                               value="{{ old('number') }}"
                               placeholder="Contoh: SM/0001/11/2025">
                        @error('number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
                        <input type="date" 
                               class="form-control @error('date') is-invalid @enderror" 
                               id="date" 
                               name="date"
                               value="{{ old('date', date('Y-m-d')) }}"
                               required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="classification" class="form-label">Klasifikasi <span class="text-danger">*</span></label>
                        <select class="form-select @error('classification') is-invalid @enderror" 
                                id="classification" 
                                name="classification"
                                required>
                            <option value="">Pilih Klasifikasi</option>
                            <option value="biasa" {{ old('classification') === 'biasa' ? 'selected' : '' }}>Biasa</option>
                            <option value="rahasia" {{ old('classification') === 'rahasia' ? 'selected' : '' }}>Rahasia (Terenkripsi)</option>
                            <option value="telegram" {{ old('classification') === 'telegram' ? 'selected' : '' }}>Telegram</option>
                        </select>
                        @error('classification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Dokumen dengan klasifikasi "Rahasia" akan dienkripsi otomatis
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Prioritas <span class="text-danger">*</span></label>
                        <select class="form-select @error('priority') is-invalid @enderror" 
                                id="priority" 
                                name="priority"
                                required>
                            <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Tinggi</option>
                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Mendesak</option>
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="{{ $type === 'masuk' ? 'sender' : 'recipient' }}" class="form-label">
                            {{ $type === 'masuk' ? 'Pengirim' : 'Penerima' }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error($type === 'masuk' ? 'sender' : 'recipient') is-invalid @enderror" 
                               id="{{ $type === 'masuk' ? 'sender' : 'recipient' }}" 
                               name="{{ $type === 'masuk' ? 'sender' : 'recipient' }}"
                               value="{{ old($type === 'masuk' ? 'sender' : 'recipient') }}"
                               placeholder="Nama instansi/organisasi"
                               required>
                        @error($type === 'masuk' ? 'sender' : 'recipient')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Perihal/Subjek <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('subject') is-invalid @enderror" 
                           id="subject" 
                           name="subject"
                           value="{{ old('subject') }}"
                           placeholder="Perihal surat"
                           required>
                    @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Keterangan</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description"
                              rows="4"
                              placeholder="Keterangan tambahan (opsional)">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="attachments" class="form-label">Lampiran File</label>
                    <input type="file" 
                           class="form-control @error('attachments.*') is-invalid @enderror" 
                           id="attachments" 
                           name="attachments[]"
                           multiple
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Maksimal 10MB per file.
                    </div>
                    @error('attachments.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%); color: #d4af37; border: 1px solid #d4af37;">
                        <i class="bi bi-save"></i> Simpan sebagai Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('classification').addEventListener('change', function() {
    const classification = this.value;
    const alert = document.getElementById('encryption-alert');
    
    if (classification === 'rahasia') {
        if (!alert) {
            const alertDiv = document.createElement('div');
            alertDiv.id = 'encryption-alert';
            alertDiv.className = 'alert alert-warning mt-3';
            alertDiv.innerHTML = '<i class="bi bi-shield-lock"></i> <strong>Perhatian:</strong> Dokumen ini akan dienkripsi secara otomatis untuk keamanan.';
            this.closest('.col-md-4').appendChild(alertDiv);
        }
    } else {
        if (alert) {
            alert.remove();
        }
    }
});
</script>
@endpush
@endsection
