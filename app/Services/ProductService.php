<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service class for Product business logic.
 *
 * This service handles all business logic related to products,
 * keeping controllers thin and focused on HTTP concerns.
 */
class ProductService implements ProductServiceInterface
{
    /**
     * Cache TTL for light data (24 hours).
     */
    private const CACHE_TTL = 86400;

    /**
     * Get all products with filters, search, and pagination.
     *
     * @param array<string, mixed> $filters
     * @param string|null $search
     * @param string $sortBy
     * @param string $sortOrder
     * @param int $perPage
     * @param int $page
     * @param bool $isLight
     * @return Collection|LengthAwarePaginator
     */
    public function getAll(
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'DESC',
        int $perPage = 15,
        int $page = 1,
        bool $isLight = false
    ) {
        if ($isLight) {
            return $this->getLightData();
        }

        $query = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'products.id',
                'products.item_code',
                'products.name',
                'products.price',
                'products.description',
                'products.category_id',
                'products.sub_category_id',
                'products.available_status',
                'products.quantity',
                'products.created_at',
                'products.updated_at',
            ]);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.item_code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('products.price', 'like', "%{$search}%")
                    ->orWhere('products.description', 'like', "%{$search}%")
                    ->orWhere('categories.name', 'like', "%{$search}%")
                    ->orWhere('products.sub_category_id', 'like', "%{$search}%")
                    ->orWhere('products.available_status', 'like', "%{$search}%")
                    ->orWhere('products.quantity', 'like', "%{$search}%");
            });
        }

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->where('products.' . $key, '=', $value);
            }
        }

        // Apply sorting
        $query->orderBy('products.' . $sortBy, $sortOrder);

        // Eager load relationships to prevent N+1 queries
        // Using with() to load relationships efficiently
        $query->with([
            'category:id,name,status',
            'subCategory:id,name,status,category_id'
        ]);

        // Apply pagination
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get light data (cached).
     *
     * @return Collection
     */
    private function getLightData(): Collection
    {
        return Cache::remember('product.all', self::CACHE_TTL, function () {
            $product = new Product();
            /** @var array<string> $lightFields */
            $lightFields = $product->light ?? [];

            return Product::select($lightFields)->get();
        });
    }

    /**
     * Get a single product by ID.
     *
     * @param int $id
     * @return Product|null
     */
    public function getById(int $id): ?Product
    {
        return Product::with([
            'category:id,name,status',
            'subCategory:id,name,status,category_id'
        ])->find($id);
    }

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $data
     * @return Product
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            // Invalidate cache
            Cache::forget('product.all');

            // Eager load relationships
            $product->load(['category:id,name', 'subCategory:id,name']);

            return $product;
        });
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @return Product
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        // Invalidate cache
        Cache::forget('product.all');

        // Eager load relationships
        $product->load([
            'category:id,name,status',
            'subCategory:id,name,status,category_id'
        ]);

        return $product;
    }

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool
    {
        $deleted = $product->delete();

        // Invalidate cache
        if ($deleted) {
            Cache::forget('product.all');
        }

        return $deleted;
    }

    /**
     * Delete multiple products.
     *
     * @param array<int> $ids
     * @return int Number of deleted products
     */
    public function deleteMultiple(array $ids): int
    {
        $deletedCount = 0;

        $products = Product::whereIn('id', $ids)->get();

        foreach ($products as $product) {
            if ($product->delete()) {
                $deletedCount++;
            }
        }

        // Invalidate cache if any were deleted
        if ($deletedCount > 0) {
            Cache::forget('product.all');
        }

        return $deletedCount;
    }
}
