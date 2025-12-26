<?php

namespace Database\Factories;

use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dokumen>
 */
class DocumentFactory extends Factory
{
     /**
      * Model terkait factory.
      *
      * @var string
      */
     protected $model = Dokumen::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['masuk', 'keluar']);
        $classification = fake()->randomElement(['biasa', 'rahasia', 'telegram']);
        
        return [
            'type' => $type,
            'classification' => $classification,
            'number' => $this->generateDocumentNumber($type, $classification),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'sender' => $type === 'masuk' ? fake()->company() : null,
            'recipient' => $type === 'keluar' ? fake()->company() : null,
            'subject' => fake()->sentence(8),
            'description' => fake()->optional()->paragraph(3),
            'attachments' => fake()->optional()->randomElements(
                ['file1.pdf', 'file2.pdf', 'document.docx', 'image.jpg'],
                fake()->numberBetween(0, 3)
            ),
            'status' => 'draft',
            'priority' => fake()->randomElement(['normal', 'high', 'urgent']),
            'is_encrypted' => $classification === 'rahasia',
            'archived_at' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    /**
     * Generate document number based on type and classification.
     *
     * @param  string  $type
     * @param  string  $classification
     * @return string
     */
    private function generateDocumentNumber($type, $classification)
    {
        $prefix = match($type) {
            'masuk' => 'SM',
            'keluar' => 'SK',
            default => 'DOC',
        };
        
        $classPrefix = match($classification) {
            'rahasia' => 'R',
            'telegram' => 'T',
            default => '',
        };
        
        $number = fake()->unique()->numberBetween(1000, 9999);
        $year = date('Y');
        
        return $classPrefix ? "{$prefix}-{$classPrefix}/{$number}/{$year}" : "{$prefix}/{$number}/{$year}";
    }

    /**
     * Indicate that the document is incoming (surat masuk).
     */
    public function incoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'masuk',
            'sender' => fake()->company(),
            'recipient' => null,
            'number' => $this->generateDocumentNumber('masuk', $attributes['classification']),
        ]);
    }

    /**
     * Indicate that the document is outgoing (surat keluar).
     */
    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'keluar',
            'sender' => null,
            'recipient' => fake()->company(),
            'number' => $this->generateDocumentNumber('keluar', $attributes['classification']),
        ]);
    }

    /**
     * Indicate that the document is classified as biasa.
     */
    public function biasa(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'biasa',
            'is_encrypted' => false,
            'number' => $this->generateDocumentNumber($attributes['type'], 'biasa'),
        ]);
    }

    /**
     * Indicate that the document is classified as rahasia.
     */
    public function rahasia(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'rahasia',
            'is_encrypted' => true,
            'number' => $this->generateDocumentNumber($attributes['type'], 'rahasia'),
        ]);
    }

    /**
     * Indicate that the document is classified as telegram.
     */
    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'telegram',
            'is_encrypted' => false,
            'number' => $this->generateDocumentNumber($attributes['type'], 'telegram'),
        ]);
    }

    /**
     * Indicate that the document is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the document is pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_approval',
        ]);
    }

    /**
     * Indicate that the document is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the document is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the document is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'archived_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the document has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the document has urgent priority.
     */
    public function urgentPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the document has attachments.
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => fake()->randomElements(
                ['document1.pdf', 'document2.pdf', 'file.docx', 'image.jpg', 'scan.pdf'],
                fake()->numberBetween(1, 4)
            ),
        ]);
    }
}
