<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationPreferenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the notification preferences form
     */
    public function show(): View
    {
        $user = auth()->user();
        $preferences = $user->getOrCreateNotificationPreferences();

        return view('notifications.preferences', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update the notification preferences
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $preferences = $user->getOrCreateNotificationPreferences();

        $data = [
            'approval_request_enabled' => $request->has('approval_request_enabled'),
            'approval_request_email' => $request->has('approval_request_email'),
            'document_status_enabled' => $request->has('document_status_enabled'),
            'document_status_email' => $request->has('document_status_email'),
            'schedule_reminder_enabled' => $request->has('schedule_reminder_enabled'),
            'schedule_reminder_email' => $request->has('schedule_reminder_email'),
            'system_notifications_enabled' => $request->has('system_notifications_enabled'),
            'schedule_reminder_timing' => $request->input('schedule_reminder_timing', '1-day'),
        ];

        $preferences->update($data);

        return redirect()->route('notifications.preferences')
            ->with('success', 'Preferensi notifikasi telah diperbarui.');
    }
}
