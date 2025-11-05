<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = [
            ['name' => 'admin_sistem', 'display_name' => 'Administrator Sistem', 'description' => 'Full system access'],
            ['name' => 'pimpinan', 'display_name' => 'Pimpinan/Pejabat Tinggi', 'description' => 'Leadership role'],
            ['name' => 'kasi_kaur', 'display_name' => 'Kasi/Kaur', 'description' => 'Mid-level management'],
            ['name' => 'batih_staf', 'display_name' => 'Batih/Staf', 'description' => 'Staff role'],
        ];

        $role = $this->faker->randomElement($roles);

        return [
            'name' => $role['name'] . '_' . $this->faker->unique()->numberBetween(1, 1000),
            'display_name' => $role['display_name'],
            'description' => $role['description'],
        ];
    }
}
