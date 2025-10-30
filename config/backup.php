<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automated backup system.
    |
    */

    // Number of days to keep backups
    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    // Backup schedule (for Laravel scheduler)
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', true),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'), // daily, weekly, hourly
        'time' => env('BACKUP_TIME', '02:00'), // Time for daily backups (24h format)
    ],

    // Directories to backup
    'directories' => [
        storage_path('app/private'),
        storage_path('app/public'),
    ],

    // Backup notifications
    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', true),
        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL'),
        ],
    ],
];
