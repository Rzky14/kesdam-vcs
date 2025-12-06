<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'manage'];
        $modules = ['users', 'documents', 'schedules', 'reports', 'system'];

        $action = $this->faker->randomElement($actions);
        $module = $this->faker->randomElement($modules);

        return [
            'name' => $action . '_' . $module . '_' . $this->faker->unique()->numberBetween(1, 1000),
            'display_name' => ucfirst($action) . ' ' . ucfirst($module),
            'module' => $module,
            'description' => 'Can ' . $action . ' ' . $module,
        ];
    }
}
