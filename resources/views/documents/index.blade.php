@extends('layouts.app')

@section('content')
<style>
    .container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        margin-bottom: 25px;
    }
    
    .page-header h1 {
        color: #1a472a;
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 5px 0;
    }
    
    .page-subtitle {
        color: #666;
        font-size: 14px;
        margin: 0 0 20px 0;
    }
    
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
    }
    
    .btn-primary {
        background: #1a472a;
        color: white;
    }
    
    .btn-primary:hover {
        background: #0d2818;
        transform: translateY(-1px);
    }
    
    .btn-outline {
        background: white;
        color: #1a472a;
        border: 1px solid #1a472a;
    }
    
    .btn-outline:hover {
        background: #1a472a;
        color: white;
    }
    
    .btn-group {
        display: flex;
        gap: 10px;
    }
    
    /* Tab Navigation */
    .tabs {
        display: flex;
        gap: 5px;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 25px;
    }
    
    .tab-item {
        padding: 12px 20px;
        background: none;
        border: none;
        color: #666;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    
    .tab-item:hover {
        color: #1a472a;
    }
    
    .tab-item.active {
        color: #1a472a;
        border-bottom-color: #1a472a;
        font-weight: 600;
    }
    
    /* Search and Filter Bar */
    .filter-bar {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        align-items: center;
    }
    
    .search-box {
        flex: 1;
        position: relative;
    }
    
    .search-box input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    
    .filter-btn {
        padding: 10px 20px;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-btn:hover {
        border-color: #1a472a;
        color: #1a472a;
    }
    
    /* Document Cards */
    .document-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .document-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .document-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: #ccc;
    }
    
    .document-info {
        flex: 1;
    }
    
    .document-title {
        font-size: 16px;
        font-weight: 600;
        color: #1a472a;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .document-details {
        display: flex;
        gap: 25px;
        font-size: 13px;
        color: #666;
        margin-top: 8px;
    }
    
    .document-detail-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .document-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .btn-sm {
        padding: 8px 14px;
        font-size: 13px;
        border-radius: 6px;
    }
    
    .btn-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        color: #666;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-icon:hover {
        background: #e0e0e0;
        border-color: #ccc;
    }
    
    .btn-info {
        background: #e3f2fd;
        color: #1976d2;
        border: 1px solid #1976d2;
    }
    
    .btn-warning {
        background: #fff3e0;
        color: #f57c00;
        border: 1px solid #f57c00;
    }
    
    .btn-danger {
        background: #ffebee;
        color: #d32f2f;
        border: 1px solid #d32f2f;
    }
    
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-biasa {
        background: #0288d1;
        color: white;
    }
    
    .badge-rahasia {
        background: #d32f2f;
        color: white;
    }
    
    .badge-telegram {
        background: #1a472a;
        color: white;
    }
    
    .badge-dibaca {
        background: #9e9e9e;
        color: white;
    }
    
    .badge-diproses {
        background: #f57c00;
        color: white;
    }
    
    .badge-draft {
        background: #9e9e9e;
        color: white;
    }
    
    .badge-pending {
        background: #f57c00;
        color: white;
    }
    
    .badge-approved {
        background: #4caf50;
        color: white;
    }
    
    .alert {
        background: white;
        border-left: 4px solid;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        border-color: #4caf50;
        color: #2e7d32;
        background: #e8f5e9;
    }
    
    .alert-error {
        border-color: #f44336;
        color: #c62828;
        background: #ffebee;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-state svg {
        width: 80px;
        height: 80px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 30px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: #666;
        border: 1px solid #e0e0e0;
        background: white;
    }
    
    .pagination .active {
        background: #1a472a;
        color: white;
        border-color: #1a472a;
    }
</style>

