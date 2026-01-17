<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Category Observer
 *
 * Handles model events for Category model:
 * - Clears cache when categories are modified
 * - Updates search indexes (if implemented)
 * - Note: Activity logging is handled by AuditTrail trait
 */
class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     *
     * @param Category $category
     * @return void
     */
    public function created(Category $category): void
    {
        $this->clearCategoryCache();
        $this->updateSearchIndex($category, 'created');
    }

    /**
     * Handle the Category "updated" event.
     *
     * @param Category $category
     * @return void
     */
    public function updated(Category $category): void
    {
        $this->clearCategoryCache();
        
        // Clear subcategory cache if status changed
        if ($category->wasChanged('status')) {
            $this->clearSubCategoryCache();
        }
        
        $this->updateSearchIndex($category, 'updated');
    }

    /**
     * Handle the Category "deleted" event.
     *
     * @param Category $category
     * @return void
     */
    public function deleted(Category $category): void
    {
        $this->clearCategoryCache();
        $this->clearSubCategoryCache();
        
        // Clear subcategory cache for this specific category
        Cache::forget("getSubCategoriesByCategory:{$category->id}");
        
        $this->updateSearchIndex($category, 'deleted');
    }

    /**
     * Handle the Category "restored" event.
     *
     * @param Category $category
     * @return void
     */
    public function restored(Category $category): void
    {
        $this->clearCategoryCache();
        $this->clearSubCategoryCache();
        $this->updateSearchIndex($category, 'restored');
    }

    /**
     * Clear all category-related cache.
     *
     * @return void
     */
    private function clearCategoryCache(): void
    {
        Cache::forget('getAllCategory');
        Cache::forget('getAllActiveCategory');
    }

    /**
     * Clear all subcategory-related cache.
     *
     * @return void
     */
    private function clearSubCategoryCache(): void
    {
        Cache::forget('getAllActiveSubCategory');
        
        // Clear all subcategory by category caches
        // Note: This is a simple approach. For better performance with many categories,
        // consider using cache tags if your cache driver supports it.
        $categories = Category::pluck('id');
        foreach ($categories as $categoryId) {
            Cache::forget("getSubCategoriesByCategory:{$categoryId}");
        }
    }

    /**
     * Update search index for the category.
     *
     * This is a placeholder for future search implementation.
     * You can integrate with Laravel Scout, Elasticsearch, Algolia, etc.
     *
     * @param Category $category
     * @param string $event
     * @return void
     */
    private function updateSearchIndex(Category $category, string $event): void
    {
        // Placeholder for search index updates
        // Example implementations:
        
        // If using Laravel Scout:
        // if ($event === 'deleted') {
        //     $category->unsearchable();
        // } else {
        //     $category->searchable();
        // }
        
        // If using custom search service:
        // app(SearchService::class)->indexCategory($category, $event);
        
        // For now, we'll just log that search index should be updated
        // You can implement this based on your search solution
    }
}
