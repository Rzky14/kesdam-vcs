<?php

namespace Database\Factories;

use App\Models\CorrectionRequest;
use App\Models\Dokumen;
use App\Models\ApprovalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory PermintaanKoreksi
 *
 * Membuat data dummy untuk PermintaanKoreksi sesuai kebutuhan pengujian.
 * Menyediakan state untuk berbagai status (menunggu, diproses, selesai, ditolak, terlambat).
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CorrectionRequest>
 */
class CorrectionRequestFactory extends Factory
{
    protected $model = CorrectionRequest::class;

    /**
     * State dasar permintaan koreksi.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Dokumen::factory(),
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
     * State: menunggu koreksi.
     */
    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'corrected_at' => null,
        ]);
    }

    /**
     * State: sedang diproses.
     */
    public function sedangDiproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'corrected_at' => null,
        ]);
    }

    /**
     * State: koreksi selesai.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'corrected_at' => now(),
        ]);
    }

    /**
     * State: koreksi ditolak.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'corrected_at' => now(),
        ]);
    }

    /**
     * State: melewati batas waktu (terlambat).
     */
    public function terlambat(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'due_date' => now()->subDays(1),
            'corrected_at' => null,
        ]);
    }
}
