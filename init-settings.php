<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Initializing default settings...\n\n";

$defaults = [
    // System Settings
    [
        'key' => 'system.auto_backup',
        'value' => json_encode(true),
        'type' => 'system',
        'group' => 'backup',
        'description' => 'Backup database otomatis harian',
        'is_active' => true,
    ],
    [
        'key' => 'system.auto_document_number',
        'value' => json_encode(true),
        'type' => 'system',
        'group' => 'document',
        'description' => 'Penomoran surat otomatis',
        'is_active' => true,
    ],
    [
        'key' => 'system.personnel_integration',
        'value' => json_encode(false),
        'type' => 'system',
        'group' => 'integration',
        'description' => 'Integrasi dengan sistem kepegawaian',
        'is_active' => true,
    ],
    [
        'key' => 'system.personnel_api_url',
        'value' => json_encode(''),
        'type' => 'system',
        'group' => 'integration',
        'description' => 'URL API kepegawaian',
        'is_active' => true,
    ],
    [
        'key' => 'system.personnel_api_key',
        'value' => json_encode(''),
        'type' => 'system',
        'group' => 'integration',
        'description' => 'API Key kepegawaian',
        'is_active' => true,
    ],
];

foreach ($defaults as $setting) {
    $existing = DB::table('settings')
        ->where('key', $setting['key'])
        ->first();
    
    if (!$existing) {
        DB::table('settings')->insert(array_merge($setting, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        echo "✓ Created setting: {$setting['key']}\n";
    } else {
        echo "- Setting already exists: {$setting['key']}\n";
    }
}

echo "\n✓ Default settings initialized successfully!\n";
