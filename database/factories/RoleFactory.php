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
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'status' => $this->faker->randomElement(['Y', 'N']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the role is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'Y',
        ]);
    }

    /**
     * Indicate that the role is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'N',
        ]);
    }
}
