@extends('layouts.guest')

@section('content')
<div class="auth-header">
    <h1>Daftar Akun Baru</h1>
    <p>KESDAM III/Siliwangi - Sistem VCS</p>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    
    <div class="form-group">
        <label for="name">Nama Lengkap</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            value="{{ old('name') }}" 
            required 
            autofocus 
            class="{{ $errors->has('name') ? 'error' : '' }}"
            placeholder="Nama lengkap dengan gelar"
        >
        @error('name')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="nrp">NRP (Nomor Registrasi Pokok)</label>
        <input 
            type="text" 
            id="nrp" 
            name="nrp" 
            value="{{ old('nrp') }}" 
            required 
            class="{{ $errors->has('nrp') ? 'error' : '' }}"
            placeholder="Contoh: 21050012345678"
        >
        @error('nrp')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="rank">Pangkat</label>
        <input 
            type="text" 
            id="rank" 
            name="rank" 
            value="{{ old('rank') }}" 
            required 
            class="{{ $errors->has('rank') ? 'error' : '' }}"
            placeholder="Contoh: Serda, Sertu, Mayor, dll"
        >
        @error('rank')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="position">Jabatan</label>
        <input 
            type="text" 
            id="position" 
            name="position" 
            value="{{ old('position') }}" 
            required 
            class="{{ $errors->has('position') ? 'error' : '' }}"
            placeholder="Contoh: Kaur, Kasi, Batih, dll"
        >
        @error('position')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="unit">Satuan</label>
        <input 
            type="text" 
            id="unit" 
            name="unit" 
            value="{{ old('unit') }}" 
            required 
            class="{{ $errors->has('unit') ? 'error' : '' }}"
            placeholder="Contoh: KESDAM III/Siliwangi"
        >
        @error('unit')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="email">Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            required 
            class="{{ $errors->has('email') ? 'error' : '' }}"
            placeholder="nama@email.com"
        >
        @error('email')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="phone">No. Telepon (Opsional)</label>
        <input 
            type="tel" 
            id="phone" 
            name="phone" 
            value="{{ old('phone') }}" 
            class="{{ $errors->has('phone') ? 'error' : '' }}"
            placeholder="08xxxxxxxxxx"
        >
        @error('phone')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password">Password</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            required 
            class="{{ $errors->has('password') ? 'error' : '' }}"
            placeholder="Minimal 8 karakter"
        >
        @error('password')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="form-group">
        <label for="password_confirmation">Konfirmasi Password</label>
        <input 
            type="password" 
            id="password_confirmation" 
            name="password_confirmation" 
            required 
            placeholder="Ketik ulang password"
        >
    </div>
    
    <button type="submit" class="btn">Daftar</button>
</form>

<div class="auth-footer">
    <p>Sudah punya akun? <a href="{{ route('login') }}">Login Sekarang</a></p>
</div>
@endsection

