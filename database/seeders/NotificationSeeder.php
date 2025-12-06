<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default notification preferences for all existing users
        $users = User::all();

        foreach ($users as $user) {
            // Create preferences for each notification type
            foreach (NotificationPreference::TYPES as $type => $label) {
                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $type,
                    ],
                    [
                        'in_app_enabled' => true,  // Enable all in-app by default
                        'email_enabled' => false,  // Disable email by default
                    ]
                );
            }
        }

        $this->command->info('Created notification preferences for ' . $users->count() . ' users');
        $this->command->info('Total preferences created: ' . (NotificationPreference::count()));
    }
}
