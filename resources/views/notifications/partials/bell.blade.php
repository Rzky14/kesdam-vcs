<!-- Notification Bell -->
<div class="dropdown">
    <a class="nav-link position-relative" href="#" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
              id="notification-badge" 
              style="display: none; font-size: 0.6rem;">
            0
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown shadow" aria-labelledby="notificationDropdown" style="min-width: 350px; max-height: 500px; overflow-y: auto;">
        <div class="dropdown-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Notifications</span>
            <a href="{{ route('notifications.mark-all-read') }}" 
               class="btn btn-sm btn-link text-decoration-none" 
               onclick="event.preventDefault(); markAllAsRead();">
                Mark all as read
            </a>
        </div>
        <div class="dropdown-divider"></div>
        
        <div id="notification-list" class="notification-list">
            <div class="text-center py-4 text-muted">
                <i class="bi bi-bell-slash fs-1"></i>
                <p class="mt-2 mb-0">No new notifications</p>
            </div>
        </div>
        
        <div class="dropdown-divider"></div>
        <div class="dropdown-footer text-center">
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link text-decoration-none">
                View all notifications
            </a>
        </div>
    </div>
</div>

<style>
    .notification-dropdown .dropdown-item {
        white-space: normal;
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .notification-dropdown .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .notification-dropdown .dropdown-item.unread {
        background-color: #e8f4f8;
    }
    
    .notification-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
</style>

<script>
    let notificationInterval = null;

    // Fetch notifications on page load
    document.addEventListener('DOMContentLoaded', function() {
        fetchNotifications();
        
        // Poll for new notifications every 30 seconds
        notificationInterval = setInterval(fetchNotifications, 30000);
    });

    async function fetchNotifications() {
        try {
            const response = await fetch('{{ route('notifications.recent') }}?limit=5');
            const data = await response.json();
            
            updateNotificationBadge(data.total_unread);
            updateNotificationList(data.notifications);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    function updateNotificationList(notifications) {
        const listElement = document.getElementById('notification-list');
        
        if (notifications.length === 0) {
            listElement.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash fs-1"></i>
                    <p class="mt-2 mb-0">No new notifications</p>
                </div>
            `;
            return;
        }
        
        listElement.innerHTML = notifications.map(notification => {
            const data = notification.data;
            const isUnread = !notification.read_at;
            
            let icon = 'bi-info-circle text-primary';
            if (data.type === 'schedule_reminder') icon = 'bi-calendar-event text-info';
            else if (data.type === 'approval_request') icon = 'bi-file-earmark-check text-warning';
            else if (data.type === 'document_approved') icon = 'bi-check-circle text-success';
            else if (data.type === 'document_rejected') icon = 'bi-x-circle text-danger';
            else if (data.type === 'correction_requested') icon = 'bi-pencil-square text-warning';
            
            return `
                <a href="/notifications/${notification.id}" class="dropdown-item ${isUnread ? 'unread' : ''}">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon bg-light me-3">
                            <i class="bi ${icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-1">
                                ${isUnread ? '<span class="badge bg-primary badge-sm me-1">New</span>' : ''}
                                <strong class="d-block">${truncate(data.message || 'Notification', 60)}</strong>
                            </div>
                            <small class="text-muted">${formatDate(notification.created_at)}</small>
                        </div>
                    </div>
                </a>
            `;
        }).join('');
    }

    async function markAllAsRead() {
        try {
            const response = await fetch('{{ route('notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (response.ok) {
                fetchNotifications();
            }
        } catch (error) {
            console.error('Error marking notifications as read:', error);
        }
    }

    function truncate(str, length) {
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
        if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + ' days ago';
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
</script>
