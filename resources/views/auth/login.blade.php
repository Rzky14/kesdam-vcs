@extends('layouts.guest')

@section('content')
<div class="auth-header">
    <h1>KESDAM III/Siliwangi</h1>
    <p>Sistem Manajemen Penjadwalan & Surat Menyurat</p>
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    
    <div class="form-group">
        <label for="email">Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            required 
            autofocus 
            class="{{ $errors->has('email') ? 'error' : '' }}"
            placeholder="nama@email.com"
        >
        @error('email')
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
            placeholder="Masukkan password"
        >
        @error('password')
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="checkbox-group">
        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
        <label for="remember">Ingat Saya</label>
    </div>
    
    <button type="submit" class="btn">Login</button>
</form>

<div class="auth-footer">
    <p>Belum punya akun? <a href="{{ route('register') }}">Daftar Sekarang</a></p>
</div>
@endsection
