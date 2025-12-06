<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * BackupService
 * 
 * Handles automated database and file backups for disaster recovery.
 */
class BackupService
{
    protected string $backupPath;
    protected int $retentionDays;

    public function __construct()
    {
        $this->backupPath = storage_path('backups');
        $this->retentionDays = config('backup.retention_days', 30);

        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a full backup (database + files).
     *
     * @return array
     */
    public function createFullBackup(): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $results = [];

        try {
            // Backup database
            $dbBackup = $this->backupDatabase($timestamp);
            $results['database'] = $dbBackup;

            // Backup files
            $filesBackup = $this->backupFiles($timestamp);
            $results['files'] = $filesBackup;

            // Clean old backups
            $this->cleanOldBackups();

            $results['status'] = 'success';
            $results['timestamp'] = $timestamp;

            Log::info('Full backup completed successfully', $results);

            return $results;
        } catch (\Exception $e) {
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'timestamp' => $timestamp
            ];
        }
    }

    /**
     * Backup database.
     *
     * @param string $timestamp
     * @return array
     */
    public function backupDatabase(string $timestamp): array
    {
        $filename = "database_{$timestamp}.sql";
        $filepath = "{$this->backupPath}/{$filename}";

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        // Use mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%d %s > %s 2>&1',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            $port,
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('Database backup failed: ' . implode("\n", $output));
        }

        // Compress the SQL file
        $this->compressFile($filepath);

        return [
            'filename' => $filename . '.gz',
            'path' => $filepath . '.gz',
            'size' => filesize($filepath . '.gz'),
            'created_at' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Backup important files and directories.
     *
     * @param string $timestamp
     * @return array
     */
    public function backupFiles(string $timestamp): array
    {
        $filename = "files_{$timestamp}.tar.gz";
        $filepath = "{$this->backupPath}/{$filename}";

        $directories = [
            storage_path('app/private'),
            storage_path('app/public'),
        ];

        // Create tar.gz archive
        $command = sprintf(
            'tar -czf %s %s 2>&1',
            escapeshellarg($filepath),
            implode(' ', array_map('escapeshellarg', $directories))
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('Files backup failed: ' . implode("\n", $output));
        }

        return [
            'filename' => $filename,
            'path' => $filepath,
            'size' => filesize($filepath),
            'created_at' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Compress a file using gzip.
     *
     * @param string $filepath
     * @return void
     */
    protected function compressFile(string $filepath): void
    {
        $command = sprintf('gzip %s', escapeshellarg($filepath));
        exec($command);
    }

    /**
     * Clean old backups based on retention policy.
     *
     * @return int Number of deleted backups
     */
    public function cleanOldBackups(): int
    {
        $cutoffDate = Carbon::now()->subDays($this->retentionDays);
        $deleted = 0;

        $files = glob("{$this->backupPath}/*");

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = Carbon::createFromTimestamp(filemtime($file));
                
                if ($fileTime->lt($cutoffDate)) {
                    unlink($file);
                    $deleted++;
                    Log::info('Deleted old backup', ['file' => basename($file)]);
                }
            }
        }

        return $deleted;
    }

    /**
     * List all available backups.
     *
     * @return array
     */
    public function listBackups(): array
    {
        $files = glob("{$this->backupPath}/*");
        $backups = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'created_at' => Carbon::createFromTimestamp(filemtime($file))->toDateTimeString()
                ];
            }
        }

        // Sort by creation time, newest first
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Restore database from backup.
     *
     * @param string $backupFile
     * @return bool
     */
    public function restoreDatabase(string $backupFile): bool
    {
        $filepath = "{$this->backupPath}/{$backupFile}";

        if (!file_exists($filepath)) {
            throw new \Exception("Backup file not found: {$backupFile}");
        }

        // Decompress if needed
        if (str_ends_with($filepath, '.gz')) {
            $command = sprintf('gunzip -c %s > %s', 
                escapeshellarg($filepath),
                escapeshellarg(str_replace('.gz', '', $filepath))
            );
            exec($command);
            $filepath = str_replace('.gz', '', $filepath);
        }

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%d %s < %s 2>&1',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            $port,
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('Database restore failed: ' . implode("\n", $output));
        }

        Log::info('Database restored successfully', ['backup' => $backupFile]);

        return true;
    }
}
