<?php

namespace App\Services\Export;

use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubCategoryExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = SubCategory::query()

            ->leftJoin('categories', 'categories.id', '=', 'sub_categories.category_id')
            ->select([
                'sub_categories.id',
                'categories.name as categories_name',
                'sub_categories.name',
                DB::raw(
                    '(CASE
                        WHEN sub_categories.status = "' . config('constants.sub_category.status.key.inactive') . '" THEN  "' . config('constants.sub_category.status.value.inactive') . '"
                        WHEN sub_categories.status = "' . config('constants.sub_category.status.key.active') . '" THEN  "' . config('constants.sub_category.status.value.active') . '"
                        ELSE " "
                    END) AS status'
                ),
            ])
            ->whereNull('sub_categories.deleted_at')
            ->groupBy('sub_categories.id');

        // Apply name filters
        if (isset($filters['input_text']['subcategories']['name']) && $filters['input_text']['subcategories']['name']) {
            $query->where('sub_categories.name', 'like', '%' . $filters['input_text']['subcategories']['name'] . '%');
        }

        // Apply status filters
        if (isset($filters['select']['subcategories']['status']) && $filters['select']['subcategories']['status']) {
            $query->where('sub_categories.status', $filters['select']['subcategories']['status']);
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('sub_categories.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('sub_categories.name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sub_categories.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->id ?? '',
            $row->categories_name ?? '',
            $row->name ?? '',
            $row->status ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Id","Category Id","Name","Status"';
    }

    public function getFilenamePrefix(): string
    {
        return 'SubCategoryReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-subcategory');
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
