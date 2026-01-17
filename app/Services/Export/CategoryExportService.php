<?php

namespace App\Services\Export;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CategoryExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = Category::query()

            ->select([
                'categories.id',
                'categories.name',
                DB::raw(
                    '(CASE
                        WHEN categories.status = "' . config('constants.category.status.key.inactive') . '" THEN  "' . config('constants.category.status.value.inactive') . '"
                        WHEN categories.status = "' . config('constants.category.status.key.active') . '" THEN  "' . config('constants.category.status.value.active') . '"
                        ELSE " "
                    END) AS status'
                ),
            ])
            ->whereNull('categories.deleted_at')
            ->groupBy('categories.id');

        // Apply name filters
        if (isset($filters['input_text']['categories']['name']) && $filters['input_text']['categories']['name']) {
            $query->where('categories.name', 'like', '%' . $filters['input_text']['categories']['name'] . '%');
        }

        // Apply status filters
        if (isset($filters['select']['categories']['status']) && $filters['select']['categories']['status']) {
            $query->where('categories.status', $filters['select']['categories']['status']);
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('categories.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('categories.name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('categories.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->id ?? '',
            $row->name ?? '',
            $row->status ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Id","Name","Status"';
    }

    public function getFilenamePrefix(): string
    {
        return 'CategoryReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-category');
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
