<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Display notification center view
     */
    public function index(): View
    {
        $user = auth()->user();
        $notifications = $this->notificationService->getNotifications($user, 50);
        $unreadCount = $this->notificationService->getUnreadNotificationCount($user);
        $stats = $this->notificationService->getStatistics($user);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'stats' => $stats,
        ]);
    }

    /**
     * Get unread notifications (API endpoint)
     */
    public function getUnread(): JsonResponse
    {
        $user = auth()->user();
        $notifications = $this->notificationService->getUnreadNotifications($user, 10);
        $unreadCount = $this->notificationService->getUnreadNotificationCount($user);

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'icon' => $n->icon,
                'action_url' => $n->action_url,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Get all notifications (API endpoint)
     */
    public function getAll(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);
        $notifications = $this->notificationService->getNotifications($user, $limit);

        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'icon' => $n->icon,
                'action_url' => $n->action_url,
                'is_read' => $n->isRead(),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Get notifications by type (API endpoint)
     */
    public function getByType(Request $request, string $type): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 20);
        $notifications = $this->notificationService->getNotificationsByType($user, $type, $limit);

        return response()->json([
            'type' => $type,
            'count' => $notifications->count(),
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'icon' => $n->icon,
                'action_url' => $n->action_url,
                'is_read' => $n->isRead(),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai sudah dibaca.',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sebagai sudah dibaca.',
        ]);
    }

    /**
     * Delete notification
     */
    public function delete(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi dihapus.',
        ]);
    }

    /**
     * Delete all notifications
     */
    public function deleteAll(): JsonResponse
    {
        $user = auth()->user();
        $this->notificationService->deleteAllNotifications($user);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi dihapus.',
        ]);
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->notificationService->getStatistics($user);

        return response()->json($stats);
    }

    /**
     * Get notification count for header badge
     */
    public function getCount(): JsonResponse
    {
        $user = auth()->user();
        $unreadCount = $this->notificationService->getUnreadNotificationCount($user);

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }
}
