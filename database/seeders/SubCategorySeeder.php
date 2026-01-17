<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active categories
        $categories = Category::where('status', '1')->get();

        if ($categories->isEmpty()) {
            $this->command->warn('No active categories found. Please run CategorySeeder first.');
            return;
        }

        // Create 3-5 sub categories for each active category
        foreach ($categories as $category) {
            $subCategoryCount = rand(3, 5);

            SubCategory::factory()
                ->count($subCategoryCount)
                ->forCategory($category->id)
                ->active()
                ->create();
        }

        // Create a few inactive sub categories (randomly assign to any category)
        $randomCategory = Category::inRandomOrder()->first();
        if ($randomCategory) {
            SubCategory::factory()
                ->count(5)
                ->forCategory($randomCategory->id)
                ->inactive()
                ->create();
        }
    }
}
