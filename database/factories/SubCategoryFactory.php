<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubCategory>
 */
class SubCategoryFactory extends Factory
{
    protected $model = SubCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a unique subcategory name using faker
        // Using words() to create more variety and avoid running out of unique values
        $name = $this->faker->words(2, true); // e.g., "Modern Electronics", "Premium Accessories"

        // Capitalize first letter of each word
        $name = ucwords($name);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'status' => $this->faker->randomElement(['0', '1']),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the sub category is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => '1',
        ]);
    }

    /**
     * Indicate that the sub category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => '0',
        ]);
    }

    /**
     * Set the category for this sub category.
     */
    public function forCategory(int $categoryId): static
    {
        return $this->state(fn(array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
