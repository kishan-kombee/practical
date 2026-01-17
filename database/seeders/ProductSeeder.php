<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active sub categories with their categories
        $subCategories = SubCategory::where('status', '1')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->where('status', '1');
            })
            ->get();

        if ($subCategories->isEmpty()) {
            $this->command->warn('No active sub categories found. Please run CategorySeeder and SubCategorySeeder first.');
            return;
        }

        // Create products for each sub category
        foreach ($subCategories as $subCategory) {
            // Create 5-10 products for each sub category
            $productCount = rand(5, 10);

            Product::factory()
                ->count($productCount)
                ->forCategoryAndSubCategory($subCategory->category_id, $subCategory->id)
                ->available()
                ->create();
        }

        // Create some products with different statuses (using random active sub categories)
        $randomSubCategories = SubCategory::where('status', '1')
            ->whereHas('category', function ($query) {
                $query->where('status', '1');
            })
            ->inRandomOrder()
            ->limit(10)
            ->get();

        foreach ($randomSubCategories as $subCategory) {
            Product::factory()
                ->forCategoryAndSubCategory($subCategory->category_id, $subCategory->id)
                ->notAvailable()
                ->create();
        }

        // Create low stock products
        $randomSubCategories = SubCategory::where('status', '1')
            ->whereHas('category', function ($query) {
                $query->where('status', '1');
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($randomSubCategories as $subCategory) {
            Product::factory()
                ->forCategoryAndSubCategory($subCategory->category_id, $subCategory->id)
                ->lowStock()
                ->create();
        }

        // Create high stock products
        $randomSubCategories = SubCategory::where('status', '1')
            ->whereHas('category', function ($query) {
                $query->where('status', '1');
            })
            ->inRandomOrder()
            ->limit(5)
            ->get();

        foreach ($randomSubCategories as $subCategory) {
            Product::factory()
                ->forCategoryAndSubCategory($subCategory->category_id, $subCategory->id)
                ->highStock()
                ->create();
        }
    }
}
