@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bell"></i> Notifications</h2>
                <div>
                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check-all"></i> Mark All as Read
                        </button>
                    </form>
                    <form action="{{ route('notifications.delete-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all notifications?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete All
                        </button>
                    </form>
                    <a href="{{ route('notifications.preferences') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear"></i> Preferences
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ !request('filter') || request('filter') == 'all' ? 'active' : '' }}" 
                               href="{{ route('notifications.index', ['filter' => 'all']) }}">
                                All ({{ $notifications->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request('filter') == 'unread' ? 'active' : '' }}" 
                               href="{{ route('notifications.index', ['filter' => 'unread']) }}">
                                Unread ({{ $unreadCount }})
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    @if($notifications->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash" style="font-size: 3rem;"></i>
                            <p class="mt-3">No notifications found</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                @php
                                    $data = $notification->data;
                                    $isUnread = is_null($notification->read_at);
                                @endphp
                                <a href="{{ route('notifications.show', $notification->id) }}" 
                                   class="list-group-item list-group-item-action {{ $isUnread ? 'bg-light' : '' }}">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                @if($isUnread)
                                                    <span class="badge bg-primary me-2">New</span>
                                                @endif
                                                
                                                @if($data['type'] === 'schedule_reminder')
                                                    <i class="bi bi-calendar-event text-info me-2"></i>
                                                @elseif($data['type'] === 'approval_request')
                                                    <i class="bi bi-file-earmark-check text-warning me-2"></i>
                                                @elseif($data['type'] === 'document_approved')
                                                    <i class="bi bi-check-circle text-success me-2"></i>
                                                @elseif($data['type'] === 'document_rejected')
                                                    <i class="bi bi-x-circle text-danger me-2"></i>
                                                @elseif($data['type'] === 'correction_requested')
                                                    <i class="bi bi-pencil-square text-warning me-2"></i>
                                                @else
                                                    <i class="bi bi-info-circle text-primary me-2"></i>
                                                @endif
                                                
                                                <h6 class="mb-1 {{ $isUnread ? 'fw-bold' : '' }}">
                                                    {{ $data['message'] ?? 'Notification' }}
                                                </h6>
                                            </div>
                                            <small class="text-muted">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <form action="{{ route('notifications.destroy', $notification->id) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Delete this notification?')"
                                                  onclick="event.stopPropagation()">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

