<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@kesdam.mil.id',
            'password' => Hash::make('password123'),
            'nrp' => '123456',
            'rank' => 'Mayor',
            'position' => 'Administrator Sistem',
            'unit' => 'KESDAM III/Siliwangi',
            'phone' => '081234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Attach admin role
        $adminRole = Role::where('name', 'admin_sistem')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id);
        }

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@kesdam.mil.id');
        $this->command->info('Password: password123');
    }
}
