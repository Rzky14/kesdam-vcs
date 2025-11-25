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
        max-width: 1400px;
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
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(26, 71, 42, 0.3);
        color: #fff;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .btn-info {
        background: #3498db;
        color: white;
    }
    
    .btn-warning {
        background: #f39c12;
        color: white;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
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
    
    .alert-error {
        border-left: 4px solid #e74c3c;
        color: #721c24;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        margin-bottom: 20px;
    }
    
    .search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .search-form input,
    .search-form select {
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .search-form input {
        flex: 1;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    thead {
        background: #f8f9fa;
    }
    
    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e1e8ed;
    }
    
    th {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    td {
        color: #666;
        font-size: 14px;
    }
    
    tbody tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-primary {
        background: #cce5ff;
        color: #004085;
    }
    
    .actions {
        display: flex;
        gap: 5px;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: #667eea;
        font-size: 14px;
    }
    
    .pagination .active {
        background: #667eea;
        color: white;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1>User Management</h1>
        @if(auth()->user()->hasPermission('create_users'))
            <a href="{{ route('users.create') }}" class="btn btn-primary">+ Tambah User Baru</a>
        @endif
    </div>
    
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif
    
    <div class="card">
        <form method="GET" action="{{ route('users.index') }}" class="search-form">
            <input type="text" name="search" placeholder="Cari nama, email, NRP, pangkat, atau jabatan..." value="{{ request('search') }}">
            <select name="role">
                <option value="">Semua Role</option>
                <option value="admin_sistem" {{ request('role') == 'admin_sistem' ? 'selected' : '' }}>Admin Sistem</option>
                <option value="pimpinan" {{ request('role') == 'pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                <option value="kasi_kaur" {{ request('role') == 'kasi_kaur' ? 'selected' : '' }}>Kasi/Kaur</option>
                <option value="batih_staf" {{ request('role') == 'batih_staf' ? 'selected' : '' }}>Batih/Staf</option>
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>NRP</th>
                        <th>Nama</th>
                        <th>Pangkat</th>
                        <th>Jabatan</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->nrp }}</td>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->rank }}</td>
                            <td>{{ $user->position }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge badge-primary">{{ $role->display_name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    @if(auth()->user()->hasPermission('view_users'))
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-info btn-sm">Detail</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('edit_users'))
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm">Edit</a>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete_users') && $user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                Tidak ada data user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="pagination">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
