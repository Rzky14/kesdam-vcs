<?php

namespace Database\Factories;

use App\Models\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApprovalWorkflow>
 */
class ApprovalWorkflowFactory extends Factory
{
    protected $model = ApprovalWorkflow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'document_type' => $this->faker->randomElement(['masuk', 'keluar', null]),
            'classification' => $this->faker->randomElement(['biasa', 'rahasia', 'telegram', null]),
            'approval_chain' => [1, 2, 3], // Mock role IDs
            'is_active' => true,
            'priority' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate workflow is for standard documents
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard Document Approval',
            'classification' => 'biasa',
            'priority' => 1,
        ]);
    }

    /**
     * Indicate workflow is for classified documents
     */
    public function classified(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Classified Document Approval',
            'classification' => 'rahasia',
            'priority' => 10,
        ]);
    }

    /**
     * Indicate workflow is for urgent documents
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgent Document Approval',
            'classification' => 'telegram',
            'priority' => 20,
        ]);
    }

    /**
     * Indicate workflow is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate workflow is for incoming documents
     */
    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'masuk',
        ]);
    }

    /**
     * Indicate workflow is for outgoing documents
     */
    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'keluar',
        ]);
    }
}
