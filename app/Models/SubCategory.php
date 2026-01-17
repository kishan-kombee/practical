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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * SubCategory Model
 *
 * Represents a product subcategory in the system with attributes such as category association, name, and status.
 *
 * @property int $id
 * @property int|null $category_id
 * @property string|null $name
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 */
class SubCategory extends Model
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
    public $light = ['id', 'name', 'status'];

    /**
     * Fields to track in activity logs.
     *
     * @var array<string>
     */
    public $activity_log = ['id', 'name', 'status'];

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
    protected $fillable = ['category_id', 'name', 'status'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category_id' => 'integer',
        'name' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get status options as a collection.
     *
     * Returns a collection of status options with key-value pairs
     * for use in dropdowns, filters, and form selections.
     *
     * @return \Illuminate\Support\Collection<int, array<string, string>>
     */
    public static function status(): Collection
    {
        return collect(
            [['key' => '0', 'label' => 'Inactive'], ['key' => '1', 'label' => 'Active']]
        );
    }

    /**
     * Get the category that owns the sub category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, self>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the products for the sub category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active sub categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '1');
    }

    /**
     * Scope a query to only include inactive sub categories.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', '0');
    }

    /**
     * Scope a query to filter sub categories by category.
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
     * Scope a query to filter sub categories by name.
     *
     * @param Builder $query
     * @param string $name
     * @return Builder
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
