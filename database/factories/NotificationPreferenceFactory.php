<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(NotificationPreference::TYPES);
        
        return [
            'user_id' => User::factory(),
            'notification_type' => $this->faker->randomElement($types),
            'in_app_enabled' => $this->faker->boolean(80), // 80% enabled
            'email_enabled' => $this->faker->boolean(30), // 30% enabled
        ];
    }

    /**
     * State for in-app only
     */
    public function inAppOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_app_enabled' => true,
            'email_enabled' => false,
        ]);
    }

    /**
     * State for email only
     */
    public function emailOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_app_enabled' => false,
            'email_enabled' => true,
        ]);
    }

    /**
     * State for both channels
     */
    public function bothChannels(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_app_enabled' => true,
            'email_enabled' => true,
        ]);
    }

    /**
     * State for disabled
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_app_enabled' => false,
            'email_enabled' => false,
        ]);
    }

    /**
     * State for schedule reminder type
     */
    public function scheduleReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'schedule_reminder',
        ]);
    }

    /**
     * State for approval request type
     */
    public function approvalRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => 'approval_request',
        ]);
    }
}
