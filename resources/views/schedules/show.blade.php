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
    
    .action-buttons {
        display: flex;
        gap: 10px;
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
    
    .btn-warning {
        background: #f39c12;
        color: white;
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    
    .content-grid {
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
    
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e1e8ed;
    }
    
    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-label {
        font-weight: 600;
        color: #666;
    }
    
    .detail-value {
        color: #333;
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
    
    .personnel-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .personnel-card {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .personnel-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    
    .personnel-details {
        font-size: 12px;
        color: #666;
    }
    
    .audit-log {
        font-size: 13px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
        border-left: 3px solid #667eea;
    }
    
    .audit-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    
    .audit-user {
        font-weight: 600;
        color: #333;
    }
    
    .audit-time {
        color: #999;
        font-size: 11px;
    }
    
    .audit-action {
        color: #666;
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
</style>

<div class="container">
    <a href="{{ route('schedules.index') }}" class="back-link">← Kembali ke Daftar Jadwal</a>
    
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    
    <div class="page-header">
        <h1>Detail Jadwal</h1>
        <div class="action-buttons">
            @if(Auth::user()->hasPermission('edit_schedules'))
            <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-warning">Edit</a>
            @endif
            @if(Auth::user()->hasPermission('delete_schedules'))
            <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Hapus</button>
            </form>
            @endif
        </div>
    </div>
    
    <div class="content-grid">
        <div>
            <div class="card">
                <div class="card-title">Informasi Jadwal</div>
                
                <div class="detail-row">
                    <div class="detail-label">Jenis Jadwal:</div>
                    <div class="detail-value">
                        @if($schedule->type == 'dukkes')
                            <span class="badge badge-info">{{ $schedule->getTypeLabel() }}</span>
                        @elseif($schedule->type == 'jaga')
                            <span class="badge badge-warning">{{ $schedule->getTypeLabel() }}</span>
                        @else
                            <span class="badge badge-primary">{{ $schedule->getTypeLabel() }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Judul:</div>
                    <div class="detail-value"><strong>{{ $schedule->title }}</strong></div>
                </div>
                
                @if($schedule->description)
                <div class="detail-row">
                    <div class="detail-label">Deskripsi:</div>
                    <div class="detail-value">{{ $schedule->description }}</div>
                </div>
                @endif
                
                <div class="detail-row">
                    <div class="detail-label">Tanggal:</div>
                    <div class="detail-value">
                        {{ \Carbon\Carbon::parse($schedule->start_date)->format('d/m/Y') }}
                        @if($schedule->end_date != $schedule->start_date)
                            s/d {{ \Carbon\Carbon::parse($schedule->end_date)->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
                
                @if($schedule->start_time || $schedule->end_time)
                <div class="detail-row">
                    <div class="detail-label">Waktu:</div>
                    <div class="detail-value">
                        @if($schedule->start_time)
                            {{ substr($schedule->start_time, 0, 5) }}
                        @endif
                        @if($schedule->end_time)
                            - {{ substr($schedule->end_time, 0, 5) }}
                        @endif
                    </div>
                </div>
                @endif
                
                @if($schedule->location)
                <div class="detail-row">
                    <div class="detail-label">Lokasi:</div>
                    <div class="detail-value">{{ $schedule->location }}</div>
                </div>
                @endif
                
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="badge badge-{{ $schedule->getStatusColor() }}">
                            {{ $schedule->getStatusLabel() }}
                        </span>
                    </div>
                </div>
                
                @if($schedule->notes)
                <div class="detail-row">
                    <div class="detail-label">Catatan:</div>
                    <div class="detail-value">{{ $schedule->notes }}</div>
                </div>
                @endif
                
                <div class="detail-row">
                    <div class="detail-label">Dibuat oleh:</div>
                    <div class="detail-value">
                        {{ $schedule->creator->rank }} {{ $schedule->creator->name }}
                        <br>
                        <small style="color: #999;">{{ $schedule->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
                
                @if($schedule->updated_at != $schedule->created_at)
                <div class="detail-row">
                    <div class="detail-label">Terakhir diubah:</div>
                    <div class="detail-value">
                        {{ $schedule->updater ? $schedule->updater->rank . ' ' . $schedule->updater->name : '-' }}
                        <br>
                        <small style="color: #999;">{{ $schedule->updated_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
                @endif
            </div>
            
            <div class="card">
                <div class="card-title">Personel yang Ditugaskan ({{ count($personnel) }})</div>
                <div class="personnel-list">
                    @forelse($personnel as $person)
                    <div class="personnel-card">
                        <div class="personnel-name">{{ $person->rank }} {{ $person->name }}</div>
                        <div class="personnel-details">
                            {{ $person->position }} | NRP: {{ $person->nrp }}
                            @if($person->phone)
                                | Tel: {{ $person->phone }}
                            @endif
                        </div>
                    </div>
                    @empty
                    <p style="color: #999; text-align: center;">Tidak ada personel yang ditugaskan.</p>
                    @endforelse
                </div>
            </div>
        </div>
        
        <div>
            <div class="card">
                <div class="card-title">Riwayat Perubahan</div>
                @forelse($auditLogs as $log)
                <div class="audit-log">
                    <div class="audit-header">
                        <span class="audit-user">{{ $log->user->rank ?? '' }} {{ $log->user->name ?? 'System' }}</span>
                        <span class="audit-time">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="audit-action">
                        @if($log->action == 'created')
                            ✨ Membuat jadwal
                        @elseif($log->action == 'updated')
                            ✏️ Mengubah jadwal
                        @elseif($log->action == 'deleted')
                            🗑️ Menghapus jadwal
                        @else
                            📋 {{ ucfirst($log->action) }}
                        @endif
                    </div>
                </div>
                @empty
                <p style="color: #999; text-align: center;">Belum ada riwayat perubahan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
