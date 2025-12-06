@extends('layouts.app')

@section('title', 'Notification Preferences')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-gear"></i> Notification Preferences</h2>
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Notifications
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Configure Your Notification Settings</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Choose which types of notifications you want to receive and through which channels.
                    </p>

                    <form action="{{ route('notifications.preferences.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Notification Type</th>
                                        <th class="text-center">In-App</th>
                                        <th class="text-center">Email</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preferences as $type => $preference)
                                        <tr>
                                            <td>
                                                <strong>{{ \App\Models\NotificationPreference::TYPES[$type] }}</strong>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="preferences[{{ $type }}][in_app_enabled]"
                                                           id="in_app_{{ $type }}"
                                                           value="1"
                                                           {{ $preference->in_app_enabled ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="preferences[{{ $type }}][email_enabled]"
                                                           id="email_{{ $type }}"
                                                           value="1"
                                                           {{ $preference->email_enabled ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    @if($type === 'schedule_reminder')
                                                        Get reminded about upcoming schedules
                                                    @elseif($type === 'approval_request')
                                                        When documents need your approval
                                                    @elseif($type === 'document_approved')
                                                        When your documents are approved
                                                    @elseif($type === 'document_rejected')
                                                        When your documents are rejected
                                                    @elseif($type === 'correction_requested')
                                                        When corrections are needed for your documents
                                                    @elseif($type === 'document_status_changed')
                                                        When document status changes
                                                    @endif
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> 
                            <ul class="mb-0 mt-2">
                                <li><strong>In-App:</strong> Notifications appear in the application bell icon</li>
                                <li><strong>Email:</strong> Notifications are sent to your registered email address</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('notifications.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
