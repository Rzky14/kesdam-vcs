<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SettingService
 * 
 * Service untuk mengelola pengaturan sistem.
 */
class SettingService
{
    /**
     * Get all settings grouped by type.
     */
    public function getAllSettings(?int $userId = null): array
    {
        $query = Setting::active();
        
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereNull('user_id');
            });
        } else {
            $query->whereNull('user_id');
        }
        
        $settings = $query->get()->groupBy('type');
        
        return [
            'notification' => $this->formatSettings($settings->get('notification', collect())),
            'system' => $this->formatSettings($settings->get('system', collect())),
            'user' => $this->formatSettings($settings->get('user', collect())),
        ];
    }

    /**
     * Format settings collection.
     */
    private function formatSettings($settings): array
    {
        $formatted = [];
        foreach ($settings as $setting) {
            $formatted[$setting->key] = [
                'value' => $setting->value,
                'description' => $setting->description,
                'is_active' => $setting->is_active,
            ];
        }
        return $formatted;
    }

    /**
     * Update notification settings for user.
     */
    public function updateNotificationSettings(int $userId, array $data): bool
    {
        try {
            DB::beginTransaction();

            // Notifikasi Surat Baru
            Setting::set(
                'notification.new_document',
                $data['new_document'] ?? false,
                'notification',
                $userId
            );

            // Pengingat Jadwal
            Setting::set(
                'notification.schedule_reminder',
                $data['schedule_reminder'] ?? false,
                'notification',
                $userId
            );

            // Notifikasi Persetujuan
            Setting::set(
                'notification.approval_status',
                $data['approval_status'] ?? false,
                'notification',
                $userId
            );

            DB::commit();
            
            Log::info('Notification settings updated', [
                'user_id' => $userId,
                'settings' => $data
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update notification settings', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update system settings (admin only).
     */
    public function updateSystemSettings(array $data): bool
    {
        try {
            DB::beginTransaction();

            // Backup Otomatis
            if (isset($data['auto_backup'])) {
                Setting::set('system.auto_backup', $data['auto_backup'], 'system');
                
                if ($data['auto_backup']) {
                    $this->enableAutoBackup();
                } else {
                    $this->disableAutoBackup();
                }
            }

            // Format Surat Otomatis
            if (isset($data['auto_document_number'])) {
                Setting::set('system.auto_document_number', $data['auto_document_number'], 'system');
            }

            // Integrasi Kepegawaian
            if (isset($data['personnel_integration'])) {
                Setting::set('system.personnel_integration', $data['personnel_integration'], 'system');
                
                if (!empty($data['personnel_api_url'])) {
                    Setting::set('system.personnel_api_url', $data['personnel_api_url'], 'system');
                }
                
                if (!empty($data['personnel_api_key'])) {
                    Setting::set('system.personnel_api_key', $data['personnel_api_key'], 'system');
                }
            }

            DB::commit();
            
            Log::info('System settings updated', ['settings' => $data]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Enable auto backup.
     */
    private function enableAutoBackup(): void
    {
        // Di production, ini akan mengaktifkan scheduled task
        // Untuk sekarang, kita log saja
        Log::info('Auto backup enabled');
    }

    /**
     * Disable auto backup.
     */
    private function disableAutoBackup(): void
    {
        Log::info('Auto backup disabled');
    }

    /**
     * Perform manual backup.
     */
    public function performBackup(): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $path = storage_path("app/backups/{$filename}");

            // Pastikan directory exists
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            // Get database config
            $dbHost = config('database.connections.mysql.host');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            // Mysqldump command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($path)
            );

            // Execute backup
            exec($command, $output, $returnVar);

            if ($returnVar === 0 && file_exists($path)) {
                $size = filesize($path);
                
                Log::info('Database backup created', [
                    'filename' => $filename,
                    'size' => $size,
                    'path' => $path
                ]);

                return [
                    'success' => true,
                    'filename' => $filename,
                    'size' => $this->formatBytes($size),
                    'path' => $path,
                    'timestamp' => Carbon::now()->toDateTimeString()
                ];
            } else {
                throw new \Exception('Backup file not created');
            }
        } catch (\Exception $e) {
            Log::error('Backup failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get backup history.
     */
    public function getBackupHistory(): array
    {
        try {
            $backupPath = storage_path('app/backups');
            
            if (!file_exists($backupPath)) {
                return [];
            }

            $files = scandir($backupPath, SCANDIR_SORT_DESCENDING);
            $backups = [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $fullPath = $backupPath . DIRECTORY_SEPARATOR . $file;
                
                if (is_file($fullPath)) {
                    $backups[] = [
                        'filename' => $file,
                        'size' => $this->formatBytes(filesize($fullPath)),
                        'created_at' => Carbon::createFromTimestamp(filemtime($fullPath))->format('d-m-Y H:i:s'),
                        'path' => $fullPath
                    ];
                }
            }

            return $backups;
        } catch (\Exception $e) {
            Log::error('Failed to get backup history', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate document number.
     */
    public function generateDocumentNumber(string $type, string $classification): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Get last document number for this type and month
        $lastNumber = DB::table('documents')
            ->where('type', $type)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
        
        $number = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        
        // Format: 001/IN/XII/2025 (untuk surat masuk)
        $typeCode = $type === 'incoming' ? 'IN' : 'OUT';
        $monthRoman = $this->toRoman((int) $month);
        
        return "{$number}/{$typeCode}/{$monthRoman}/{$year}";
    }

    /**
     * Convert number to Roman numeral.
     */
    private function toRoman(int $number): string
    {
        $map = [
            12 => 'XII', 11 => 'XI', 10 => 'X', 9 => 'IX',
            8 => 'VIII', 7 => 'VII', 6 => 'VI', 5 => 'V',
            4 => 'IV', 3 => 'III', 2 => 'II', 1 => 'I'
        ];
        
        return $map[$number] ?? 'I';
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Initialize default settings.
     */
    public function initializeDefaultSettings(): void
    {
        $defaults = [
            // System Settings
            ['key' => 'system.auto_backup', 'value' => true, 'type' => 'system', 'group' => 'backup', 'description' => 'Backup database otomatis harian'],
            ['key' => 'system.auto_document_number', 'value' => true, 'type' => 'system', 'group' => 'document', 'description' => 'Penomoran surat otomatis'],
            ['key' => 'system.personnel_integration', 'value' => false, 'type' => 'system', 'group' => 'integration', 'description' => 'Integrasi dengan sistem kepegawaian'],
            ['key' => 'system.personnel_api_url', 'value' => '', 'type' => 'system', 'group' => 'integration', 'description' => 'URL API kepegawaian'],
            ['key' => 'system.personnel_api_key', 'value' => '', 'type' => 'system', 'group' => 'integration', 'description' => 'API Key kepegawaian'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        Log::info('Default settings initialized');
    }
}



