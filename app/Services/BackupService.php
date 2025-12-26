<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * LayananBackup (BackupService)
 * 
 * Menangani backup otomatis database dan file untuk pemulihan bencana.
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
     * Buat backup lengkap (database + file).
     *
     * @return array Hasil backup
     */
    public function createFullBackup(): array
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $results = [];

        try {
            // Backup database
            $dbBackup = $this->backupDatabase($timestamp);
            $results['database'] = $dbBackup;

            // Backup file
            $filesBackup = $this->backupFiles($timestamp);
            $results['files'] = $filesBackup;

            // Hapus backup lama
            $this->cleanOldBackups();

            $results['status'] = 'success';
            $results['timestamp'] = $timestamp;

            Log::info('Backup lengkap berhasil diselesaikan', $results);

            return $results;
        } catch (\Exception $e) {
            Log::error('Backup gagal', [
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
     * @param string $timestamp Timestamp untuk nama file
     * @return array Informasi backup database
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

        // Gunakan perintah mysqldump
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
            throw new \Exception('Backup database gagal: ' . implode("\n", $output));
        }

        // Kompres file SQL
        $this->compressFile($filepath);

        return [
            'filename' => $filename . '.gz',
            'path' => $filepath . '.gz',
            'size' => filesize($filepath . '.gz'),
            'created_at' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Backup file dan direktori penting.
     *
     * @param string $timestamp Timestamp untuk nama file
     * @return array Informasi backup file
     */
    public function backupFiles(string $timestamp): array
    {
        $filename = "files_{$timestamp}.tar.gz";
        $filepath = "{$this->backupPath}/{$filename}";

        $directories = [
            storage_path('app/private'),
            storage_path('app/public'),
        ];

        // Buat arsip tar.gz
        $command = sprintf(
            'tar -czf %s %s 2>&1',
            escapeshellarg($filepath),
            implode(' ', array_map('escapeshellarg', $directories))
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('Backup file gagal: ' . implode("\n", $output));
        }

        return [
            'filename' => $filename,
            'path' => $filepath,
            'size' => filesize($filepath),
            'created_at' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Kompres file menggunakan gzip.
     *
     * @param string $filepath Path file yang akan dikompres
     * @return void
     */
    protected function compressFile(string $filepath): void
    {
        $command = sprintf('gzip %s', escapeshellarg($filepath));
        exec($command);
    }

    /**
     * Hapus backup lama berdasarkan kebijakan retensi.
     *
     * @return int Jumlah backup yang dihapus
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
                    Log::info('Backup lama dihapus', ['file' => basename($file)]);
                }
            }
        }

        return $deleted;
    }

    /**
     * Daftar semua backup yang tersedia.
     *
     * @return array Daftar backup
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

        // Urutkan berdasarkan waktu pembuatan, terbaru dulu
        usort($backups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Pulihkan database dari backup.
     *
     * @param string $backupFile Nama file backup
     * @return bool Status berhasil atau gagal
     */
    public function restoreDatabase(string $backupFile): bool
    {
        $filepath = "{$this->backupPath}/{$backupFile}";

        if (!file_exists($filepath)) {
            throw new \Exception("File backup tidak ditemukan: {$backupFile}");
        }

        // Dekompresi jika diperlukan
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
            throw new \Exception('Pemulihan database gagal: ' . implode("\n", $output));
        }

        Log::info('Database berhasil dipulihkan', ['backup' => $backupFile]);

        return true;
    }
}
