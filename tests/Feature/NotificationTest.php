<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\NotificationPreference;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\ApprovalRequestNotification;
use App\Notifications\ScheduleReminderNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->notificationService = new NotificationService();
    }

    /** @test */
    public function test_user_can_view_notifications_page()
    {
        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertViewHas('notifications');
        $response->assertViewHas('unreadCount');
    }

    /** @test */
    public function test_user_can_view_notification_details()
    {
        // Create a notification
        $this->user->notify(new ScheduleReminderNotification(
            Schedule::factory()->create(),
            24
        ));

        $notification = $this->user->notifications->first();

        $response = $this->actingAs($this->user)
            ->get(route('notifications.show', $notification->id));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.show');
        $response->assertViewHas('notification');
    }

    /** @test */
    public function test_viewing_notification_marks_it_as_read()
    {
        $this->user->notify(new ScheduleReminderNotification(
            Schedule::factory()->create(),
            24
        ));

        $notification = $this->user->unreadNotifications->first();
        $this->assertNull($notification->read_at);

        $this->actingAs($this->user)
            ->get(route('notifications.show', $notification->id));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function test_user_can_mark_notification_as_read_via_ajax()
    {
        $this->user->notify(new ScheduleReminderNotification(
            Schedule::factory()->create(),
            24
        ));

        $notification = $this->user->unreadNotifications->first();

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.read', $notification->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function test_user_can_mark_all_notifications_as_read()
    {
        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $this->user->notify(new ScheduleReminderNotification(
                Schedule::factory()->create(),
                24
            ));
        }

        $this->assertEquals(3, $this->user->unreadNotifications->count());

        $response = $this->actingAs($this->user)
            ->postJson(route('notifications.mark-all-read'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, $this->user->unreadNotifications->count());
    }

    /** @test */
    public function test_user_can_delete_notification()
    {
        $this->user->notify(new ScheduleReminderNotification(
            Schedule::factory()->create(),
            24
        ));

        $notification = $this->user->notifications->first();

        $response = $this->actingAs($this->user)
            ->delete(route('notifications.destroy', $notification->id));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function test_user_can_delete_all_notifications()
    {
        // Create multiple notifications
        for ($i = 0; $i < 5; $i++) {
            $this->user->notify(new ScheduleReminderNotification(
                Schedule::factory()->create(),
                24
            ));
        }

        $this->assertEquals(5, $this->user->notifications->count());

        $response = $this->actingAs($this->user)
            ->delete(route('notifications.delete-all'));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(0, $this->user->notifications->count());
    }

    /** @test */
    public function test_user_can_view_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->get(route('notifications.preferences'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.preferences');
        $response->assertViewHas('preferences');
    }

    /** @test */
    public function test_user_can_update_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->put(route('notifications.preferences.update'), [
                'preferences' => [
                    'schedule_reminder' => [
                        'in_app_enabled' => true,
                        'email_enabled' => true,
                    ],
                    'approval_request' => [
                        'in_app_enabled' => false,
                        'email_enabled' => false,
                    ],
                ],
            ]);

        $response->assertRedirect(route('notifications.preferences'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'schedule_reminder',
            'in_app_enabled' => true,
            'email_enabled' => true,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'approval_request',
            'in_app_enabled' => false,
            'email_enabled' => false,
        ]);
    }

    /** @test */
    public function test_notification_respects_user_preferences()
    {
        // Disable all notifications
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'schedule_reminder',
            'in_app_enabled' => false,
            'email_enabled' => false,
        ]);

        Notification::fake();

        $schedule = Schedule::factory()->create();
        $this->notificationService->sendScheduleReminder($schedule, $this->user, 24);

        // Should not send notification when disabled
        Notification::assertNothingSent();
    }

    /** @test */
    public function test_schedule_reminder_notification_is_sent()
    {
        Notification::fake();

        $schedule = Schedule::factory()->create();
        $this->notificationService->sendScheduleReminder($schedule, $this->user, 24);

        Notification::assertSentTo($this->user, ScheduleReminderNotification::class);
    }

    /** @test */
    public function test_approval_request_notification_is_sent()
    {
        Notification::fake();

        $document = Dokumen::factory()->create();
        $approver = User::factory()->create();
        
        $this->notificationService->sendApprovalRequest($document, $approver, $this->user, 1);

        Notification::assertSentTo($approver, ApprovalRequestNotification::class);
    }

    /** @test */
    public function test_get_unread_count_api_endpoint()
    {
        // Create notifications
        for ($i = 0; $i < 3; $i++) {
            $this->user->notify(new ScheduleReminderNotification(
                Schedule::factory()->create(),
                24
            ));
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.unread-count'));

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    /** @test */
    public function test_get_recent_notifications_api_endpoint()
    {
        // Create notifications
        for ($i = 0; $i < 5; $i++) {
            $this->user->notify(new ScheduleReminderNotification(
                Schedule::factory()->create(),
                24
            ));
        }

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.recent') . '?limit=3');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'notifications',
            'total_unread',
        ]);
        $this->assertCount(3, $response->json('notifications'));
    }

    /** @test */
    public function test_filter_notifications_by_type()
    {
        $response = $this->actingAs($this->user)
            ->get(route('notifications.index', ['type' => 'schedule_reminder']));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
    }

    /** @test */
    public function test_filter_unread_notifications()
    {
        // Create mix of read and unread
        for ($i = 0; $i < 2; $i++) {
            $this->user->notify(new ScheduleReminderNotification(
                Schedule::factory()->create(),
                24
            ));
        }

        // Mark one as read
        $this->user->unreadNotifications->first()->markAsRead();

        $response = $this->actingAs($this->user)
            ->get(route('notifications.index', ['filter' => 'unread']));

        $response->assertStatus(200);
        $this->assertEquals(1, $this->user->unreadNotifications->count());
    }

    /** @test */
    public function test_unauthorized_user_cannot_access_notifications()
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function test_user_cannot_view_other_users_notifications()
    {
        $otherUser = User::factory()->create();
        $otherUser->notify(new ScheduleReminderNotification(
            Schedule::factory()->create(),
            24
        ));

        $notification = $otherUser->notifications->first();

        $response = $this->actingAs($this->user)
            ->get(route('notifications.show', $notification->id));

        $response->assertStatus(404);
    }
}
