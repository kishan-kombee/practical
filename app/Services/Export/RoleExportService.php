<?php

namespace App\Services\Export;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoleExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = Role::query()

            ->select([
                'roles.id',
                'roles.name',
                DB::raw(
                    '(CASE
                        WHEN roles.status = "' . config('constants.role.status.key.active') . '" THEN  "' . config('constants.role.status.value.active') . '"
                        WHEN roles.status = "' . config('constants.role.status.key.inactive') . '" THEN  "' . config('constants.role.status.value.inactive') . '"
                        ELSE " "
                    END) AS status'
                ),
            ])
            ->whereNull('roles.deleted_at')
            ->groupBy('roles.id');

        // Apply name filters
        if (isset($filters['input_text']['roles']['name']) && $filters['input_text']['roles']['name']) {
            $query->where('roles.name', 'like', '%' . $filters['input_text']['roles']['name'] . '%');
        }

        // Apply status filters
        if (isset($filters['select']['roles']['status']) && $filters['select']['roles']['status']) {
            $query->where('roles.status', $filters['select']['roles']['status']);
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('roles.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('roles.name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('roles.id', 'desc');
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
        return 'RoleReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-role');
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
