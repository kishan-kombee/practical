<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create active categories (most should be active)
        Category::factory()
            ->count(10)
            ->active()
            ->create();

        // Create a few inactive categories
        Category::factory()
            ->count(2)
            ->inactive()
            ->create();
    }
}
