<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== Fix Admin Permissions ===\n\n";

$user = App\Models\User::where('name', 'Admin System')->first();

if (!$user) {
    echo "✗ Admin user not found\n";
    exit(1);
}

echo "User: {$user->name} (ID: {$user->id})\n";

// Check current roles
$currentRoles = $user->roles()->pluck('name')->toArray();
echo "Current roles: " . implode(', ', $currentRoles) . "\n\n";

// Get or create Admin Sistem role
$adminRole = App\Models\Role::firstOrCreate(
    ['name' => 'Admin Sistem'],
    [
        'display_name' => 'Admin Sistem',
        'description' => 'Administrator Sistem dengan akses penuh'
    ]
);

// Assign role
if (!$user->hasRole('Admin Sistem')) {
    $user->roles()->attach($adminRole->id);
    echo "✓ Assigned role: Admin Sistem\n";
} else {
    echo "✓ Already has role: Admin Sistem\n";
}

// Get all document permissions
$permissions = App\Models\Permission::where('name', 'like', '%document%')->get();

if ($permissions->isEmpty()) {
    echo "\n⚠ No document permissions found. Creating...\n";
    
    $perms = [
        ['name' => 'view_documents', 'description' => 'View documents'],
        ['name' => 'create_documents', 'description' => 'Create documents'],
        ['name' => 'update_documents', 'description' => 'Update documents'],
        ['name' => 'delete_documents', 'description' => 'Delete documents'],
        ['name' => 'approve_documents', 'description' => 'Approve documents'],
        ['name' => 'view_classified_documents', 'description' => 'View classified documents'],
    ];
    
    foreach ($perms as $p) {
        App\Models\Permission::firstOrCreate(['name' => $p['name']], $p);
        echo "  Created: {$p['name']}\n";
    }
    
    $permissions = App\Models\Permission::where('name', 'like', '%document%')->get();
}

echo "\nSyncing permissions to Admin Sistem role:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm->name}\n";
}

// Sync permissions to the role, not the user
$adminRole->permissions()->sync($permissions->pluck('id')->toArray());

echo "\n✓ Permissions synced!\n\n";

// Test again
$user = $user->fresh();
echo "Testing permissions:\n";
$testPerms = [
    'view_documents',
    'create_documents',
    'update_documents',
    'delete_documents',
    'approve_documents'
];

foreach ($testPerms as $perm) {
    $has = $user->hasPermission($perm);
    echo "  - {$perm}: " . ($has ? '✓' : '✗') . "\n";
}

echo "\nTesting role:\n";
echo "  - hasRole('Admin Sistem'): " . ($user->hasRole('Admin Sistem') ? '✓' : '✗') . "\n";
echo "  - isAdmin(): " . ($user->isAdmin() ? '✓' : '✗') . "\n";

echo "\n✓ Fix completed!\n";
