@extends('layouts.app')

@section('title', 'Approval History - ' . $document->subject)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">Approval History</h1>
            <p class="text-muted">{{ $document->subject }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Document
            </a>
        </div>
    </div>

    <!-- Document Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Document Number</p>
                            <p class="h6">{{ $document->number ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Type</p>
                            <p class="h6">{{ $document->getTypeLabel() }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Classification</p>
                            <p class="h6">
                                <span class="badge bg-{{ $document->classification === 'rahasia' ? 'danger' : 'secondary' }}">
                                    {{ $document->getClassificationLabel() }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-timeline"></i> Approval Timeline
                    </h5>
                </div>
                <div class="card-body">
                    @if($approvalHistory->count() > 0)
                        <div class="timeline">
                            @foreach($approvalHistory as $history)
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        @switch($history->action)
                                            @case('approved')
                                                <i class="fas fa-check text-success"></i>
                                                @break
                                            @case('rejected')
                                                <i class="fas fa-times text-danger"></i>
                                                @break
                                            @case('correction_requested')
                                                <i class="fas fa-edit text-warning"></i>
                                                @break
                                            @case('submitted')
                                                <i class="fas fa-paper-plane text-info"></i>
                                                @break
                                            @default
                                                <i class="fas fa-circle text-secondary"></i>
                                        @endswitch
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-2">
                                            {{ $history->getActionLabel() }}
                                            @if($history->approval_level)
                                                <span class="badge bg-secondary">Level {{ $history->approval_level }}</span>
                                            @endif
                                        </h6>
                                        <p class="text-muted mb-2">
                                            By: <strong>{{ $history->user->name ?? 'System' }}</strong>
                                        </p>
                                        <p class="text-muted small">
                                            <i class="far fa-calendar"></i> {{ $history->action_date->format('d/m/Y H:i:s') }}
                                        </p>
                                        
                                        @if($history->comment)
                                            <div class="alert alert-info small mt-2">
                                                <strong>Notes:</strong> {{ $history->comment }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No approval history for this document yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Correction Requests Section -->
    @if($correctionRequests->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-exclamation-triangle"></i> Correction Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Requested By</th>
                                        <th>Notes</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($correctionRequests as $correction)
                                        <tr>
                                            <td>{{ $correction->requestedBy->name ?? 'N/A' }}</td>
                                            <td>{{ Str::limit($correction->correction_notes, 50) }}</td>
                                            <td>
                                                @if($correction->due_date)
                                                    <span class="badge bg-{{ $correction->isOverdue() ? 'danger' : 'warning' }}">
                                                        {{ $correction->due_date->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $correction->getStatusBadgeColor() }}">
                                                    {{ $correction->getStatusLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $correction->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Approval Actions -->
    @can('approve', $document)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-gavel"></i> Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <form action="{{ route('approvals.approve', $document) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check-circle"></i> Approve
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="{{ route('approvals.reject-form', $document) }}" class="btn btn-danger w-100">
                                    <i class="fas fa-times-circle"></i> Reject
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="{{ route('approvals.correction-form', $document) }}" class="btn btn-warning w-100">
                                    <i class="fas fa-edit"></i> Request Correction
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>

@push('styles')
<style>
    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        padding: 20px 0;
        padding-left: 40px;
        border-left: 2px solid #dee2e6;
        position: relative;
    }

    .timeline-item:last-child {
        border-left: none;
    }

    .timeline-marker {
        position: absolute;
        left: -12px;
        top: 20px;
        width: 24px;
        height: 24px;
        background: #fff;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .timeline-content {
        padding-left: 20px;
    }
</style>
@endpush
@endsection
