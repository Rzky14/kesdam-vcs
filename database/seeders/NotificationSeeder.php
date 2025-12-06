<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create notification preferences untuk semua users
        $users = User::all();

        foreach ($users as $user) {
            UserNotificationPreference::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'approval_request_enabled' => true,
                    'approval_request_email' => true,
                    'document_status_enabled' => true,
                    'document_status_email' => true,
                    'schedule_reminder_enabled' => true,
                    'schedule_reminder_email' => false,
                    'system_notifications_enabled' => true,
                    'schedule_reminder_timing' => '1-day',
                ]
            );
        }

        // Create sample notifications untuk demo
        if ($users->count() > 0) {
            // Approval request notifications
            Notification::factory(5)
                ->for($users->random())
                ->approvalRequest()
                ->unread()
                ->create();

            // Document status notifications
            Notification::factory(4)
                ->for($users->random())
                ->documentStatus()
                ->read()
                ->create();

            // Schedule reminders
            Notification::factory(3)
                ->for($users->random())
                ->scheduleReminder()
                ->create();

            // System notifications
            Notification::factory(2)
                ->for($users->random())
                ->system()
                ->create();
        }

        $this->command->info('Notification seeder completed successfully!');
    }
}
