<?php

namespace App\Services\Export;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProductExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = Product::query()

            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->select([
                'products.id',
                'products.item_code',
                'products.name',
                'products.price',
                'products.description',
                'categories.name as categories_name',
                'sub_categories.name as sub_categories_name',
                DB::raw(
                    '(CASE
                        WHEN products.available_status = "' . config('constants.product.available_status.key.not-available') . '" THEN  "' . config('constants.product.available_status.value.not-available') . '"
                        WHEN products.available_status = "' . config('constants.product.available_status.key.available') . '" THEN  "' . config('constants.product.available_status.value.available') . '"
                        ELSE " "
                    END) AS available_status'
                ),
                'products.quantity',
            ])
            ->whereNull('products.deleted_at')
            ->groupBy('products.id');

        // Apply item_code filters
        if (isset($filters['input_text']['products']['item_code']) && $filters['input_text']['products']['item_code']) {
            $query->where('products.item_code', 'like', '%' . $filters['input_text']['products']['item_code'] . '%');
        }

        // Apply name filters
        if (isset($filters['input_text']['products']['name']) && $filters['input_text']['products']['name']) {
            $query->where('products.name', 'like', '%' . $filters['input_text']['products']['name'] . '%');
        }

        // Apply price filters
        if (isset($filters['input_text']['products']['price']) && $filters['input_text']['products']['price']) {
            $query->where('products.price', 'like', '%' . $filters['input_text']['products']['price'] . '%');
        }

        // Apply description filters
        if (isset($filters['input_text']['products']['description']) && $filters['input_text']['products']['description']) {
            $query->where('products.description', 'like', '%' . $filters['input_text']['products']['description'] . '%');
        }

        // Apply available_status filters
        if (isset($filters['select']['products']['available_status']) && $filters['select']['products']['available_status']) {
            $query->where('products.available_status', $filters['select']['products']['available_status']);
        }

        // Apply quantity filters
        if (isset($filters['input_text']['products']['quantity']) && $filters['input_text']['products']['quantity']) {
            $query->where('products.quantity', 'like', '%' . $filters['input_text']['products']['quantity'] . '%');
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('products.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('products.item_code', 'like', "%{$search}%");
                $q->orWhere('products.name', 'like', "%{$search}%");
                $q->orWhere('products.price', 'like', "%{$search}%");
                $q->orWhere('products.description', 'like', "%{$search}%");
                $q->orWhere('products.quantity', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('products.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->id ?? '',
            $row->item_code ?? '',
            $row->name ?? '',
            $row->price ?? '',
            $row->description ?? '',
            $row->categories_name ?? '',
            $row->sub_categories_name ?? '',
            $row->available_status ?? '',
            $row->quantity ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Id","Item Code","Name","Price","Description","Category Id","Sub Category Id","Available Status","Quantity"';
    }

    public function getFilenamePrefix(): string
    {
        return 'ProductReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-product');
    }

    /**
     * Wrap value in quotes for CSV compatibility
     */
    private function wrapInQuotes($value): string
    {
        $value = (string) ($value ?? '');

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