<div class="container">
    <div class="page-header">
        <h1>Surat Menyurat</h1>
        <p class="page-subtitle">Kelola surat masuk dan keluar</p>
    </div>
    
    <div class="header-actions">
        <div class="tabs">
            <a href="{{ route('documents.index') }}" class="tab-item {{ !request('type') ? 'active' : '' }}">
                Surat Masuk
            </a>
            <a href="{{ route('documents.index', ['type' => 'keluar']) }}" class="tab-item {{ request('type') == 'keluar' ? 'active' : '' }}">
                Surat Keluar
            </a>
            <a href="{{ route('documents.index', ['status' => 'archived']) }}" class="tab-item {{ request('status') == 'archived' ? 'active' : '' }}">
                Arsip
            </a>
        </div>
        
        <div class="btn-group">
            @can('create', App\Models\Document::class)
                <a href="{{ route('documents.create', ['type' => 'masuk']) }}" class="btn btn-outline">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                    </svg>
                    Upload Surat Masuk
                </a>
                <a href="{{ route('documents.create', ['type' => 'keluar']) }}" class="btn btn-primary">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Buat Surat Keluar
                </a>
            @endcan
        </div>
    </div>
    
    @if(session('success'))
    <div class="alert alert-success">
        ✓ {{ session('success') }}
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-error">
        ✗ {{ session('error') }}
    </div>
    @endif
    
    <div class="filter-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <form method="GET" action="{{ route('documents.index') }}" style="margin: 0;">
                <input type="hidden" name="type" value="{{ request('type') }}">
                <input type="hidden" name="classification" value="{{ request('classification') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="text" name="search" placeholder="Cari surat..." value="{{ request('search') }}" onchange="this.form.submit()">
            </form>
        </div>
        
        <button class="filter-btn" onclick="document.getElementById('filterModal').style.display='flex'">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z"/>
            </svg>
            Filter
        </button>
    </div>
    
    <div class="document-list">
        @forelse($documents as $document)
        <div class="document-card">
            <div class="document-info">
                <div class="document-title">
                    {{ $document->subject }}
                    
                    @if($document->classification == 'biasa')
                        <span class="badge badge-biasa">Biasa</span>
                    @elseif($document->classification == 'rahasia')
                        <span class="badge badge-rahasia">Rahasia</span>
                    @elseif($document->classification == 'telegram')
                        <span class="badge badge-telegram">Telegram</span>
                    @endif
                    
                    @if($document->status == 'draft')
                        <span class="badge badge-draft">Draft</span>
                    @elseif($document->status == 'pending_approval')
                        <span class="badge badge-pending">Menunggu TTD</span>
                    @elseif($document->status == 'approved')
                        <span class="badge badge-approved">Selesai</span>
                    @elseif(request('status') == 'archived' || $document->archived_at)
                        <span class="badge badge-dibaca">Dibaca</span>
                    @else
                        <span class="badge badge-diproses">Diproses</span>
                    @endif
                </div>
                
                <div class="document-details">
                    <div class="document-detail-item">
                        <strong>Nomor:</strong> {{ $document->number ?? 'Belum ada' }}
                    </div>
                    
                    <div class="document-detail-item">
                        <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($document->date)->format('d M Y') }}
                    </div>
                    
                    @if($document->type == 'masuk' && $document->sender)
                    <div class="document-detail-item">
                        <strong>Dari:</strong> {{ $document->sender }}
                    </div>
                    @elseif($document->type == 'keluar' && $document->recipient)
                    <div class="document-detail-item">
                        <strong>Ke:</strong> {{ $document->recipient }}
                    </div>
                    @endif
                </div>
            </div>
            
            <div class="document-actions">
                <a href="{{ route('documents.show', $document) }}" class="btn-icon" title="Lihat Detail">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                    </svg>
                </a>
                
                @if($document->attachments && count($document->attachments) > 0)
                <a href="{{ route('documents.download', [$document, 0]) }}" class="btn-icon" title="Download">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                </a>
                @endif
                
                @can('update', $document)
                <a href="{{ route('documents.edit', $document) }}" class="btn-icon" title="Edit">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                    </svg>
                </a>
                @endcan
                
                @can('delete', $document)
                <form method="POST" action="{{ route('documents.destroy', $document) }}" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-icon" title="Hapus" style="color: #d32f2f;">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                        </svg>
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <div class="empty-state">
            <svg fill="currentColor" viewBox="0 0 16 16">
                <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
            </svg>
            <p><strong>Belum ada dokumen</strong></p>
            <p>Klik tombol "Buat Surat Keluar" atau "Upload Surat Masuk" untuk menambahkan dokumen</p>
        </div>
        @endforelse
    </div>
    
    @if($documents->hasPages())
    <div class="pagination">
        {{ $documents->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<!-- Filter Modal -->
<div id="filterModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px;">
        <h3 style="margin-top: 0;">Filter Dokumen</h3>
        <form method="GET" action="{{ route('documents.index') }}">
            <input type="hidden" name="type" value="{{ request('type') }}">
            <input type="hidden" name="search" value="{{ request('search') }}">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Klasifikasi</label>
                <select name="classification" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Semua Klasifikasi</option>
                    <option value="biasa" {{ request('classification') == 'biasa' ? 'selected' : '' }}>Biasa</option>
                    <option value="rahasia" {{ request('classification') == 'rahasia' ? 'selected' : '' }}>Rahasia</option>
                    <option value="telegram" {{ request('classification') == 'telegram' ? 'selected' : '' }}>Telegram</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Prioritas</label>
                <select name="priority" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Semua Prioritas</option>
                    <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Tinggi</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Mendesak</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Terapkan Filter</button>
                <button type="button" onclick="document.getElementById('filterModal').style.display='none'" class="btn-icon" style="width: auto; padding: 10px 20px;">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
// Close modal when clicking outside
document.getElementById('filterModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>
@endsection
