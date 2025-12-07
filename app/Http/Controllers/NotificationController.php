<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        // Middleware applied in routes
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $filter = $request->get('filter', 'all'); // all, unread, read
        $type = $request->get('type');

        if ($filter === 'unread') {
            $notifications = $this->notificationService->getUnreadNotifications($user);
        } else {
            $notifications = $this->notificationService->getAllNotifications($user);
        }

        if ($type) {
            $notifications = $this->notificationService->getNotificationsByType($user, $type);
        }

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter', 'type'));
    }

    /**
     * Display the specified notification.
     */
    public function show(string $id): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        // Mark as read if unread
        if (!$notification->read_at) {
            $this->notificationService->markAsRead($notification);
        }

        return view('notifications.show', compact('notification'));
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete the specified notification.
     */
    public function destroy(string $id): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        $this->notificationService->deleteNotification($notification);

        return redirect()->route('notifications.index')
            ->with('success', 'Notification deleted successfully');
    }

    /**
     * Get unread notifications count (for AJAX)
     */
    public function unreadCount(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Get recent notifications (for dropdown)
     */
    public function recent(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $limit = $request->get('limit', 5);
        
        $notifications = $this->notificationService->getUnreadNotifications($user, $limit);

        return response()->json([
            'notifications' => $notifications,
            'total_unread' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Display notification preferences page.
     */
    public function preferences(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Get or create default preferences
        $preferences = [];
        foreach (NotificationPreference::TYPES as $type => $label) {
            $preference = $user->notificationPreferences()
                ->where('notification_type', $type)
                ->first();

            if (!$preference) {
                $preference = new NotificationPreference([
                    'notification_type' => $type,
                    'in_app_enabled' => true,
                    'email_enabled' => false,
                ]);
            }

            $preferences[$type] = $preference;
        }

        return view('notifications.preferences', compact('preferences'));
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.in_app_enabled' => 'boolean',
            'preferences.*.email_enabled' => 'boolean',
        ]);

        foreach ($validated['preferences'] as $type => $settings) {
            $user->notificationPreferences()->updateOrCreate(
                ['notification_type' => $type],
                [
                    'in_app_enabled' => $settings['in_app_enabled'] ?? false,
                    'email_enabled' => $settings['email_enabled'] ?? false,
                ]
            );
        }

        return redirect()->route('notifications.preferences')
            ->with('success', 'Notification preferences updated successfully');
    }

    /**
     * Delete all notifications for the user.
     */
    public function deleteAll(): RedirectResponse
    {
        $user = Auth::user();
        $this->notificationService->deleteAllNotifications($user);

        return redirect()->route('notifications.index')
            ->with('success', 'All notifications deleted successfully');
    }
}
