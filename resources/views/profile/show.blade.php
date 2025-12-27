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
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .page-header {
        background: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-header h1 {
        color: #333;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);
        color: #d4af37;
        border: 1px solid #d4af37;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(26, 71, 42, 0.3);
        color: #fff;
    }
    
    .alert {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #27ae60;
        color: #155724;
    }
    
    .grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .card h2 {
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
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        margin-right: 5px;
    }
    
    .badge-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .activity-item {
        padding: 15px;
        border-left: 3px solid #667eea;
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 8px;
    }
    
    .activity-event {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .activity-desc {
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .activity-time {
        font-size: 12px;
        color: #999;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1>Profile Saya</h1>
        <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
    </div>
    
    @if(session('success'))
        <div class="alert">
            {{ session('success') }}
        </div>
    @endif
    
    <div class="grid">
        <div>
            <div class="card">
                <h2>Informasi Personal</h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value">{{ $user->name }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">NRP</div>
                        <div class="info-value">{{ $user->nrp ?? '-' }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Pangkat</div>
                        <div class="info-value">{{ $user->rank ?? '-' }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Jabatan</div>
                        <div class="info-value">{{ $user->position ?? '-' }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Satuan</div>
                        <div class="info-value">{{ $user->unit ?? '-' }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $user->email }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">No. Telepon</div>
                        <div class="info-value">{{ $user->phone ?? '-' }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Last Login</div>
                        <div class="info-value">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Belum pernah' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Role & Permissions</h2>
                <div>
                    @forelse($user->roles as $role)
                        <span class="badge badge-primary">{{ $role->display_name }}</span>
                    @empty
                        <p style="color: #666;">Belum ada role</p>
                    @endforelse
                </div>
            </div>
        </div>
        
        <div>
            <div class="card">
                <h2>Aktivitas Terkini</h2>
                @forelse($recentActivities as $activity)
                    <div class="activity-item">
                        <div class="activity-event">{{ ucfirst(str_replace('_', ' ', $activity->event)) }}</div>
                        @if($activity->description)
                            <div class="activity-desc">{{ $activity->description }}</div>
                        @endif
                        <div class="activity-time">{{ $activity->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <p style="color: #666; text-align: center; padding: 20px;">Belum ada aktivitas</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

