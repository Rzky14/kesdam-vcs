@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Edit {{ $document->getTypeLabel() }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Dokumen</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('documents.show', $document) }}">{{ $document->number }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('documents.update', $document) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <input type="hidden" name="type" value="{{ $document->type }}">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="number" class="form-label">Nomor Surat</label>
                        <input type="text" 
                               class="form-control @error('number') is-invalid @enderror" 
                               id="number" 
                               name="number"
                               value="{{ old('number', $document->number) }}"
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
                               value="{{ old('date', $document->date->format('Y-m-d')) }}"
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
                            <option value="biasa" {{ old('classification', $document->classification) === 'biasa' ? 'selected' : '' }}>Biasa</option>
                            <option value="rahasia" {{ old('classification', $document->classification) === 'rahasia' ? 'selected' : '' }}>Rahasia (Terenkripsi)</option>
                            <option value="telegram" {{ old('classification', $document->classification) === 'telegram' ? 'selected' : '' }}>Telegram</option>
                        </select>
                        @error('classification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($document->isClassified())
                            <div class="form-text text-danger">
                                <i class="bi bi-shield-lock"></i> Dokumen ini terenkripsi
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Prioritas <span class="text-danger">*</span></label>
                        <select class="form-select @error('priority') is-invalid @enderror" 
                                id="priority" 
                                name="priority"
                                required>
                            <option value="normal" {{ old('priority', $document->priority) === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ old('priority', $document->priority) === 'high' ? 'selected' : '' }}>Tinggi</option>
                            <option value="urgent" {{ old('priority', $document->priority) === 'urgent' ? 'selected' : '' }}>Mendesak</option>
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="{{ $document->type === 'masuk' ? 'sender' : 'recipient' }}" class="form-label">
                            {{ $document->type === 'masuk' ? 'Pengirim' : 'Penerima' }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error($document->type === 'masuk' ? 'sender' : 'recipient') is-invalid @enderror" 
                               id="{{ $document->type === 'masuk' ? 'sender' : 'recipient' }}" 
                               name="{{ $document->type === 'masuk' ? 'sender' : 'recipient' }}"
                               value="{{ old($document->type === 'masuk' ? 'sender' : 'recipient', $document->type === 'masuk' ? $document->sender : $document->recipient) }}"
                               placeholder="Nama instansi/organisasi"
                               required>
                        @error($document->type === 'masuk' ? 'sender' : 'recipient')
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
                           value="{{ old('subject', $document->subject) }}"
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
                              placeholder="Keterangan tambahan (opsional)">{{ old('description', $document->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Existing Attachments -->
                @if($document->attachments && count($document->attachments) > 0)
                    <div class="mb-3">
                        <label class="form-label">Lampiran yang Ada</label>
                        <div class="list-group">
                            @foreach($document->attachments as $index => $attachment)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="remove_attachments[]" 
                                               value="{{ $attachment }}"
                                               id="remove_{{ $index }}">
                                        <label class="form-check-label" for="remove_{{ $index }}">
                                            <i class="bi bi-paperclip"></i>
                                            {{ basename($attachment) }}
                                        </label>
                                    </div>
                                    <a href="{{ route('documents.download', [$document, $index]) }}" 
                                       class="btn btn-sm" style="color: #1a472a; border-color: #1a472a;"
                                       target="_blank">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text text-danger">
                            <i class="bi bi-exclamation-triangle"></i> Centang file yang ingin dihapus
                        </div>
                    </div>
                @endif

                <!-- New Attachments -->
                <div class="mb-3">
                    <label for="attachments" class="form-label">Tambah Lampiran Baru</label>
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
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%); color: #d4af37; border: 1px solid #d4af37;">
                        <i class="bi bi-save"></i> Simpan Perubahan
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


