<?php

namespace App\Models;

use App\Traits\AuditTrail;
use App\Traits\CommonTrait;
use App\Traits\CreatedbyUpdatedby;
use App\Traits\ImportTrait;
use App\Traits\Legendable;
use App\Traits\UploadTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Product Model
 *
 * Represents a product in the system with attributes such as item code, name, price,
 * description, category associations, availability status, and quantity.
 *
 * @property int $id
 * @property string|null $item_code
 * @property string|null $name
 * @property float|null $price
 * @property string|null $description
 * @property int|null $category_id
 * @property int|null $sub_category_id
 * @property string|null $available_status
 * @property int|null $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\SubCategory|null $subCategory
 */
class Product extends Model
{
    use AuditTrail;
    use CommonTrait;
    use CreatedbyUpdatedby;
    use HasFactory;
    use ImportTrait;
    use Legendable;
    use SoftDeletes;
    use UploadTrait;

    /**
     * Lightweight fields for minimal data retrieval.
     *
     * @var array<string>
     */
    public $light = ['id', 'item_code', 'name', 'price', 'description'];

    /**
     * Fields to track in activity logs.
     *
     * @var array<string>
     */
    public $activity_log = ['id', 'item_code', 'name', 'price', 'description', 'available_status', 'quantity'];

    /**
     * Related models to include in log tracking.
     *
     * @var array<string>
     */
    public $log_relations = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['item_code', 'name', 'price', 'description', 'category_id', 'sub_category_id', 'available_status', 'quantity'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_code' => 'string',
        'name' => 'string',
        'price' => 'decimal:2',
        'description' => 'string',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'available_status' => 'string',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get available status options as a collection.
     *
     * Returns a collection of available status options with key-value pairs
     * for use in dropdowns, filters, and form selections.
     *
     * @return \Illuminate\Support\Collection<int, array<string, string>>
     */
    public static function available_status(): Collection
    {
        return collect(
            [['key' => '0', 'label' => 'Not-available'], ['key' => '1', 'label' => 'Available']]
        );
    }

    /**
     * Get the category that owns the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, self>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the sub category that owns the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SubCategory, self>
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * Scope a query to only include available products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('available_status', '1');
    }

    /**
     * Scope a query to only include not available products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotAvailable(Builder $query): Builder
    {
        return $query->where('available_status', '0');
    }

    /**
     * Scope a query to only include products with stock.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to only include out of stock products.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope a query to filter products by category.
     *
     * @param Builder $query
     * @param int $categoryId
     * @return Builder
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter products by sub category.
     *
     * @param Builder $query
     * @param int $subCategoryId
     * @return Builder
     */
    public function scopeBySubCategory(Builder $query, int $subCategoryId): Builder
    {
        return $query->where('sub_category_id', $subCategoryId);
    }

    /**
     * Scope a query to filter products by price range.
     *
     * @param Builder $query
     * @param float $minPrice
     * @param float|null $maxPrice
     * @return Builder
     */
    public function scopeByPriceRange(Builder $query, float $minPrice, ?float $maxPrice = null): Builder
    {
        $query->where('price', '>=', $minPrice);

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Get the formatted price attribute.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price === null) {
            return '0.00';
        }

        return number_format((float) $this->price, 2, '.', ',');
    }

    /**
     * Get the status label attribute.
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        $statusOptions = self::available_status();
        $status = $statusOptions->firstWhere('key', $this->available_status);

        return $status ? $status['label'] : 'Unknown';
    }

    /**
     * Get the availability status label attribute.
     *
     * @return string
     */
    public function getAvailableStatusLabelAttribute(): string
    {
        return $this->status_label;
    }

    /**
     * Set the price attribute with proper formatting.
     *
     * @param mixed $value
     * @return void
     */
    public function setPriceAttribute($value): void
    {
        // Ensure price is stored as decimal with 2 decimal places
        $this->attributes['price'] = $value !== null ? number_format((float) $value, 2, '.', '') : null;
    }
}
