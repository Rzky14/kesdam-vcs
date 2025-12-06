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
            // User Management (5 permissions)
            ['name' => 'view_users', 'display_name' => 'View Users', 'module' => 'users', 'description' => 'Can view user list'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'module' => 'users', 'description' => 'Can create new users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'module' => 'users', 'description' => 'Can edit user information'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'module' => 'users', 'description' => 'Can delete users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'module' => 'users', 'description' => 'Can assign and remove roles'],
            
            // Document Management (12 permissions)
            ['name' => 'view_documents', 'display_name' => 'View Documents', 'module' => 'documents', 'description' => 'Can view documents'],
            ['name' => 'create_documents', 'display_name' => 'Create Documents', 'module' => 'documents', 'description' => 'Can create documents'],
            ['name' => 'edit_documents', 'display_name' => 'Edit Documents', 'module' => 'documents', 'description' => 'Can edit documents'],
            ['name' => 'delete_documents', 'display_name' => 'Delete Documents', 'module' => 'documents', 'description' => 'Can delete documents'],
            ['name' => 'view_classified_documents', 'display_name' => 'View Classified Documents', 'module' => 'documents', 'description' => 'Can view classified (rahasia) documents'],
            ['name' => 'approve_documents', 'display_name' => 'Approve Documents', 'module' => 'documents', 'description' => 'Can approve documents in workflow'],
            ['name' => 'reject_documents', 'display_name' => 'Reject Documents', 'module' => 'documents', 'description' => 'Can reject documents in workflow'],
            ['name' => 'request_correction_documents', 'display_name' => 'Request Correction', 'module' => 'documents', 'description' => 'Can request corrections on documents'],
            ['name' => 'download_documents', 'display_name' => 'Download Documents', 'module' => 'documents', 'description' => 'Can download document files'],
            ['name' => 'print_documents', 'display_name' => 'Print Documents', 'module' => 'documents', 'description' => 'Can print documents'],
            ['name' => 'upload_signature', 'display_name' => 'Upload Signature', 'module' => 'documents', 'description' => 'Can upload digital signature'],
            ['name' => 'archive_documents', 'display_name' => 'Archive Documents', 'module' => 'documents', 'description' => 'Can archive documents'],
            
            // Schedule Management (6 permissions)
            ['name' => 'view_schedules', 'display_name' => 'View Schedules', 'module' => 'schedules', 'description' => 'Can view schedules'],
            ['name' => 'create_schedules', 'display_name' => 'Create Schedules', 'module' => 'schedules', 'description' => 'Can create schedules'],
            ['name' => 'edit_schedules', 'display_name' => 'Edit Schedules', 'module' => 'schedules', 'description' => 'Can edit schedules'],
            ['name' => 'delete_schedules', 'display_name' => 'Delete Schedules', 'module' => 'schedules', 'description' => 'Can delete schedules'],
            ['name' => 'approve_schedules', 'display_name' => 'Approve Schedules', 'module' => 'schedules', 'description' => 'Can approve schedules'],
            ['name' => 'publish_schedules', 'display_name' => 'Publish Schedules', 'module' => 'schedules', 'description' => 'Can publish schedules to all users'],
            
            // Reports (3 permissions)
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'module' => 'reports', 'description' => 'Can view reports'],
            ['name' => 'generate_reports', 'display_name' => 'Generate Reports', 'module' => 'reports', 'description' => 'Can generate reports'],
            ['name' => 'export_reports', 'display_name' => 'Export Reports', 'module' => 'reports', 'description' => 'Can export reports to PDF/Excel'],
            
            // Notifications (2 permissions)
            ['name' => 'view_notifications', 'display_name' => 'View Notifications', 'module' => 'notifications', 'description' => 'Can view notifications'],
            ['name' => 'manage_notifications', 'display_name' => 'Manage Notifications', 'module' => 'notifications', 'description' => 'Can manage notification settings'],
            
            // System (2 permissions)
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'module' => 'system', 'description' => 'Can view system audit logs'],
            ['name' => 'manage_system_settings', 'display_name' => 'Manage System Settings', 'module' => 'system', 'description' => 'Can manage system configuration'],
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
                'description' => 'Leadership role with approval authority and comprehensive view access'
            ]
        );

        $kasiKaurRole = Role::firstOrCreate(
            ['name' => 'kasi_kaur'],
            [
                'display_name' => 'Kasi/Kaur',
                'description' => 'Mid-level management with approval, verification, and data management'
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

        // Admin Sistem - All permissions (30 total)
        $adminRole->permissions()->sync(Permission::all());

        // Pimpinan - View, Approve, Report (18 permissions)
        $pimpinanRole->permissions()->sync(
            Permission::whereIn('name', [
                // Users
                'view_users',
                
                // Documents - Full approval authority
                'view_documents',
                'view_classified_documents',
                'approve_documents',
                'reject_documents',
                'request_correction_documents',
                'download_documents',
                'print_documents',
                'upload_signature',
                
                // Schedules - Approval authority
                'view_schedules',
                'approve_schedules',
                'publish_schedules',
                
                // Reports - Full access
                'view_reports',
                'generate_reports',
                'export_reports',
                
                // System
                'view_audit_logs',
                
                // Notifications
                'view_notifications',
            ])->pluck('id')
        );

        // Kasi/Kaur - Mid-level management (22 permissions)
        $kasiKaurRole->permissions()->sync(
            Permission::whereIn('name', [
                // Users
                'view_users',
                
                // Documents - Can manage and approve
                'view_documents',
                'create_documents',
                'edit_documents',
                'view_classified_documents',
                'approve_documents',
                'reject_documents',
                'request_correction_documents',
                'download_documents',
                'print_documents',
                'upload_signature',
                
                // Schedules - Can manage and approve
                'view_schedules',
                'create_schedules',
                'edit_schedules',
                'delete_schedules',
                'approve_schedules',
                'publish_schedules',
                
                // Reports
                'view_reports',
                'generate_reports',
                'export_reports',
                
                // Notifications
                'view_notifications',
                'manage_notifications',
            ])->pluck('id')
        );

        // Batih/Staf - Operational staff (12 permissions)
        $batihStafRole->permissions()->sync(
            Permission::whereIn('name', [
                // Documents - Basic operations
                'view_documents',
                'create_documents',
                'edit_documents',
                'download_documents',
                'print_documents',
                
                // Schedules - Basic operations
                'view_schedules',
                'create_schedules',
                'edit_schedules',
                'delete_schedules',
                
                // Reports - View only
                'view_reports',
                
                // Notifications
                'view_notifications',
            ])->pluck('id')
        );

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('📊 Total Permissions: ' . Permission::count());
        $this->command->info('👥 Total Roles: ' . Role::count());
        $this->command->info('');
        $this->command->info('Role Assignments:');
        $this->command->info('- Admin Sistem: ' . $adminRole->permissions()->count() . ' permissions');
        $this->command->info('- Pimpinan: ' . $pimpinanRole->permissions()->count() . ' permissions');
        $this->command->info('- Kasi/Kaur: ' . $kasiKaurRole->permissions()->count() . ' permissions');
        $this->command->info('- Batih/Staf: ' . $batihStafRole->permissions()->count() . ' permissions');
    }
}
