<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Permission;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $staff;
    protected Role $adminRole;
    protected Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = Role::factory()->create(['name' => 'admin_sistem']);
        $this->staffRole = Role::factory()->create(['name' => 'batih']);

        // Create permissions
        $viewSchedules = Permission::factory()->create(['name' => 'view_schedules']);
        $createSchedules = Permission::factory()->create(['name' => 'create_schedules']);
        $editSchedules = Permission::factory()->create(['name' => 'edit_schedules']);
        $deleteSchedules = Permission::factory()->create(['name' => 'delete_schedules']);

        // Assign all permissions to admin
        $this->adminRole->permissions()->attach([
            $viewSchedules->id,
            $createSchedules->id,
            $editSchedules->id,
            $deleteSchedules->id,
        ]);

        // Assign limited permissions to staff
        $this->staffRole->permissions()->attach([
            $viewSchedules->id,
            $createSchedules->id,
        ]);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($this->staffRole);
    }

    /** @test */
    public function user_can_view_schedules_list_with_permission()
    {
        $this->actingAs($this->admin);

        Schedule::factory()->count(3)->create([
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('schedules.index'));

        $response->assertStatus(200);
        $response->assertViewIs('schedules.index');
        $response->assertViewHas('schedules');
    }

    /** @test */
    public function user_cannot_view_schedules_without_permission()
    {
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission);

        $response = $this->get(route('schedules.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_search_schedules_by_title()
    {
        $this->actingAs($this->admin);

        Schedule::factory()->create([
            'title' => 'Jadwal Jaga Pos 1',
            'created_by' => $this->admin->id,
        ]);

        Schedule::factory()->create([
            'title' => 'Jadwal Dukkes Pagi',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('schedules.index', ['search' => 'Jaga']));

        $response->assertStatus(200);
        $response->assertSee('Jadwal Jaga Pos 1');
        $response->assertDontSee('Jadwal Dukkes Pagi');
    }

    /** @test */
    public function user_can_filter_schedules_by_type()
    {
        $this->actingAs($this->admin);

        Schedule::factory()->dukkes()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);
        Schedule::factory()->jaga()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);

        $response = $this->get(route('schedules.index', ['type' => 'dukkes']));

        $response->assertStatus(200);
        $response->assertSee('Dukkes');
    }

    /** @test */
    public function user_can_filter_schedules_by_status()
    {
        $this->actingAs($this->admin);

        Schedule::factory()->active()->create(['created_by' => $this->admin->id]);
        Schedule::factory()->draft()->create(['created_by' => $this->admin->id]);

        $response = $this->get(route('schedules.index', ['status' => 'active']));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_filter_schedules_by_date_range()
    {
        $this->actingAs($this->admin);

        Schedule::factory()->create([
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'created_by' => $this->admin->id,
        ]);

        Schedule::factory()->create([
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-05',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('schedules.index', [
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-30',
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_create_schedule_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('schedules.create'));

        $response->assertStatus(200);
        $response->assertViewIs('schedules.create');
        $response->assertViewHas('users');
    }

    /** @test */
    public function user_cannot_view_create_form_without_permission()
    {
        $this->actingAs($this->staff);
        $this->staffRole->permissions()->detach(Permission::where('name', 'create_schedules')->first());

        $response = $this->get(route('schedules.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_create_schedule_with_valid_data()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(2)->create();

        $scheduleData = [
            'type' => 'jaga',
            'title' => 'Jadwal Jaga Pos 1',
            'description' => 'Jadwal jaga untuk pos 1',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'location' => 'Pos 1',
            'personnel' => $users->pluck('id')->toArray(),
            'status' => 'active',
            'notes' => 'Catatan jadwal',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'title' => 'Jadwal Jaga Pos 1',
            'type' => 'jaga',
            'created_by' => $this->admin->id,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Schedule::class,
            'event' => 'created',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function schedule_creation_requires_valid_type()
    {
        $this->actingAs($this->admin);

        $scheduleData = [
            'type' => 'invalid_type',
            'title' => 'Test Schedule',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'personnel' => [User::factory()->create()->id],
            'status' => 'active',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertSessionHasErrors('type');
    }

    /** @test */
    public function schedule_creation_requires_title()
    {
        $this->actingAs($this->admin);

        $scheduleData = [
            'type' => 'jaga',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'personnel' => [User::factory()->create()->id],
            'status' => 'active',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertSessionHasErrors('title');
    }

    /** @test */
    public function schedule_end_date_must_be_after_or_equal_start_date()
    {
        $this->actingAs($this->admin);

        $scheduleData = [
            'type' => 'jaga',
            'title' => 'Test Schedule',
            'start_date' => '2025-11-15',
            'end_date' => '2025-11-10',
            'personnel' => [User::factory()->create()->id],
            'status' => 'active',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertSessionHasErrors('end_date');
    }

    /** @test */
    public function schedule_requires_at_least_one_personnel()
    {
        $this->actingAs($this->admin);

        $scheduleData = [
            'type' => 'jaga',
            'title' => 'Test Schedule',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'personnel' => [],
            'status' => 'active',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertSessionHasErrors('personnel');
    }

    /** @test */
    public function system_detects_personnel_scheduling_conflicts()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        // Create existing schedule
        Schedule::factory()->active()->create([
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'personnel' => [$user->id],
            'created_by' => $this->admin->id,
        ]);

        // Try to create conflicting schedule
        $scheduleData = [
            'type' => 'jaga',
            'title' => 'Conflicting Schedule',
            'start_date' => '2025-11-12',
            'end_date' => '2025-11-17',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'personnel' => [$user->id],
            'status' => 'active',
        ];

        $response = $this->post(route('schedules.store'), $scheduleData);

        $response->assertSessionHas('error');
        $response->assertRedirect();
    }

    /** @test */
    public function user_can_view_schedule_details()
    {
        $this->actingAs($this->admin);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);

        $response = $this->get(route('schedules.show', $schedule));

        $response->assertStatus(200);
        $response->assertViewIs('schedules.show');
        $response->assertViewHas('schedule');
        $response->assertViewHas('personnel');
        $response->assertViewHas('auditLogs');
        $response->assertSee($schedule->title);
    }

    /** @test */
    public function user_can_view_edit_schedule_form()
    {
        $this->actingAs($this->admin);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('schedules.edit', $schedule));

        $response->assertStatus(200);
        $response->assertViewIs('schedules.edit');
        $response->assertViewHas('schedule');
        $response->assertViewHas('users');
    }

    /** @test */
    public function user_cannot_edit_schedule_without_permission()
    {
        $this->actingAs($this->staff);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('schedules.edit', $schedule));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_update_schedule_with_valid_data()
    {
        $this->actingAs($this->admin);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);

        $updateData = [
            'type' => 'dukkes',
            'title' => 'Updated Schedule Title',
            'description' => 'Updated description',
            'start_date' => '2025-11-20',
            'end_date' => '2025-11-25',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'location' => 'Updated Location',
            'personnel' => [$this->admin->id],
            'status' => 'completed',
            'notes' => 'Updated notes',
        ];

        $response = $this->put(route('schedules.update', $schedule), $updateData);

        $response->assertRedirect(route('schedules.show', $schedule));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'title' => 'Updated Schedule Title',
            'type' => 'dukkes',
            'status' => 'completed',
            'updated_by' => $this->admin->id,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Schedule::class,
            'auditable_id' => $schedule->id,
            'event' => 'updated',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function user_can_delete_schedule()
    {
        $this->actingAs($this->admin);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $response = $this->delete(route('schedules.destroy', $schedule));

        $response->assertRedirect(route('schedules.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('schedules', [
            'id' => $schedule->id,
        ]);

        // Check audit log was created BEFORE deletion
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Schedule::class,
            'auditable_id' => $schedule->id,
            'event' => 'deleted',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_schedule_without_permission()
    {
        $this->actingAs($this->staff);

        $schedule = Schedule::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $response = $this->delete(route('schedules.destroy', $schedule));

        $response->assertStatus(403);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function schedule_model_has_correct_relationships()
    {
        $creator = User::factory()->create();
        $updater = User::factory()->create();

        $schedule = Schedule::factory()->create([
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);

        $this->assertInstanceOf(User::class, $schedule->creator);
        $this->assertEquals($creator->id, $schedule->creator->id);

        $this->assertInstanceOf(User::class, $schedule->updater);
        $this->assertEquals($updater->id, $schedule->updater->id);
    }

    /** @test */
    public function schedule_model_scopes_work_correctly()
    {
        $this->actingAs($this->admin);

        $dukkes = Schedule::factory()->dukkes()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);
        $jaga = Schedule::factory()->jaga()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);
        $activeSchedule = Schedule::factory()->active()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);
        $draftSchedule = Schedule::factory()->draft()->create([
            'created_by' => $this->admin->id,
            'personnel' => [$this->admin->id],
        ]);

        $this->assertTrue(Schedule::ofType('dukkes')->where('id', $dukkes->id)->exists());
        $this->assertTrue(Schedule::ofType('jaga')->where('id', $jaga->id)->exists());
        $this->assertTrue(Schedule::withStatus('draft')->where('id', $draftSchedule->id)->exists());
        $this->assertTrue(Schedule::active()->where('id', $activeSchedule->id)->exists());
    }

    /** @test */
    public function schedule_can_get_personnel_users()
    {
        $users = User::factory()->count(3)->create();
        
        $schedule = Schedule::factory()->create([
            'personnel' => $users->pluck('id')->toArray(),
            'created_by' => $this->admin->id,
        ]);

        $personnelUsers = $schedule->getPersonnelUsers();

        $this->assertCount(3, $personnelUsers);
        $this->assertInstanceOf(User::class, $personnelUsers->first());
    }

    /** @test */
    public function schedule_status_helpers_work_correctly()
    {
        $activeSchedule = Schedule::factory()->active()->create(['created_by' => $this->admin->id]);
        $draftSchedule = Schedule::factory()->draft()->create(['created_by' => $this->admin->id]);
        $completedSchedule = Schedule::factory()->completed()->create(['created_by' => $this->admin->id]);
        $cancelledSchedule = Schedule::factory()->cancelled()->create(['created_by' => $this->admin->id]);

        $this->assertTrue($activeSchedule->isActive());
        $this->assertFalse($activeSchedule->isDraft());

        $this->assertTrue($draftSchedule->isDraft());
        $this->assertFalse($draftSchedule->isActive());

        $this->assertTrue($completedSchedule->isCompleted());
        $this->assertTrue($cancelledSchedule->isCancelled());
    }

    /** @test */
    public function schedule_displays_correct_labels()
    {
        $schedule = Schedule::factory()->dukkes()->active()->create(['created_by' => $this->admin->id]);

        $this->assertEquals('Jadwal Dukkes', $schedule->getTypeLabel());
        $this->assertEquals('Aktif', $schedule->getStatusLabel());
        $this->assertEquals('success', $schedule->getStatusColor());
    }
}
