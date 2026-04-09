<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function admin_can_view_users_list()
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    /** @test */
    public function user_without_permission_cannot_view_users_list()
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        $admin = $this->createAdminUser();
        $role = Role::where('name', 'batih')->first();

        $userData = [
            'name' => 'Test User',
            'nrp' => '21050012345678',
            'rank' => 'Serda',
            'position' => 'Staf',
            'unit' => 'KESDAM III/Siliwangi',
            'email' => 'testuser@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
            'roles' => ['batih'],
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
            'nrp' => '21050012345678',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();
        $this->assertTrue($user->hasRole('batih'));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user_created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_creation_validates_required_fields()
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('users.store'), []);

        $response->assertSessionHasErrors(['name', 'nrp', 'rank', 'position', 'unit', 'email', 'password', 'roles']);
    }

    /** @test */
    public function user_creation_validates_unique_email_and_nrp()
    {
        $admin = $this->createAdminUser();
        $existingUser = $this->createBasicUser();

        $userData = [
            'name' => 'Another User',
            'nrp' => $existingUser->nrp,
            'rank' => 'Serda',
            'position' => 'Staf',
            'unit' => 'KESDAM III/Siliwangi',
            'email' => $existingUser->email,
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
            'roles' => ['batih'],
        ];

        $response = $this->actingAs($admin)->post(route('users.store'), $userData);

        $response->assertSessionHasErrors(['email', 'nrp']);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('users.show');
        $response->assertViewHas('user');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    /** @test */
    public function admin_can_update_user()
    {
        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();

        $updateData = [
            'name' => 'Updated Name',
            'nrp' => $user->nrp,
            'rank' => 'Sertu',
            'position' => 'Updated Position',
            'unit' => $user->unit,
            'email' => $user->email,
            'phone' => '089999999999',
            'is_active' => false,
            'roles' => ['kasi'],
        ];

        $response = $this->actingAs($admin)->put(route('users.update', $user), $updateData);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'rank' => 'Sertu',
            'position' => 'Updated Position',
            'is_active' => false,
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('kasi'));
        $this->assertFalse($user->hasRole('batih'));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user_updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_update_user_password()
    {
        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();
        $oldPasswordHash = $user->password;

        $updateData = [
            'name' => $user->name,
            'nrp' => $user->nrp,
            'rank' => $user->rank,
            'position' => $user->position,
            'unit' => $user->unit,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'is_active' => true,
            'roles' => ['batih'],
        ];

        $response = $this->actingAs($admin)->put(route('users.update', $user), $updateData);

        $response->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertNotEquals($oldPasswordHash, $user->password);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $admin = $this->createAdminUser();
        $user = $this->createBasicUser();
        $userId = $user->id;

        $response = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user_deleted',
            'auditable_type' => User::class,
            'auditable_id' => $userId,
        ]);
    }

    /** @test */
    public function user_cannot_delete_themselves()
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /** @test */
    public function user_without_permission_cannot_delete_users()
    {
        $user = $this->createBasicUser();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('users.destroy', $targetUser));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    }

    /** @test */
    public function users_list_can_be_searched()
    {
        $admin = $this->createAdminUser();
        
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    /** @test */
    public function users_list_can_be_filtered_by_role()
    {
        $admin = $this->createAdminUser();
        
        $staffUser = User::factory()->create();
        $staffUser->assignRole('batih');
        
        $kasiUser = User::factory()->create();
        $kasiUser->assignRole('kasi');

        $response = $this->actingAs($admin)->get(route('users.index', ['role' => 'batih']));

        $response->assertStatus(200);
        $response->assertSee($staffUser->name);
    }

    /** @test */
    public function user_can_view_own_profile()
    {
        $user = $this->createBasicUser();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.show');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    /** @test */
    public function user_can_update_own_profile()
    {
        $user = $this->createBasicUser();

        $updateData = [
            'name' => 'Updated Name',
            'nrp' => $user->nrp,
            'rank' => 'Sertu',
            'position' => 'Updated Position',
            'unit' => $user->unit,
            'email' => $user->email,
            'phone' => '089999999999',
            'address' => 'New Address 123',
        ];

        $response = $this->actingAs($user)->put(route('profile.update'), $updateData);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'rank' => 'Sertu',
            'address' => 'New Address 123',
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'profile_updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_can_change_own_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);
        $user->assignRole('batih');

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_changed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);
        $user->assignRole('batih');

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    // Helper methods
    protected function createAdminUser(): User
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        
        // Assign admin_sistem role which has all permissions
        $adminRole = Role::where('name', 'admin_sistem')->first();
        $user->roles()->attach($adminRole->id);
        
        return $user;
    }

    protected function createBasicUser(): User
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        
        // Assign batih role
        $staffRole = Role::where('name', 'batih')->first();
        $user->roles()->attach($staffRole->id);
        
        return $user;
    }
}
