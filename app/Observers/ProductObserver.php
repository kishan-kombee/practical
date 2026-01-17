<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Product Observer
 *
 * Handles model events for Product model:
 * - Clears cache when products are modified
 * - Updates search indexes (if implemented)
 * - Note: Activity logging is handled by AuditTrail trait
 */
class ProductObserver
{
    /**
     * Handle the Product "created" event.
     *
     * @param Product $product
     * @return void
     */
    public function created(Product $product): void
    {
        $this->clearProductCache();
        $this->updateSearchIndex($product, 'created');
    }

    /**
     * Handle the Product "updated" event.
     *
     * @param Product $product
     * @return void
     */
    public function updated(Product $product): void
    {
        $this->clearProductCache();

        // If category or subcategory changed, clear related caches
        if ($product->wasChanged('category_id') || $product->wasChanged('sub_category_id')) {
            $this->clearCategoryRelatedCache($product);
        }

        $this->updateSearchIndex($product, 'updated');
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param Product $product
     * @return void
     */
    public function deleted(Product $product): void
    {
        $this->clearProductCache();
        $this->updateSearchIndex($product, 'deleted');
    }

    /**
     * Handle the Product "restored" event.
     *
     * @param Product $product
     * @return void
     */
    public function restored(Product $product): void
    {
        $this->clearProductCache();
        $this->updateSearchIndex($product, 'restored');
    }

    /**
     * Clear all product-related cache.
     *
     * @return void
     */
    private function clearProductCache(): void
    {
        Cache::forget('product.all');
    }

    /**
     * Clear category-related cache when product category changes.
     *
     * @param Product $product
     * @return void
     */
    private function clearCategoryRelatedCache(Product $product): void
    {
        // Clear subcategory cache if category changed
        if ($product->wasChanged('category_id')) {
            $oldCategoryId = $product->getOriginal('category_id');
            if ($oldCategoryId) {
                Cache::forget("getSubCategoriesByCategory:{$oldCategoryId}");
            }

            if ($product->category_id) {
                Cache::forget("getSubCategoriesByCategory:{$product->category_id}");
            }
        }

        // Clear subcategory cache if subcategory changed
        if ($product->wasChanged('sub_category_id')) {
            $oldSubCategoryId = $product->getOriginal('sub_category_id');
            if ($oldSubCategoryId) {
                $oldSubCategory = \App\Models\SubCategory::find($oldSubCategoryId);
                if ($oldSubCategory && $oldSubCategory->category_id) {
                    Cache::forget("getSubCategoriesByCategory:{$oldSubCategory->category_id}");
                }
            }

            if ($product->sub_category_id) {
                $subCategory = \App\Models\SubCategory::find($product->sub_category_id);
                if ($subCategory && $subCategory->category_id) {
                    Cache::forget("getSubCategoriesByCategory:{$subCategory->category_id}");
                }
            }
        }
    }

    /**
     * Update search index for the product.
     *
     * This is a placeholder for future search implementation.
     * You can integrate with Laravel Scout, Elasticsearch, Algolia, etc.
     *
     * @param Product $product
     * @param string $event
     * @return void
     */
    private function updateSearchIndex(Product $product, string $event): void
    {
        // Placeholder for search index updates
        // Example implementations:

        // If using Laravel Scout:
        // if ($event === 'deleted') {
        //     $product->unsearchable();
        // } else {
        //     $product->searchable();
        // }

        // If using custom search service:
        // app(SearchService::class)->indexProduct($product, $event);

        // For now, we'll just log that search index should be updated
        // You can implement this based on your search solution
    }
}
