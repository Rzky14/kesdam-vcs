<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'View Users', 'module' => 'users', 'description' => 'Can view user list'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'module' => 'users', 'description' => 'Can create new users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'module' => 'users', 'description' => 'Can edit user information'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'module' => 'users', 'description' => 'Can delete users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'module' => 'users', 'description' => 'Can assign and remove roles'],
            
            // Document Management
            ['name' => 'view_documents', 'display_name' => 'View Documents', 'module' => 'documents', 'description' => 'Can view documents'],
            ['name' => 'create_documents', 'display_name' => 'Create Documents', 'module' => 'documents', 'description' => 'Can create documents'],
            ['name' => 'edit_documents', 'display_name' => 'Edit Documents', 'module' => 'documents', 'description' => 'Can edit documents'],
            ['name' => 'update_documents', 'display_name' => 'Update Documents', 'module' => 'documents', 'description' => 'Can update documents'],
            ['name' => 'delete_documents', 'display_name' => 'Delete Documents', 'module' => 'documents', 'description' => 'Can delete documents'],
            ['name' => 'view_classified_documents', 'display_name' => 'View Classified Documents', 'module' => 'documents', 'description' => 'Can view classified documents'],
            ['name' => 'approve_documents', 'display_name' => 'Approve Documents', 'module' => 'documents', 'description' => 'Can approve documents'],
            ['name' => 'reject_documents', 'display_name' => 'Reject Documents', 'module' => 'documents', 'description' => 'Can reject documents'],
            ['name' => 'archive_documents', 'display_name' => 'Archive Documents', 'module' => 'documents', 'description' => 'Can archive documents'],
            
            // Schedule Management
            ['name' => 'view_schedules', 'display_name' => 'View Schedules', 'module' => 'schedules', 'description' => 'Can view schedules'],
            ['name' => 'create_schedules', 'display_name' => 'Create Schedules', 'module' => 'schedules', 'description' => 'Can create schedules'],
            ['name' => 'edit_schedules', 'display_name' => 'Edit Schedules', 'module' => 'schedules', 'description' => 'Can edit schedules'],
            ['name' => 'delete_schedules', 'display_name' => 'Delete Schedules', 'module' => 'schedules', 'description' => 'Can delete schedules'],
            
            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'module' => 'reports', 'description' => 'Can view reports'],
            ['name' => 'generate_reports', 'display_name' => 'Generate Reports', 'module' => 'reports', 'description' => 'Can generate reports'],
            ['name' => 'export_reports', 'display_name' => 'Export Reports', 'module' => 'reports', 'description' => 'Can export reports'],
            
            // System
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'module' => 'system', 'description' => 'Can view audit logs'],
            ['name' => 'manage_system_settings', 'display_name' => 'Manage System Settings', 'module' => 'system', 'description' => 'Can manage system settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin_sistem'],
            [
                'display_name' => 'Administrator Sistem',
                'description' => 'Full system access with all permissions'
            ]
        );

        $pimpinanRole = Role::firstOrCreate(
            ['name' => 'pimpinan'],
            [
                'display_name' => 'Pimpinan/Pejabat Tinggi',
                'description' => 'Leadership role with approval authority and view access'
            ]
        );

        $kasiKaurRole = Role::firstOrCreate(
            ['name' => 'kasi_kaur'],
            [
                'display_name' => 'Kasi/Kaur',
                'description' => 'Mid-level management with approval and data verification'
            ]
        );

        $batihStafRole = Role::firstOrCreate(
            ['name' => 'batih_staf'],
            [
                'display_name' => 'Batih/Staf',
                'description' => 'Staff role for data entry and operational tasks'
            ]
        );

        // Assign Permissions to Roles

        // Admin Sistem - All permissions
        $adminRole->permissions()->sync(Permission::all());

        // Pimpinan - View and approve
        $pimpinanRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_users',
                'view_documents',
                'view_classified_documents',
                'approve_documents',
                'reject_documents',
                'archive_documents',
                'view_schedules',
                'view_reports',
                'generate_reports',
                'export_reports',
                'view_audit_logs',
            ])->pluck('id')
        );

        // Kasi/Kaur - Mid-level management
        $kasiKaurRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_users',
                'view_documents',
                'create_documents',
                'edit_documents',
                'update_documents',
                'approve_documents',
                'reject_documents',
                'archive_documents',
                'view_schedules',
                'create_schedules',
                'edit_schedules',
                'view_reports',
                'generate_reports',
            ])->pluck('id')
        );

        // Batih/Staf - Operational staff
        $batihStafRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_documents',
                'create_documents',
                'edit_documents',
                'update_documents',
                'view_schedules',
                'create_schedules',
                'edit_schedules',
                'view_reports',
            ])->pluck('id')
        );

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
