<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService)
    {
    }

    /**
     * Tampilkan halaman pengaturan.
     */
    public function index(): View
    {
        $userId = Auth::id();
        $settings = $this->settingService->getAllSettings($userId);
        $backupHistory = $this->settingService->getBackupHistory();
        
        return view('settings.index', compact('settings', 'backupHistory'));
    }

    /**
     * Perbarui preferensi notifikasi.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'new_document' => 'nullable|boolean',
            'schedule_reminder' => 'nullable|boolean',
            'approval_status' => 'nullable|boolean',
        ]);

        // Convert checkbox values (on/off) to boolean
        $data = [
            'new_document' => isset($validated['new_document']) ? (bool) $validated['new_document'] : false,
            'schedule_reminder' => isset($validated['schedule_reminder']) ? (bool) $validated['schedule_reminder'] : false,
            'approval_status' => isset($validated['approval_status']) ? (bool) $validated['approval_status'] : false,
        ];

        $success = $this->settingService->updateNotificationSettings(Auth::id(), $data);

        if ($success) {
            return redirect()->route('settings.index')
                ->with('success', '✓ Preferensi notifikasi berhasil diperbarui');
        }

        return redirect()->route('settings.index')
            ->with('error', '✗ Gagal memperbarui preferensi notifikasi');
    }

    /**
     * Perbarui pengaturan sistem (khusus admin).
     */
    public function updateSystem(Request $request): RedirectResponse
    {
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('settings.index')
                ->with('error', '✗ Anda tidak memiliki akses untuk mengubah pengaturan sistem');
        }

        $validated = $request->validate([
            'auto_backup' => 'nullable|boolean',
            'auto_document_number' => 'nullable|boolean',
            'personnel_integration' => 'nullable|boolean',
            'personnel_api_url' => 'nullable|url',
            'personnel_api_key' => 'nullable|string|max:255',
        ]);

        // Convert checkbox values to boolean
        $data = [
            'auto_backup' => isset($validated['auto_backup']) ? (bool) $validated['auto_backup'] : false,
            'auto_document_number' => isset($validated['auto_document_number']) ? (bool) $validated['auto_document_number'] : false,
            'personnel_integration' => isset($validated['personnel_integration']) ? (bool) $validated['personnel_integration'] : false,
            'personnel_api_url' => $validated['personnel_api_url'] ?? '',
            'personnel_api_key' => $validated['personnel_api_key'] ?? '',
        ];

        $success = $this->settingService->updateSystemSettings($data);

        if ($success) {
            return redirect()->route('settings.index')
                ->with('success', '✓ Pengaturan sistem berhasil diperbarui');
        }

        return redirect()->route('settings.index')
            ->with('error', '✗ Gagal memperbarui pengaturan sistem');
    }

    /**
     * Perform manual backup.
     */
    public function backup(): JsonResponse
    {
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan backup'
            ], 403);
        }

        $result = $this->settingService->performBackup();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Backup berhasil dibuat',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal membuat backup: ' . ($result['error'] ?? 'Unknown error')
        ], 500);
    }

    /**
     * Show integration configuration modal.
     */
    public function showIntegrationConfig(): View
    {
        $settings = $this->settingService->getAllSettings();
        
        return view('settings.integration-config', compact('settings'));
    }
}



