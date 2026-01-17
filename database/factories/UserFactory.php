<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'mobile_number' => $this->faker->numerify('6#########'), // 10-digit mobile starting with 6
            'password' => Hash::make('password'), // Default password: 'password'
            'status' => $this->faker->randomElement(['Y', 'N']),
            'last_login_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'locale' => $this->faker->randomElement(['en', 'hi', 'ar']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'Y',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'N',
        ]);
    }

    /**
     * Indicate that the user has logged in recently.
     */
    public function recentlyLoggedIn(): static
    {
        return $this->state(fn(array $attributes) => [
            'last_login_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
