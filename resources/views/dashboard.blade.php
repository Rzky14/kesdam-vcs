@extends('layouts.app')

@section('content')
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .dashboard-header {
        background: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .dashboard-header h1 {
        color: #333;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    
    .dashboard-header p {
        color: #666;
        margin: 5px 0 0 0;
    }
    
    .logout-form {
        margin: 0;
    }
    
    .btn-logout {
        padding: 10px 24px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-logout:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }
    
    .alert {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .alert-success {
        border-left: 4px solid #27ae60;
        color: #155724;
    }
    
    .user-info-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .user-info-card h2 {
        color: #333;
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e1e8ed;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 3px solid #667eea;
    }
    
    .info-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 16px;
        color: #333;
        font-weight: 500;
    }
    
    .roles-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px solid #e1e8ed;
    }
    
    .roles-section h3 {
        color: #333;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .roles-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .role-badge {
        padding: 8px 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div>
            <h1>Dashboard</h1>
            <p>Selamat datang, {{ Auth::user()->name }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
    
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    <div class="user-info-card">
        <h2>Informasi Pengguna</h2>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nama Lengkap</div>
                <div class="info-value">{{ Auth::user()->name }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">NRP</div>
                <div class="info-value">{{ Auth::user()->nrp ?? '-' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Pangkat</div>
                <div class="info-value">{{ Auth::user()->rank ?? '-' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Jabatan</div>
                <div class="info-value">{{ Auth::user()->position ?? '-' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Satuan</div>
                <div class="info-value">{{ Auth::user()->unit ?? '-' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">{{ Auth::user()->email }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">No. Telepon</div>
                <div class="info-value">{{ Auth::user()->phone ?? '-' }}</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge {{ Auth::user()->is_active ? 'status-active' : 'status-inactive' }}">
                        {{ Auth::user()->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Last Login</div>
                <div class="info-value">
                    {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->format('d M Y, H:i') : 'Belum pernah login' }}
                </div>
            </div>
        </div>
        
        <div class="roles-section">
            <h3>Role & Permissions</h3>
            <div class="roles-list">
                @forelse(Auth::user()->roles as $role)
                    <span class="role-badge">{{ $role->display_name }}</span>
                @empty
                    <p style="color: #666;">Belum ada role yang diberikan</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
