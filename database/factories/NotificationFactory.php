<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['approval_request', 'document_status', 'schedule_reminder', 'system'];
        $type = $this->faker->randomElement($types);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => $this->generateTitle($type),
            'message' => $this->faker->paragraph(),
            'icon' => $this->getIcon($type),
            'action_url' => $this->faker->optional(0.7)->url(),
            'related_model_type' => $this->faker->optional(0.5)->randomElement(['Document', 'Schedule']),
            'related_model_id' => $this->faker->optional(0.5)->numberBetween(1, 100),
            'read_at' => $this->faker->optional(0.6)->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'read_at' => $this->faker->dateTime(),
        ]);
    }

    /**
     * Set notification type to approval_request
     */
    public function approvalRequest(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'approval_request',
            'title' => 'Permintaan Persetujuan Dokumen',
            'icon' => 'file-earmark-check',
        ]);
    }

    /**
     * Set notification type to document_status
     */
    public function documentStatus(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'document_status',
            'title' => 'Status Dokumen Berubah',
            'icon' => 'file-earmark-check',
        ]);
    }

    /**
     * Set notification type to schedule_reminder
     */
    public function scheduleReminder(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'schedule_reminder',
            'title' => 'Pengingat Jadwal',
            'icon' => 'calendar-event',
        ]);
    }

    /**
     * Set notification type to system
     */
    public function system(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'system',
            'title' => 'Notifikasi Sistem',
            'icon' => 'info-circle',
        ]);
    }

    /**
     * Generate title based on type
     */
    private function generateTitle(string $type): string
    {
        return match($type) {
            'approval_request' => 'Permintaan Persetujuan Dokumen',
            'document_status' => 'Status Dokumen Berubah',
            'schedule_reminder' => 'Pengingat Jadwal',
            'system' => 'Notifikasi Sistem',
            default => 'Notifikasi',
        };
    }

    /**
     * Get icon based on type
     */
    private function getIcon(string $type): string
    {
        return match($type) {
            'approval_request' => 'file-earmark-check',
            'document_status' => 'file-earmark-check',
            'schedule_reminder' => 'calendar-event',
            'system' => 'info-circle',
            default => 'bell',
        };
    }
}
