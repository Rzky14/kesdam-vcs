<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $periodEnd = $this->faker->dateTimeBetween($periodStart, 'now');

        return [
            'name' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement(['schedule', 'document']),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'created_by' => User::factory(),
            'data' => [
                'total' => $this->faker->numberBetween(10, 100),
                'summary' => $this->faker->paragraph(),
            ],
            'status' => 'generated',
        ];
    }

    /**
     * Indicate report is for schedule
     */
    public function schedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Laporan Jadwal - ' . now()->format('M Y'),
            'type' => 'schedule',
            'data' => [
                'total_schedules' => $this->faker->numberBetween(20, 50),
                'by_type' => [
                    'dukkes' => $this->faker->numberBetween(5, 15),
                    'jaga' => $this->faker->numberBetween(5, 15),
                    'kegiatan_satuan' => $this->faker->numberBetween(5, 15),
                ],
                'by_status' => [
                    'active' => $this->faker->numberBetween(10, 30),
                    'completed' => $this->faker->numberBetween(5, 20),
                ],
            ],
        ]);
    }

    /**
     * Indicate report is for document
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Laporan Dokumen - ' . now()->format('M Y'),
            'type' => 'document',
            'data' => [
                'total_documents' => $this->faker->numberBetween(30, 100),
                'by_type' => [
                    'masuk' => $this->faker->numberBetween(15, 50),
                    'keluar' => $this->faker->numberBetween(15, 50),
                ],
                'by_classification' => [
                    'biasa' => $this->faker->numberBetween(20, 70),
                    'rahasia' => $this->faker->numberBetween(5, 20),
                    'telegram' => $this->faker->numberBetween(2, 10),
                ],
                'by_status' => [
                    'draft' => $this->faker->numberBetween(5, 15),
                    'pending_approval' => $this->faker->numberBetween(5, 20),
                    'approved' => $this->faker->numberBetween(20, 60),
                ],
                'approval_rate' => $this->faker->randomFloat(2, 70, 95),
            ],
        ]);
    }

    /**
     * Indicate report is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate report is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'generated',
        ]);
    }

    /**
     * Indicate report has failed
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
