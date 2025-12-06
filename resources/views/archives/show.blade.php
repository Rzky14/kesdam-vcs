@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $archive->name }}</h2>
            <small class="text-muted">
                {{ $archive->getCategoryLabel() }} - 
                Disimpan {{ $archive->archive_date->diffForHumans() }}
            </small>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('archives.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Arsip</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted d-block">Kategori</small>
                            <strong>{{ $archive->getCategoryLabel() }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Tanggal Arsip</small>
                            <strong>{{ $archive->archive_date->format('d M Y H:i') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Status Indeks</small>
                            <strong>
                                <span class="badge bg-{{ $archive->is_indexed ? 'success' : 'warning' }}">
                                    {{ $archive->is_indexed ? 'Terindeks' : 'Belum Terindeks' }}
                                </span>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">Tindakan</h5>
                    <div class="d-grid gap-2">
                        <form action="{{ route('archives.destroy', $archive) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin akan menghapus arsip ini?')">
                                <i class="bi bi-trash"></i> Hapus Arsip
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
