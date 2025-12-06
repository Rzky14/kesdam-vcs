@extends('layouts.app')

@section('title', 'Approval Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">Approval Dashboard</h1>
            <p class="text-muted">Manage document approvals and corrections</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('approvals.corrections') }}" class="btn btn-warning">
                <i class="fas fa-exclamation-circle"></i> Pending Corrections
                <span class="badge bg-danger">{{ $statistics['pending_corrections_count'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="text-primary font-weight-bold text-uppercase mb-1">Pending Approvals</div>
                    <div class="h3 mb-0">{{ $statistics['pending_approvals_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="text-success font-weight-bold text-uppercase mb-1">Recent Approvals</div>
                    <div class="h3 mb-0">{{ $recentApprovals->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="text-warning font-weight-bold text-uppercase mb-1">Overdue Deadlines</div>
                    <div class="h3 mb-0">{{ $statistics['overdue_deadlines_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <div class="text-danger font-weight-bold text-uppercase mb-1">Corrections Needed</div>
                    <div class="h3 mb-0">{{ $statistics['pending_corrections_count'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-hourglass-half"></i> Pending Approvals
                    </h5>
                </div>
                <div class="card-body">
                    @if($pendingApprovals->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Document</th>
                                        <th>Type</th>
                                        <th>Classification</th>
                                        <th>From</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingApprovals as $doc)
                                        <tr>
                                            <td>
                                                <a href="{{ route('documents.show', $doc) }}">
                                                    {{ $doc->subject }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $doc->getTypeLabel() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $doc->classification === 'rahasia' ? 'danger' : 'secondary' }}">
                                                    {{ $doc->getClassificationLabel() }}
                                                </span>
                                            </td>
                                            <td>{{ $doc->creator->name ?? 'N/A' }}</td>
                                            <td>{{ $doc->created_at->diffForHumans() }}</td>
                                            <td>
                                                <a href="{{ route('approvals.show', $doc) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Review
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                                <p>No pending approvals at this time</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> You have no pending approvals!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Approvals -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Recent Approvals
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Document</th>
                                    <th>Action</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentApprovals as $approval)
                                    <tr>
                                        <td>
                                            <a href="{{ route('documents.show', $approval->document) }}">
                                                {{ $approval->document->subject }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $approval->action === 'approved' ? 'success' : ($approval->action === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ $approval->getActionLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">Level {{ $approval->approval_level }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : 'info' }}">
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $approval->action_date->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No recent approvals</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .border-left-primary {
        border-left: 0.25rem solid #007bff !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #ffc107 !important;
    }
    .border-left-danger {
        border-left: 0.25rem solid #dc3545 !important;
    }
</style>
@endpush
@endsection
