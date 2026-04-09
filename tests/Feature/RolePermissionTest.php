<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_user_can_have_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin_sistem')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin_sistem'));
        $this->assertCount(1, $user->roles);
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();
        
        $user->assignRole('admin_sistem');
        $user->assignRole('kasi');

        $this->assertTrue($user->hasRole('admin_sistem'));
        $this->assertTrue($user->hasRole('kasi'));
        $this->assertCount(2, $user->roles);
    }

    public function test_user_can_remove_role(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'batih')->first();

        $user->assignRole($role);
        $this->assertTrue($user->hasRole('batih'));

        $user->removeRole($role);
        $this->assertFalse($user->hasRole('batih'));
    }

    public function test_role_can_have_permissions(): void
    {
        $role = Role::where('name', 'admin_sistem')->first();
        
        $this->assertGreaterThan(0, $role->permissions->count());
    }

    public function test_user_has_permission_through_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin_sistem');

        $this->assertTrue($user->hasPermission('create_users'));
        $this->assertTrue($user->hasPermission('view_documents'));
    }

    public function test_kasi_role_has_correct_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('kasi');

        // Kasi should be able to view and approve
        $this->assertTrue($user->hasPermission('view_documents'));
        $this->assertTrue($user->hasPermission('approve_documents'));
        
        // But not create or delete
        $this->assertFalse($user->hasPermission('create_users'));
        $this->assertFalse($user->hasPermission('delete_users'));
    }

    public function test_batih_role_has_limited_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('batih');

        // Batih can view and create
        $this->assertTrue($user->hasPermission('view_documents'));
        $this->assertTrue($user->hasPermission('create_documents'));
        
        // But cannot approve or delete
        $this->assertFalse($user->hasPermission('approve_documents'));
        $this->assertFalse($user->hasPermission('delete_users'));
    }

    public function test_role_can_give_permission(): void
    {
        $role = Role::where('name', 'batih')->first();
        $permission = Permission::where('name', 'view_audit_logs')->first();

        $initialCount = $role->permissions->count();
        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermission('view_audit_logs'));
        $this->assertEquals($initialCount + 1, $role->fresh()->permissions->count());
    }

    public function test_role_can_revoke_permission(): void
    {
        $role = Role::where('name', 'admin_sistem')->first();
        $permission = Permission::where('name', 'create_users')->first();

        $this->assertTrue($role->hasPermission('create_users'));

        $role->revokePermissionTo($permission);

        $this->assertFalse($role->fresh()->hasPermission('create_users'));
    }

    public function test_permissions_are_grouped_by_module(): void
    {
        $userPermissions = Permission::byModule('users');
        $documentPermissions = Permission::byModule('documents');

        $this->assertGreaterThan(0, $userPermissions->count());
        $this->assertGreaterThan(0, $documentPermissions->count());

        // Verify all permissions belong to correct module
        foreach ($userPermissions as $permission) {
            $this->assertEquals('users', $permission->module);
        }
    }
}
