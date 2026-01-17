<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_code' => strtoupper($this->faker->unique()->bothify('PRD-####-??')), // e.g., PRD-1234-AB
            'name' => $this->faker->words(3, true), // 3 random words
            'price' => $this->faker->randomFloat(2, 10, 10000), // Price between 10 and 10000
            'description' => $this->faker->paragraph(3), // 3 sentences
            'category_id' => Category::factory(),
            'sub_category_id' => SubCategory::factory(),
            'available_status' => $this->faker->randomElement(['0', '1']),
            'quantity' => $this->faker->numberBetween(0, 1000),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the product is available.
     */
    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'available_status' => '1',
            'quantity' => $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Indicate that the product is not available.
     */
    public function notAvailable(): static
    {
        return $this->state(fn(array $attributes) => [
            'available_status' => '0',
            'quantity' => 0,
        ]);
    }

    /**
     * Set the category and sub category for this product.
     */
    public function forCategoryAndSubCategory(int $categoryId, int $subCategoryId): static
    {
        return $this->state(fn(array $attributes) => [
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
        ]);
    }

    /**
     * Set low stock quantity.
     */
    public function lowStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Set high stock quantity.
     */
    public function highStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => $this->faker->numberBetween(500, 1000),
        ]);
    }
}
