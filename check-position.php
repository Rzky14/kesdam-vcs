<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking User Position Field ===\n\n";

$users = App\Models\User::all();

foreach ($users as $user) {
    echo "User: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Position: " . ($user->position ?? 'NULL/EMPTY') . "\n";
    echo "---\n";
}

echo "\n=== Checking if position column exists ===\n";
$columns = DB::select("SHOW COLUMNS FROM users");
foreach ($columns as $column) {
    if ($column->Field === 'position') {
        echo "✅ Column 'position' exists\n";
        echo "Type: {$column->Type}\n";
        echo "Null: {$column->Null}\n";
        echo "Default: {$column->Default}\n";
        break;
    }
}
