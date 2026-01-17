<?php

namespace App\Observers;

use App\Models\SubCategory;
use Illuminate\Support\Facades\Cache;

/**
 * SubCategory Observer
 *
 * Handles model events for SubCategory model:
 * - Clears cache when subcategories are modified
 * - Updates search indexes (if implemented)
 * - Note: Activity logging is handled by AuditTrail trait
 */
class SubCategoryObserver
{
    /**
     * Handle the SubCategory "created" event.
     *
     * @param SubCategory $subCategory
     * @return void
     */
    public function created(SubCategory $subCategory): void
    {
        $this->clearSubCategoryCache($subCategory);
        $this->updateSearchIndex($subCategory, 'created');
    }

    /**
     * Handle the SubCategory "updated" event.
     *
     * @param SubCategory $subCategory
     * @return void
     */
    public function updated(SubCategory $subCategory): void
    {
        $this->clearSubCategoryCache($subCategory);
        
        // If category_id changed, clear cache for both old and new categories
        if ($subCategory->wasChanged('category_id')) {
            $oldCategoryId = $subCategory->getOriginal('category_id');
            if ($oldCategoryId) {
                Cache::forget("getSubCategoriesByCategory:{$oldCategoryId}");
            }
        }
        
        $this->updateSearchIndex($subCategory, 'updated');
    }

    /**
     * Handle the SubCategory "deleted" event.
     *
     * @param SubCategory $subCategory
     * @return void
     */
    public function deleted(SubCategory $subCategory): void
    {
        $this->clearSubCategoryCache($subCategory);
        $this->updateSearchIndex($subCategory, 'deleted');
    }

    /**
     * Handle the SubCategory "restored" event.
     *
     * @param SubCategory $subCategory
     * @return void
     */
    public function restored(SubCategory $subCategory): void
    {
        $this->clearSubCategoryCache($subCategory);
        $this->updateSearchIndex($subCategory, 'restored');
    }

    /**
     * Clear all subcategory-related cache.
     *
     * @param SubCategory $subCategory
     * @return void
     */
    private function clearSubCategoryCache(SubCategory $subCategory): void
    {
        Cache::forget('getAllActiveSubCategory');
        
        // Clear cache for the specific category's subcategories
        if ($subCategory->category_id) {
            Cache::forget("getSubCategoriesByCategory:{$subCategory->category_id}");
        }
    }

    /**
     * Update search index for the subcategory.
     *
     * This is a placeholder for future search implementation.
     * You can integrate with Laravel Scout, Elasticsearch, Algolia, etc.
     *
     * @param SubCategory $subCategory
     * @param string $event
     * @return void
     */
    private function updateSearchIndex(SubCategory $subCategory, string $event): void
    {
        // Placeholder for search index updates
        // Example implementations:
        
        // If using Laravel Scout:
        // if ($event === 'deleted') {
        //     $subCategory->unsearchable();
        // } else {
        //     $subCategory->searchable();
        // }
        
        // If using custom search service:
        // app(SearchService::class)->indexSubCategory($subCategory, $event);
        
        // For now, we'll just log that search index should be updated
        // You can implement this based on your search solution
    }
}
