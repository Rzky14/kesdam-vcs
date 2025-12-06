@extends('layouts.app')

@section('title', 'Pusat Notifikasi')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifikasi</h5>
                    @if($unreadCount > 0)
                        <button class="btn btn-sm btn-light" id="markAllRead">Tandai Semua Sudah Dibaca</button>
                    @endif
                </div>
                <div class="card-body">
                    @if($notifications->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">Tidak ada notifikasi</p>
                        </div>
                    @else
                        <div class="notification-list">
                            @foreach($notifications as $notification)
                                <div class="notification-item {{ !$notification->isRead() ? 'unread' : '' }} border-bottom py-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-{{ $notification->icon }}"></i>
                                                <h6 class="mb-0">{{ $notification->title }}</h6>
                                                @if(!$notification->isRead())
                                                    <span class="badge bg-primary">Baru</span>
                                                @endif
                                            </div>
                                            <p class="text-muted small mb-2">{{ $notification->message }}</p>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="ms-3">
                                            @if(!$notification->isRead())
                                                <button class="btn btn-sm btn-outline-primary mark-read" 
                                                        data-notification-id="{{ $notification->id }}">
                                                    Baca
                                                </button>
                                            @endif
                                            @if($notification->action_url)
                                                <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-info ms-2">
                                                    Lihat
                                                </a>
                                            @endif
                                            <button class="btn btn-sm btn-outline-danger delete-notification" 
                                                    data-notification-id="{{ $notification->id }}" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Statistics Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">Statistik</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="text-primary">{{ $stats['unread'] }}</h4>
                            <small class="text-muted">Belum Dibaca</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="text-info">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                    <hr>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Persetujuan:</span>
                            <strong>{{ $stats['approval_requests'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Status Dokumen:</span>
                            <strong>{{ $stats['document_status_changes'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pengingat Jadwal:</span>
                            <strong>{{ $stats['schedule_reminders'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Sistem:</span>
                            <strong>{{ $stats['system'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preferences Link -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Pengaturan</h6>
                </div>
                <div class="card-body text-center">
                    <a href="{{ route('notifications.preferences') }}" class="btn btn-secondary btn-sm w-100">
                        <i class="bi bi-gear"></i> Preferensi Notifikasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .notification-item {
        transition: background-color 0.3s ease;
    }

    .notification-item.unread {
        background-color: #f0f8ff;
    }

    .notification-item:hover {
        background-color: #f9f9f9;
    }

    .notification-list {
        max-height: 600px;
        overflow-y: auto;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mark as read
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;
                markNotificationAsRead(notificationId);
            });
        });

        // Delete notification
        document.querySelectorAll('.delete-notification').forEach(btn => {
            btn.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;
                deleteNotification(notificationId);
            });
        });

        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', markAllAsRead);
        }
    });

    function markNotificationAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function deleteNotification(notificationId) {
        if (confirm('Hapus notifikasi ini?')) {
            fetch(`/notifications/${notificationId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }

    function markAllAsRead() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
</script>
@endsection
