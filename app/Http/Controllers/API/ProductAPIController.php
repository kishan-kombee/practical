<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\DataTrueResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesApiFilters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Product API Controller.
 *
 * This controller handles the products API endpoints:
 * - index: List products with filters, search, and pagination
 * - show: Get a single product
 * - store: Create a new product
 * - update: Update an existing product
 * - destroy: Delete a product
 * - deleteAll: Delete multiple products
 *
 * @package App\Http\Controllers\API
 */
class ProductAPIController extends Controller
{
    use ApiResponseTrait;
    use HandlesApiFilters;

    /**
     * Create a new controller instance.
     *
     * @param ProductService $service
     */
    public function __construct(
        private ProductService $service
    ) {
    }
    /**
     * List products with filters, search, and pagination.
     *
     * @param Request $request
     * @return ProductCollection
     */
    public function index(Request $request): ProductCollection
    {
        $isLight = $request->get('is_light', false);
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'DESC');
        $perPage = $request->get('per_page', config('constants.apiPerPage', 15));
        $page = $request->get('page', config('constants.apiPage', 1));

        // Get filters from request using trait method
        $filters = $this->parseFilters($request);

        // Get data from service
        $result = $this->service->getAll(
            $filters,
            $search,
            $sortBy,
            $sortOrder,
            (int) $perPage,
            (int) $page,
            $isLight
        );

        return new ProductCollection(ProductResource::collection($result), ProductResource::class);
    }

    /**
     * Get a single product by ID.
     *
     * @param Product $product
     * @return ProductCollection
     */
    public function show(Product $product): ProductCollection
    {
        // Use Policy to check authorization
        Gate::authorize('view', $product);

        // Get product with eager loaded relationships from service
        $product = $this->service->getById($product->id);

        if (!$product) {
            return new ProductCollection(
                ProductResource::collection([]),
                ProductResource::class
            );
        }

        return new ProductCollection(
            ProductResource::collection([$product]),
            ProductResource::class
        );
    }

    /**
     * Create a new product.
     *
     * @param ProductRequest $request
     * @return ProductCollection
     */
    public function store(ProductRequest $request): ProductCollection
    {
        // Use Policy to check if user can create products
        Gate::authorize('create', Product::class);

        // Create product using service
        $product = $this->service->create($request->validated());

        return new ProductCollection(
            ProductResource::collection([$product]),
            ProductResource::class,
            trans('messages.api.create_success', ['model' => 'Product'])
        );
    }

    /**
     * Update an existing product.
     *
     * @param ProductUpdateRequest $request
     * @param int|string $id
     * @return ProductCollection
     */
    public function update(ProductUpdateRequest $request, $id): ProductCollection
    {
        $product = Product::findOrFail($id);

        // Use Policy to check authorization
        Gate::authorize('update', $product);

        // Update product using service
        $product = $this->service->update($product, $request->validated());

        return new ProductCollection(
            ProductResource::collection([$product]),
            ProductResource::class,
            trans('messages.api.update_success', ['model' => 'Product'])
        );
    }

    /**
     * Delete a product.
     *
     * @param Request $request
     * @param Product $product
     * @return DataTrueResource
     */
    public function destroy(Request $request, Product $product): DataTrueResource
    {
        // Use Policy to check authorization
        Gate::authorize('delete', $product);

        // Delete product using service
        $this->service->delete($product);

        return new DataTrueResource($product, trans('messages.api.delete_success', ['model' => 'Product']));
    }

    /**
     * Delete multiple products.
     *
     * @param Request $request
     * @return DataTrueResource|JsonResponse
     */
    public function deleteAll(Request $request): DataTrueResource|JsonResponse
    {
        /** @var array<int>|null $ids */
        $ids = $request->ids ?? null;

        if (empty($ids) || !is_array($ids)) {
            return $this->errorResponse(
                trans('messages.api.delete_multiple_error'),
                null,
                config('constants.validation_codes.unprocessable_entity')
            );
        }

        // Check authorization for each product before deletion
        $user = $request->user();
        if ($user) {
            $products = Product::whereIn('id', $ids)->get();
            foreach ($products as $product) {
                if (!$user->can('delete', $product)) {
                    return $this->forbiddenResponse(__('messages.api.forbidden'));
                }
            }
        }

        // Delete products using service
        $deletedCount = $this->service->deleteMultiple($ids);

        if ($deletedCount > 0) {
            return new DataTrueResource(
                true,
                trans('messages.api.delete_multiple_success', ['models' => Str::plural('Product')])
            );
        }

        return $this->errorResponse(
            trans('messages.api.delete_multiple_error'),
            null,
            config('constants.validation_codes.unprocessable_entity')
        );
    }
}
