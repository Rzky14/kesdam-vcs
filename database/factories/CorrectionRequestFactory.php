<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Document;
use App\Models\ApprovalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CorrectionRequest>
 */
class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'approval_history_id' => ApprovalHistory::factory(),
            'requested_by_user_id' => User::factory(),
            'assigned_to_user_id' => User::factory(),
            'correction_notes' => $this->faker->paragraph(),
            'status' => 'pending',
            'revision_number' => 1,
            'requested_at' => now(),
            'due_date' => now()->addDays(3),
        ];
    }

    /**
     * Indicate the correction is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'corrected_at' => null,
        ]);
    }

    /**
     * Indicate the correction is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'corrected_at' => null,
        ]);
    }

    /**
     * Indicate the correction is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'corrected_at' => now(),
        ]);
    }

    /**
     * Indicate the correction was rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'corrected_at' => now(),
        ]);
    }

    /**
     * Indicate the correction is overdue
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'due_date' => now()->subDays(1),
            'corrected_at' => null,
        ]);
    }
}
