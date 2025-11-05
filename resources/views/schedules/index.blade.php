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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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
    
    .btn-secondary {
        background: #95a5a6;
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
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .filter-form input,
    .filter-form select {
        padding: 10px 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-size: 14px;
        width: 100%;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
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
        transition: background 0.2s;
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .badge-primary {
        background: #cce5ff;
        color: #004085;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        background: white;
        border: 2px solid #e1e8ed;
    }
    
    .pagination .active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: #667eea;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
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
</style>

<div class="container">
    <a href="{{ route('dashboard') }}" class="back-link">← Kembali ke Dashboard</a>
    
    <div class="page-header">
        <h1>Manajemen Jadwal</h1>
        @if(Auth::user()->hasPermission('create_schedules'))
        <a href="{{ route('schedules.create') }}" class="btn btn-primary">+ Tambah Jadwal</a>
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
        <form method="GET" action="{{ route('schedules.index') }}" class="filter-form">
            <input type="text" name="search" placeholder="Cari judul, deskripsi, lokasi..." value="{{ request('search') }}">
            
            <select name="type">
                <option value="">-- Semua Jenis --</option>
                <option value="dukkes" {{ request('type') == 'dukkes' ? 'selected' : '' }}>Jadwal Dukkes</option>
                <option value="jaga" {{ request('type') == 'jaga' ? 'selected' : '' }}>Jadwal Jaga</option>
                <option value="kegiatan_satuan" {{ request('type') == 'kegiatan_satuan' ? 'selected' : '' }}>Jadwal Kegiatan Satuan</option>
            </select>
            
            <select name="status">
                <option value="">-- Semua Status --</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            
            <input type="date" name="start_date" placeholder="Dari Tanggal" value="{{ request('start_date') }}">
            
            <input type="date" name="end_date" placeholder="Sampai Tanggal" value="{{ request('end_date') }}">
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('schedules.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Judul</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Lokasi</th>
                        <th>Personel</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr>
                        <td>
                            @if($schedule->type == 'dukkes')
                                <span class="badge badge-info">{{ $schedule->getTypeLabel() }}</span>
                            @elseif($schedule->type == 'jaga')
                                <span class="badge badge-warning">{{ $schedule->getTypeLabel() }}</span>
                            @else
                                <span class="badge badge-primary">{{ $schedule->getTypeLabel() }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $schedule->title }}</strong></td>
                        <td>
                            {{ \Carbon\Carbon::parse($schedule->start_date)->format('d/m/Y') }}
                            @if($schedule->end_date != $schedule->start_date)
                                - {{ \Carbon\Carbon::parse($schedule->end_date)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td>
                            @if($schedule->start_time)
                                {{ substr($schedule->start_time, 0, 5) }}
                                @if($schedule->end_time)
                                    - {{ substr($schedule->end_time, 0, 5) }}
                                @endif
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                        <td>{{ $schedule->location ?? '-' }}</td>
                        <td>
                            <span class="badge badge-secondary">
                                {{ count($schedule->personnel) }} orang
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $schedule->getStatusColor() }}">
                                {{ $schedule->getStatusLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('schedules.show', $schedule) }}" class="btn btn-sm btn-info">Detail</a>
                                @if(Auth::user()->hasPermission('edit_schedules'))
                                <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endif
                                @if(Auth::user()->hasPermission('delete_schedules'))
                                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #999;">
                            Tidak ada jadwal yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="pagination">
            {{ $schedules->links() }}
        </div>
    </div>
</div>
@endsection
