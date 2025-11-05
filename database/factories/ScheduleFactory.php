<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, $startDate->format('Y-m-d') . ' +7 days');
        
        $hasTime = $this->faker->boolean(70); // 70% chance to have time
        
        return [
            'type' => $this->faker->randomElement(['dukkes', 'jaga', 'kegiatan_satuan']),
            'title' => $this->faker->randomElement([
                'Jadwal Jaga Pos 1',
                'Jadwal Dukkes Pagi',
                'Apel Pagi Kesatuan',
                'Jadwal Piket Malam',
                'Latihan Rutin Mingguan',
                'Pemeriksaan Kesehatan',
                'Rapat Koordinasi',
            ]) . ' - ' . $this->faker->word(),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $hasTime ? $this->faker->time('H:i:s') : null,
            'end_time' => $hasTime ? $this->faker->time('H:i:s') : null,
            'location' => $this->faker->optional(0.7)->randomElement([
                'Pos 1',
                'Pos 2',
                'Ruang Kesehatan',
                'Lapangan Upacara',
                'Aula Kesatuan',
                'Ruang Rapat',
                'Markas Komando',
            ]),
            'personnel' => [], // Will be set by state methods
            'status' => $this->faker->randomElement(['draft', 'active', 'completed', 'cancelled']),
            'notes' => $this->faker->optional(0.5)->sentence(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the schedule is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the schedule is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the schedule is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the schedule is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the schedule is of type dukkes.
     */
    public function dukkes(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'dukkes',
            'title' => 'Jadwal Dukkes ' . uniqid(),
        ]);
    }

    /**
     * Indicate that the schedule is of type jaga.
     */
    public function jaga(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'jaga',
            'title' => 'Jadwal Jaga ' . uniqid(),
        ]);
    }

    /**
     * Indicate that the schedule is of type kegiatan_satuan.
     */
    public function kegiatanSatuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'kegiatan_satuan',
            'title' => 'Jadwal Kegiatan Satuan ' . uniqid(),
        ]);
    }

    /**
     * Add personnel to the schedule.
     */
    public function withPersonnel(array $userIds): static
    {
        return $this->state(fn (array $attributes) => [
            'personnel' => $userIds,
        ]);
    }

    /**
     * Set schedule with specific date range.
     */
    public function withDateRange(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Set schedule with specific time range.
     */
    public function withTimeRange(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Set schedule without time (all day event).
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => null,
            'end_time' => null,
        ]);
    }
}
