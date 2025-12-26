<?php

namespace Database\Factories;

use App\Models\Arsip;
use App\Models\Dokumen;
use App\Models\Schedule;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<App\Models\Arsip>
 */
class ArchiveFactory extends Factory
{
    protected $model = Arsip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $archivedAt = $this->faker->dateTimeBetween('-6 months', 'now');
        $retentionYears = $this->faker->randomElement([1, 2, 3, 5, 10]);
        $retentionUntil = (new \DateTime($archivedAt->format('Y-m-d')))->modify("+{$retentionYears} years");

        // Random archiveable type
        $archiveableType = $this->faker->randomElement([
            'App\\Models\\Dokumen',
            'App\\Models\\Schedule',
            'App\\Models\\Report',
        ]);

        return [
            'archiveable_type' => $archiveableType,
            'archiveable_id' => 1, // Will be set by states
            'archive_date' => $archivedAt,
            'retention_until' => $retentionUntil,
            'category' => $this->faker->randomElement(['dokumen', 'jadwal', 'laporan', 'administratif', 'operasional']),
            'tags' => json_encode([$this->faker->word(), $this->faker->word()]),
            'is_indexed' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Menandai arsip untuk dokumen.
     */
    public function forDocument(): static
    {
        return $this->state(fn (array $attributes) => [
            'archiveable_type' => 'App\\Models\\Dokumen',
            'archiveable_id' => Dokumen::factory(),
            'category' => 'dokumen',
            'tags' => json_encode(['dokumen', 'surat', $this->faker->randomElement(['masuk', 'keluar'])]),
        ]);
    }

    /**
     * Indicate archive is for a schedule
     */
    public function forSchedule(): static
    {
        return $this->state(fn (array $attributes) => [
            'archiveable_type' => 'App\\Models\\Schedule',
            'archiveable_id' => Schedule::factory(),
            'category' => 'jadwal',
            'tags' => json_encode(['jadwal', $this->faker->randomElement(['dukkes', 'jaga', 'kegiatan_satuan'])]),
        ]);
    }

    /**
     * Indicate archive is for a report
     */
    public function forReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'archiveable_type' => 'App\\Models\\Report',
            'archiveable_id' => Report::factory(),
            'category' => 'laporan',
            'tags' => json_encode(['laporan', 'report', $this->faker->randomElement(['bulanan', 'tahunan'])]),
        ]);
    }

    /**
     * Indicate archive is indexed
     */
    public function indexed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_indexed' => true,
        ]);
    }

    /**
     * Indicate archive is not indexed
     */
    public function notIndexed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_indexed' => false,
        ]);
    }

    /**
     * Indicate archive retention is expiring soon (within 30 days)
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_until' => now()->addDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate archive retention has expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_until' => now()->subDays($this->faker->numberBetween(1, 365)),
        ]);
    }

    /**
     * Indicate archive has long retention (10 years)
     */
    public function longRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_until' => now()->addYears(10),
        ]);
    }

    /**
     * Set specific category
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Add specific tags
     */
    public function withTags(array $tags): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => json_encode($tags),
        ]);
    }
}
