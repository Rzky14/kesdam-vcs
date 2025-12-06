<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
        $this->user = User::factory()->create();
    }

    /**
     * Test dapat membuat notifikasi approval request
     */
    public function test_dapat_membuat_notifikasi_approval_request(): void
    {
        $document = Document::factory()->create(['created_by' => $this->user->id]);
        
        $this->notificationService->notifyApprovalRequest($document, $this->user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'approval_request',
            'related_model_type' => 'Document',
            'related_model_id' => $document->id,
        ]);
    }

    /**
     * Test dapat membuat notifikasi status dokumen berubah
     */
    public function test_dapat_membuat_notifikasi_status_dokumen_berubah(): void
    {
        $document = Document::factory()->create(['created_by' => $this->user->id]);

        $this->notificationService->notifyDocumentStatusChange($document, 'approved');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $document->created_by,
            'type' => 'document_status',
        ]);
    }

    /**
     * Test dapat membuat notifikasi sistem
     */
    public function test_dapat_membuat_notifikasi_sistem(): void
    {
        $this->notificationService->notifySystem(
            $this->user,
            'Test Notification',
            'This is a test system notification'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'system',
        ]);
    }

    /**
     * Test dapat mark notifikasi sebagai read
     */
    public function test_dapat_mark_notifikasi_sebagai_read(): void
    {
        $notification = Notification::factory()->unread()->create(['user_id' => $this->user->id]);

        $this->notificationService->markAsRead($notification);

        $this->assertTrue($notification->refresh()->isRead());
    }

    /**
     * Test dapat mark semua notifikasi sebagai read
     */
    public function test_dapat_mark_semua_notifikasi_sebagai_read(): void
    {
        Notification::factory(5)->unread()->create(['user_id' => $this->user->id]);

        $this->notificationService->markAllAsRead($this->user);

        $this->assertEquals(0, $this->user->notifications()->unread()->count());
    }

    /**
     * Test dapat get unread notifications
     */
    public function test_dapat_get_unread_notifications(): void
    {
        Notification::factory(3)->unread()->create(['user_id' => $this->user->id]);
        Notification::factory(2)->read()->create(['user_id' => $this->user->id]);

        $unread = $this->notificationService->getUnreadNotifications($this->user);

        $this->assertEquals(3, $unread->count());
    }

    /**
     * Test dapat get unread notification count
     */
    public function test_dapat_get_unread_notification_count(): void
    {
        Notification::factory(5)->unread()->create(['user_id' => $this->user->id]);
        Notification::factory(3)->read()->create(['user_id' => $this->user->id]);

        $count = $this->notificationService->getUnreadNotificationCount($this->user);

        $this->assertEquals(5, $count);
    }

    /**
     * Test dapat delete notifikasi
     */
    public function test_dapat_delete_notifikasi(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $this->notificationService->deleteNotification($notification);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /**
     * Test dapat delete semua notifikasi user
     */
    public function test_dapat_delete_semua_notifikasi_user(): void
    {
        Notification::factory(5)->create(['user_id' => $this->user->id]);
        Notification::factory(3)->create(); // notifications untuk user lain

        $this->notificationService->deleteAllNotifications($this->user);

        $this->assertEquals(0, $this->user->notifications()->count());
        $this->assertEquals(3, Notification::count());
    }

    /**
     * Test dapat get notification statistics
     */
    public function test_dapat_get_notification_statistics(): void
    {
        Notification::factory(2)->unread()->approvalRequest()->create(['user_id' => $this->user->id]);
        Notification::factory(3)->read()->documentStatus()->create(['user_id' => $this->user->id]);
        Notification::factory(1)->scheduleReminder()->create(['user_id' => $this->user->id]);

        $stats = $this->notificationService->getStatistics($this->user);

        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(2, $stats['unread']);
        $this->assertEquals(2, $stats['approval_requests']);
        $this->assertEquals(3, $stats['document_status_changes']);
        $this->assertEquals(1, $stats['schedule_reminders']);
    }

    /**
     * Test dapat get notifications by type
     */
    public function test_dapat_get_notifications_by_type(): void
    {
        Notification::factory(2)->approvalRequest()->create(['user_id' => $this->user->id]);
        Notification::factory(3)->documentStatus()->create(['user_id' => $this->user->id]);

        $approvalNotifications = $this->notificationService->getNotificationsByType(
            $this->user,
            'approval_request'
        );

        $this->assertEquals(2, $approvalNotifications->count());
    }

    /**
     * Test notification endpoint dapat di-access
     */
    public function test_notification_endpoint_dapat_diakses(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewIs('notifications.index');
    }

    /**
     * Test preferences endpoint dapat di-access
     */
    public function test_preferences_endpoint_dapat_diakses(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('notifications.preferences'));

        $response->assertOk();
        $response->assertViewIs('notifications.preferences');
    }

    /**
     * Test dapat update preference via API
     */
    public function test_dapat_update_preference_via_api(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('notifications.preferences.update'), [
            'approval_request_enabled' => true,
            'approval_request_email' => false,
            'document_status_enabled' => true,
            'schedule_reminder_enabled' => false,
            'system_notifications_enabled' => true,
        ]);

        $response->assertRedirect(route('notifications.preferences'));
        
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $this->user->id,
            'approval_request_enabled' => true,
            'approval_request_email' => false,
        ]);
    }

    /**
     * Test dapat get unread notifications via API
     */
    public function test_dapat_get_unread_notifications_via_api(): void
    {
        Notification::factory(2)->unread()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);
        $response = $this->get(route('notifications.api.unread'));

        $response->assertOk();
        $response->assertJsonPath('unread_count', 2);
    }

    /**
     * Test dapat mark notification as read via API
     */
    public function test_dapat_mark_notification_as_read_via_api(): void
    {
        $notification = Notification::factory()->unread()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);
        $response = $this->post(route('notifications.mark-as-read', $notification));

        $response->assertOk();
        $this->assertTrue($notification->refresh()->isRead());
    }

    /**
     * Test cannot access other user's notification
     */
    public function test_cannot_access_other_user_notification(): void
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);
        $response = $this->post(route('notifications.mark-as-read', $notification));

        $response->assertForbidden();
    }
}
