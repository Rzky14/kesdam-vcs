<?php

namespace Database\Factories;

use App\Models\ApprovalHistory;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalHistory>
 */
class ApprovalHistoryFactory extends Factory
{
    protected $model = ApprovalHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Dokumen::factory(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['submitted', 'approved', 'rejected', 'correction_requested']),
            'status' => 'pending',
            'comment' => $this->faker->optional()->sentence(),
            'current_approver_role' => $this->faker->randomElement(['admin_sistem', 'pimpinan', 'kasi_kaur', 'batih_staf']),
            'approval_level' => 1,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'action_date' => now(),
        ];
    }

    /**
     * Indicate the history is for submission
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'submitted',
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate the history is for approval
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'approved',
            'status' => 'approved',
            'comment' => 'Approved',
        ]);
    }

    /**
     * Indicate the history is for rejection
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'rejected',
            'status' => 'rejected',
            'comment' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate the history is for correction request
     */
    public function correctionRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'correction_requested',
            'status' => 'correction_requested',
            'comment' => $this->faker->sentence(),
        ]);
    }
}
