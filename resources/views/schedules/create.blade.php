@extends('layouts.app')

@section('content')
<style>
    .container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .page-header {
        background: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .page-header h1 {
        color: #333;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    label {
        display: block;
        color: #333;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    input[type="text"],
    input[type="date"],
    input[type="time"],
    select,
    textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #1a472a;
        box-shadow: 0 0 0 3px rgba(26, 71, 42, 0.1);
    }
    
    .error-message {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .alert {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .alert-error {
        border-left: 4px solid #e74c3c;
        color: #721c24;
        background: #f8d7da;
    }
    
    .personnel-select {
        max-height: 300px;
        overflow-y: auto;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        padding: 10px;
    }
    
    .personnel-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .personnel-item:hover {
        background: #e9ecef;
    }
    
    .personnel-item input[type="checkbox"] {
        width: auto;
    }
    
    .btn {
        padding: 14px 24px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);
        color: #d4af37;
        border: 1px solid #d4af37;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    
    .back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: white;
        text-decoration: none;
        font-weight: 600;
    }
    
    .back-link:hover {
        text-decoration: underline;
    }
    
    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
</style>

<div class="container">
    <a href="{{ route('schedules.index') }}" class="back-link">← Kembali ke Daftar Jadwal</a>
    
    <div class="page-header">
        <h1>Tambah Jadwal Baru</h1>
    </div>
    
    @if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
    @endif
    
    <div class="card">
        <form method="POST" action="{{ route('schedules.store') }}">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Jenis Jadwal *</label>
                    <select id="type" name="type" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="dukkes" {{ old('type') == 'dukkes' ? 'selected' : '' }}>Jadwal Dukkes</option>
                        <option value="jaga" {{ old('type') == 'jaga' ? 'selected' : '' }}>Jadwal Jaga</option>
                        <option value="kegiatan_satuan" {{ old('type') == 'kegiatan_satuan' ? 'selected' : '' }}>Jadwal Kegiatan Satuan</option>
                    </select>
                    @error('type')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group">
                <label for="title">Judul *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    value="{{ old('title') }}" 
                    required
                    placeholder="Contoh: Jadwal Jaga Rutin Minggu Pertama Desember"
                >
                @error('title')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea 
                    id="description" 
                    name="description" 
                    placeholder="Deskripsi lengkap tentang jadwal ini..."
                >{{ old('description') }}</textarea>
                @error('description')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Tanggal Mulai *</label>
                    <input 
                        type="date" 
                        id="start_date" 
                        name="start_date" 
                        value="{{ old('start_date') }}" 
                        required
                    >
                    @error('start_date')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="end_date">Tanggal Selesai *</label>
                    <input 
                        type="date" 
                        id="end_date" 
                        name="end_date" 
                        value="{{ old('end_date') }}" 
                        required
                    >
                    @error('end_date')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_time">Waktu Mulai (Opsional)</label>
                    <input 
                        type="time" 
                        id="start_time" 
                        name="start_time" 
                        value="{{ old('start_time') }}"
                    >
                    <div class="help-text">Kosongkan jika tidak ada waktu spesifik</div>
                    @error('start_time')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="end_time">Waktu Selesai (Opsional)</label>
                    <input 
                        type="time" 
                        id="end_time" 
                        name="end_time" 
                        value="{{ old('end_time') }}"
                    >
                    <div class="help-text">Kosongkan jika tidak ada waktu spesifik</div>
                    @error('end_time')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group">
                <label for="location">Lokasi</label>
                <input 
                    type="text" 
                    id="location" 
                    name="location" 
                    value="{{ old('location') }}" 
                    placeholder="Contoh: Markas Kesdam, Ruang Rapat Lt. 2"
                >
                @error('location')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label>Personel yang Ditugaskan *</label>
                <div class="personnel-select">
                    @forelse($users as $user)
                    <div class="personnel-item">
                        <input 
                            type="checkbox" 
                            name="personnel[]" 
                            value="{{ $user->id }}" 
                            id="personnel_{{ $user->id }}"
                            {{ in_array($user->id, old('personnel', [])) ? 'checked' : '' }}
                        >
                        <label for="personnel_{{ $user->id }}" style="margin: 0; cursor: pointer; flex: 1;">
                            <strong>{{ $user->rank }} {{ $user->name }}</strong><br>
                            <small style="color: #6c757d;">{{ $user->position }} - NRP: {{ $user->nrp }}</small>
                        </label>
                    </div>
                    @empty
                    <p style="text-align: center; color: #999;">Tidak ada user aktif yang tersedia.</p>
                    @endforelse
                </div>
                <div class="help-text">Pilih minimal 1 personel yang akan ditugaskan</div>
                @error('personnel')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="notes">Catatan</label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    placeholder="Catatan tambahan atau keterangan khusus..."
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                <a href="{{ route('schedules.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

