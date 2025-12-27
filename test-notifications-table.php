<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking notifications table structure...\n\n";

if (!Schema::hasTable('notifications')) {
    echo "❌ Table 'notifications' does not exist!\n";
    echo "Running migration...\n";
    
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_12_06_100000_create_notifications_table.php'
    ]);
    
    echo Artisan::output();
} else {
    echo "✓ Table 'notifications' exists\n\n";
    
    $columns = DB::select("DESCRIBE notifications");
    
    echo "Columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
}

echo "\n\nChecking for test data...\n";

$count = DB::table('notifications')->count();
echo "Total notifications: $count\n";

if ($count > 0) {
    echo "\nSample data:\n";
    $samples = DB::table('notifications')->limit(3)->get();
    foreach ($samples as $sample) {
        echo "  ID: {$sample->id}\n";
        echo "  Type: {$sample->type}\n";
        echo "  Notifiable: {$sample->notifiable_type} #{$sample->notifiable_id}\n";
        echo "  ---\n";
    }
}
