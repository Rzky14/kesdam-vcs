@extends('layouts.app')

@section('content')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
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
        margin-bottom: 20px;
    }
    
    .card h2 {
        color: #333;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e1e8ed;
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
    input[type="email"],
    input[type="password"],
    input[type="tel"],
    textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    input:focus, textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .error-message {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 5px;
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
</style>

<div class="container">
    <div class="page-header">
        <h1>Edit Profile</h1>
    </div>
    
    <div class="card">
        <h2>Informasi Personal</h2>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Lengkap *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $user->name) }}" 
                        required
                    >
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="nrp">NRP *</label>
                    <input 
                        type="text" 
                        id="nrp" 
                        name="nrp" 
                        value="{{ old('nrp', $user->nrp) }}" 
                        required
                    >
                    @error('nrp')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="rank">Pangkat *</label>
                    <input 
                        type="text" 
                        id="rank" 
                        name="rank" 
                        value="{{ old('rank', $user->rank) }}" 
                        required
                    >
                    @error('rank')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="position">Jabatan *</label>
                    <input 
                        type="text" 
                        id="position" 
                        name="position" 
                        value="{{ old('position', $user->position) }}" 
                        required
                    >
                    @error('position')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group">
                <label for="unit">Satuan *</label>
                <input 
                    type="text" 
                    id="unit" 
                    name="unit" 
                    value="{{ old('unit', $user->unit) }}" 
                    required
                >
                @error('unit')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email', $user->email) }}" 
                        required
                    >
                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="phone">No. Telepon</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="{{ old('phone', $user->phone) }}"
                    >
                    @error('phone')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Alamat</label>
                <textarea 
                    id="address" 
                    name="address" 
                    rows="3"
                >{{ old('address', $user->address) }}</textarea>
                @error('address')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('profile.show') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h2>Ubah Password</h2>
        <form method="POST" action="{{ route('profile.password.update') }}">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="current_password">Password Saat Ini *</label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    required
                >
                @error('current_password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password Baru *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                    >
                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru *</label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required
                    >
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</div>
@endsection

