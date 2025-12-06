<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Update notification preferences
     */
    public function updateNotifications(Request $request)
    {
        $validated = $request->validate([
            'schedule_reminder' => 'boolean',
            'approval_request' => 'boolean',
            'document_status' => 'boolean',
        ]);

        // Update user notification preferences
        auth()->user()->notificationPreferences()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'schedule_reminder' => $validated['schedule_reminder'] ?? false,
                'approval_request' => $validated['approval_request'] ?? false,
                'document_status' => $validated['document_status'] ?? false,
            ]
        );

        return redirect()->route('settings.index')->with('success', 'Preferensi notifikasi berhasil diperbarui');
    }

    /**
     * Update system settings (admin only)
     */
    public function updateSystem(Request $request)
    {
        // TODO: Implement system settings (backup, document format, etc.)
        return redirect()->route('settings.index')->with('success', 'Pengaturan sistem berhasil diperbarui');
    }
}
