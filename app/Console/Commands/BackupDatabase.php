<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--type=full : Type of backup (full, database, files)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the database and/or files';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService)
    {
        $type = $this->option('type');

        $this->info("Starting {$type} backup...");

        try {
            switch ($type) {
                case 'database':
                    $timestamp = now()->format('Y-m-d_His');
                    $result = $backupService->backupDatabase($timestamp);
                    $this->info("Database backup completed: {$result['filename']}");
                    break;

                case 'files':
                    $timestamp = now()->format('Y-m-d_His');
                    $result = $backupService->backupFiles($timestamp);
                    $this->info("Files backup completed: {$result['filename']}");
                    break;

                case 'full':
                default:
                    $result = $backupService->createFullBackup();
                    
                    if ($result['status'] === 'success') {
                        $this->info('Full backup completed successfully!');
                        $this->table(
                            ['Type', 'Filename', 'Size'],
                            [
                                ['Database', $result['database']['filename'], $this->formatBytes($result['database']['size'])],
                                ['Files', $result['files']['filename'], $this->formatBytes($result['files']['size'])],
                            ]
                        );
                    } else {
                        $this->error('Backup failed: ' . ($result['error'] ?? 'Unknown error'));
                        return Command::FAILURE;
                    }
                    break;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Format bytes to human readable size.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}



