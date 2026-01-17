<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Food & Beverages',
            'Books',
            'Home & Garden',
            'Sports & Outdoors',
            'Toys & Games',
            'Health & Beauty',
            'Automotive',
            'Furniture',
            'Jewelry',
            'Musical Instruments',
            'Pet Supplies',
            'Office Supplies',
            'Baby Products',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'status' => $this->faker->randomElement(['0', '1']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => '1',
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => '0',
        ]);
    }
}
