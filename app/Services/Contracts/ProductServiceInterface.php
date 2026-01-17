<?php

namespace App\Services\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product Service Interface
 *
 * Defines the contract for Product service implementations.
 * This allows for easy swapping of implementations and better testing.
 */
interface ProductServiceInterface
{
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
    );

    /**
     * Get a single product by ID.
     *
     * @param int $id
     * @return Product|null
     */
    public function getById(int $id): ?Product;

    /**
     * Create a new product.
     *
     * @param array<string, mixed> $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @return Product
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool;

    /**
     * Delete multiple products.
     *
     * @param array<int> $ids
     * @return int Number of deleted products
     */
    public function deleteMultiple(array $ids): int;
}
