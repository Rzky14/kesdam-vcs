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
    
    .header-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);
        color: #d4af37;
        border: 1px solid #d4af37;
    }
    
    .btn-warning {
        background: #f39c12;
        color: white;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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
        margin-bottom: 20px;
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
        margin-bottom: 5px;
    }
    
    .badge-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .permission-item {
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 13px;
        color: #333;
        border-left: 3px solid #667eea;
    }
    
    .audit-item {
        padding: 15px;
        border-left: 3px solid #667eea;
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 8px;
    }
    
    .audit-event {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .audit-desc {
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .audit-meta {
        font-size: 12px;
        color: #999;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1>Detail User: {{ $user->name }}</h1>
        <div class="header-actions">
            @if(auth()->user()->hasPermission('edit_users'))
                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning">Edit User</a>
            @endif
            @if(auth()->user()->hasPermission('delete_users') && $user->id !== auth()->id())
                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus User</button>
                </form>
            @endif
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
    
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
                        <div class="info-label">Terdaftar Sejak</div>
                        <div class="info-value">{{ $user->created_at->format('d M Y') }}</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Last Login</div>
                        <div class="info-value">
                            {{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'Belum pernah' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Role & Permissions</h2>
                <div style="margin-bottom: 20px;">
                    <strong style="display: block; margin-bottom: 10px;">Roles:</strong>
                    @forelse($user->roles as $role)
                        <span class="badge badge-primary">{{ $role->display_name }}</span>
                    @empty
                        <p style="color: #666;">Belum ada role</p>
                    @endforelse
                </div>
                
                <div>
                    <strong style="display: block; margin-bottom: 10px;">Permissions:</strong>
                    <div class="permissions-grid">
                        @php
                            $permissions = $user->roles->flatMap->permissions->unique('id');
                        @endphp
                        
                        @forelse($permissions as $permission)
                            <div class="permission-item">
                                {{ $permission->display_name }}
                            </div>
                        @empty
                            <p style="color: #666;">Belum ada permissions</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="card">
                <h2>Audit Logs</h2>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">10 aktivitas terakhir terkait user ini</p>
                
                @forelse($auditLogs as $log)
                    <div class="audit-item">
                        <div class="audit-event">{{ ucfirst(str_replace('_', ' ', $log->event)) }}</div>
                        @if($log->description)
                            <div class="audit-desc">{{ $log->description }}</div>
                        @endif
                        <div class="audit-meta">
                            <span>📅 {{ $log->created_at->format('d M Y, H:i') }}</span>
                            @if($log->user)
                                <span>👤 {{ $log->user->name }}</span>
                            @endif
                            @if($log->ip_address)
                                <span>🌐 {{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: #666; text-align: center; padding: 20px;">Belum ada audit log</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

