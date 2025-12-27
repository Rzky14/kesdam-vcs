@extends('layouts.app')

@section('title', 'Notification Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bell"></i> Notification Details</h2>
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Notifications
                </a>
            </div>

            @php
                $data = $notification->data;
            @endphp

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        @if($data['type'] === 'schedule_reminder')
                            <i class="bi bi-calendar-event text-info"></i> Schedule Reminder
                        @elseif($data['type'] === 'approval_request')
                            <i class="bi bi-file-earmark-check text-warning"></i> Approval Request
                        @elseif($data['type'] === 'document_approved')
                            <i class="bi bi-check-circle text-success"></i> Document Approved
                        @elseif($data['type'] === 'document_rejected')
                            <i class="bi bi-x-circle text-danger"></i> Document Rejected
                        @elseif($data['type'] === 'correction_requested')
                            <i class="bi bi-pencil-square text-warning"></i> Correction Requested
                        @else
                            <i class="bi bi-info-circle text-primary"></i> Notification
                        @endif
                    </div>
                    <small class="text-muted">{{ $notification->created_at->format('d M Y H:i') }}</small>
                </div>
                <div class="card-body">
                    <h4>{{ $data['message'] ?? 'Notification' }}</h4>
                    <hr>

                    @if($data['type'] === 'schedule_reminder')
                        <dl class="row">
                            <dt class="col-sm-3">Schedule Title:</dt>
                            <dd class="col-sm-9">{{ $data['schedule_title'] }}</dd>

                            <dt class="col-sm-3">Type:</dt>
                            <dd class="col-sm-9">{{ $data['schedule_type_label'] }}</dd>

                            <dt class="col-sm-3">Start Date:</dt>
                            <dd class="col-sm-9">{{ \Carbon\Carbon::parse($data['start_date'])->format('d M Y H:i') }}</dd>

                            <dt class="col-sm-3">Location:</dt>
                            <dd class="col-sm-9">{{ $data['location'] ?? 'N/A' }}</dd>

                            <dt class="col-sm-3">Hours Until Start:</dt>
                            <dd class="col-sm-9">{{ $data['hours_until_start'] }} hours</dd>
                        </dl>

                    @elseif($data['type'] === 'approval_request')
                        <dl class="row">
                            <dt class="col-sm-3">Document Number:</dt>
                            <dd class="col-sm-9">{{ $data['document_number'] }}</dd>

                            <dt class="col-sm-3">Subject:</dt>
                            <dd class="col-sm-9">{{ $data['document_subject'] }}</dd>

                            <dt class="col-sm-3">Type:</dt>
                            <dd class="col-sm-9">{{ $data['document_type_label'] }}</dd>

                            <dt class="col-sm-3">Classification:</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-{{ $data['classification'] === 'rahasia' ? 'danger' : ($data['classification'] === 'telegram' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($data['classification']) }}
                                </span>
                            </dd>

                            <dt class="col-sm-3">Priority:</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-{{ $data['priority'] === 'urgent' ? 'danger' : ($data['priority'] === 'high' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($data['priority']) }}
                                </span>
                            </dd>

                            <dt class="col-sm-3">Submitted By:</dt>
                            <dd class="col-sm-9">{{ $data['submitter_name'] }}</dd>

                            <dt class="col-sm-3">Approval Level:</dt>
                            <dd class="col-sm-9">Level {{ $data['current_level'] }}</dd>
                        </dl>

                    @elseif($data['type'] === 'document_approved')
                        <dl class="row">
                            <dt class="col-sm-3">Document Number:</dt>
                            <dd class="col-sm-9">{{ $data['document_number'] }}</dd>

                            <dt class="col-sm-3">Subject:</dt>
                            <dd class="col-sm-9">{{ $data['document_subject'] }}</dd>

                            <dt class="col-sm-3">Approved By:</dt>
                            <dd class="col-sm-9">{{ $data['approver_name'] }} ({{ $data['approver_rank'] }})</dd>

                            @if(isset($data['notes']) && $data['notes'])
                                <dt class="col-sm-3">Notes:</dt>
                                <dd class="col-sm-9">{{ $data['notes'] }}</dd>
                            @endif
                        </dl>

                    @elseif($data['type'] === 'document_rejected')
                        <dl class="row">
                            <dt class="col-sm-3">Document Number:</dt>
                            <dd class="col-sm-9">{{ $data['document_number'] }}</dd>

                            <dt class="col-sm-3">Subject:</dt>
                            <dd class="col-sm-9">{{ $data['document_subject'] }}</dd>

                            <dt class="col-sm-3">Rejected By:</dt>
                            <dd class="col-sm-9">{{ $data['rejector_name'] }} ({{ $data['rejector_rank'] }})</dd>

                            <dt class="col-sm-3">Reason:</dt>
                            <dd class="col-sm-9">
                                <div class="alert alert-danger">{{ $data['reason'] }}</div>
                            </dd>
                        </dl>

                    @elseif($data['type'] === 'correction_requested')
                        <dl class="row">
                            <dt class="col-sm-3">Document Number:</dt>
                            <dd class="col-sm-9">{{ $data['document_number'] }}</dd>

                            <dt class="col-sm-3">Subject:</dt>
                            <dd class="col-sm-9">{{ $data['document_subject'] }}</dd>

                            <dt class="col-sm-3">Requested By:</dt>
                            <dd class="col-sm-9">{{ $data['requester_name'] }} ({{ $data['requester_rank'] }})</dd>

                            <dt class="col-sm-3">Correction Notes:</dt>
                            <dd class="col-sm-9">
                                <div class="alert alert-warning">{{ $data['notes'] }}</div>
                            </dd>

                            <dt class="col-sm-3">Deadline:</dt>
                            <dd class="col-sm-9">{{ \Carbon\Carbon::parse($data['deadline'])->format('d M Y H:i') }}</dd>
                        </dl>
                    @endif

                    @if(isset($data['url']))
                        <div class="mt-4">
                            <a href="{{ $data['url'] }}" class="btn btn-primary">
                                <i class="bi bi-eye"></i> View Related Item
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer text-muted">
                    @if($notification->read_at)
                        <small>Read at: {{ $notification->read_at->format('d M Y H:i') }}</small>
                    @else
                        <span class="badge bg-primary">Unread</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

