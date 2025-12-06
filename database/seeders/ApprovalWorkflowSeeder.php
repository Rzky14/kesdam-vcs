<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRolePermission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get role IDs
        $adminRole = Role::where('name', 'admin_sistem')->first();
        $pimpinanRole = Role::where('name', 'pimpinan')->first();
        $kasiRole = Role::where('name', 'kasi_kaur')->first();
        $batihRole = Role::where('name', 'batih_staf')->first();

        if (!$adminRole || !$pimpinanRole || !$kasiRole || !$batihRole) {
            $this->command->error('Roles not found. Please run RoleSeeder first.');
            return;
        }

        // Standard Document Approval Workflow (Biasa)
        // Flow: Staf -> Kaur -> Kasi -> Pimpinan
        ApprovalWorkflow::create([
            'name' => 'Standard Document Approval',
            'document_type' => null, // Applies to both masuk and keluar
            'classification' => 'biasa',
            'approval_chain' => [
                $kasiRole->id,      // Level 1: Kasi/Kaur
                $pimpinanRole->id,  // Level 2: Pimpinan
            ],
            'is_active' => true,
            'priority' => 1,
        ]);

        // Classified Document Approval Workflow (Rahasia)
        // More strict approval chain for classified documents
        ApprovalWorkflow::create([
            'name' => 'Classified Document Approval',
            'document_type' => null,
            'classification' => 'rahasia',
            'approval_chain' => [
                $kasiRole->id,      // Level 1: Kasi/Kaur (verification)
                $pimpinanRole->id,  // Level 2: Pimpinan (approval)
                $adminRole->id,     // Level 3: Admin (security verification)
            ],
            'is_active' => true,
            'priority' => 10, // Higher priority than standard
        ]);

        // Urgent Document Approval Workflow (Telegram)
        // Fast-track approval for urgent communications
        ApprovalWorkflow::create([
            'name' => 'Urgent Document Approval',
            'document_type' => null,
            'classification' => 'telegram',
            'approval_chain' => [
                $pimpinanRole->id,  // Level 1: Direct to Pimpinan
            ],
            'is_active' => true,
            'priority' => 20, // Highest priority
        ]);

        // Incoming Document Workflow (Surat Masuk - Biasa)
        ApprovalWorkflow::create([
            'name' => 'Incoming Document Verification',
            'document_type' => 'masuk',
            'classification' => 'biasa',
            'approval_chain' => [
                $kasiRole->id,      // Level 1: Verification
                $pimpinanRole->id,  // Level 2: Acknowledgement
            ],
            'is_active' => true,
            'priority' => 2,
        ]);

        // Outgoing Document Workflow (Surat Keluar - Biasa)
        ApprovalWorkflow::create([
            'name' => 'Outgoing Document Approval',
            'document_type' => 'keluar',
            'classification' => 'biasa',
            'approval_chain' => [
                $kasiRole->id,      // Level 1: Content review
                $pimpinanRole->id,  // Level 2: Final approval & signature
            ],
            'is_active' => true,
            'priority' => 2,
        ]);

        $this->command->info('Approval workflows seeded successfully!');

        // Seed Approval Role Permissions
        $this->seedApprovalRolePermissions();
    }

    /**
     * Seed approval role permissions
     */
    private function seedApprovalRolePermissions(): void
    {
        $adminRole = Role::where('name', 'admin_sistem')->first();
        $pimpinanRole = Role::where('name', 'pimpinan')->first();
        $kasiRole = Role::where('name', 'kasi_kaur')->first();
        $batihRole = Role::where('name', 'batih_staf')->first();

        $permissions = [
            // Admin can do everything
            ['role_id' => $adminRole->id, 'action' => 'view', 'document_type' => null, 'classification' => null],
            ['role_id' => $adminRole->id, 'action' => 'submit', 'document_type' => null, 'classification' => null],
            ['role_id' => $adminRole->id, 'action' => 'approve', 'document_type' => null, 'classification' => null],
            ['role_id' => $adminRole->id, 'action' => 'request_correction', 'document_type' => null, 'classification' => null],

            // Pimpinan - final approver
            ['role_id' => $pimpinanRole->id, 'action' => 'view', 'document_type' => null, 'classification' => null],
            ['role_id' => $pimpinanRole->id, 'action' => 'approve', 'document_type' => null, 'classification' => null],
            ['role_id' => $pimpinanRole->id, 'action' => 'request_correction', 'document_type' => null, 'classification' => null],

            // Kasi/Kaur - mid-level approver
            ['role_id' => $kasiRole->id, 'action' => 'view', 'document_type' => null, 'classification' => null],
            ['role_id' => $kasiRole->id, 'action' => 'approve', 'document_type' => null, 'classification' => 'biasa'],
            ['role_id' => $kasiRole->id, 'action' => 'approve', 'document_type' => null, 'classification' => 'rahasia'],
            ['role_id' => $kasiRole->id, 'action' => 'request_correction', 'document_type' => null, 'classification' => null],

            // Batih/Staf - can submit and view
            ['role_id' => $batihRole->id, 'action' => 'view', 'document_type' => null, 'classification' => 'biasa'],
            ['role_id' => $batihRole->id, 'action' => 'submit', 'document_type' => null, 'classification' => 'biasa'],
        ];

        foreach ($permissions as $permission) {
            ApprovalRolePermission::updateOrCreate(
                [
                    'role_id' => $permission['role_id'],
                    'action' => $permission['action'],
                    'document_type' => $permission['document_type'],
                    'classification' => $permission['classification'],
                ],
                ['is_active' => true]
            );
        }

        $this->command->info('Approval role permissions seeded successfully!');
    }
}
