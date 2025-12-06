<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "========================================\n";
echo "📋 CHECKING ALL USERS AND ROLES\n";
echo "========================================\n\n";

$users = User::with('roles')->get();

if ($users->isEmpty()) {
    echo "❌ No users found in database!\n";
    exit;
}

foreach ($users as $user) {
    $roles = $user->roles->pluck('name')->implode(', ');
    $rolesDisplay = $user->roles->pluck('display_name')->implode(', ');
    
    echo "👤 {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Roles: " . ($roles ?: 'NO ROLE') . "\n";
    echo "   Display: " . ($rolesDisplay ?: 'NO ROLE') . "\n";
    echo "\n";
}

echo "========================================\n";
echo "📊 AVAILABLE ROLES:\n";
echo "========================================\n\n";

$roles = Role::all();
foreach ($roles as $role) {
    echo "✅ {$role->display_name} ({$role->name})\n";
}

echo "\n";
