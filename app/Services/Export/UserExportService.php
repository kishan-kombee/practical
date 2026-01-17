<?php

namespace App\Services\Export;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = User::query()

            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'roles.name as role_name',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.mobile_number',
                DB::raw(
                    '(CASE
                        WHEN users.status = "' . config('constants.user.status.key.active') . '" THEN  "' . config('constants.user.status.value.active') . '"
                        WHEN users.status = "' . config('constants.user.status.key.inactive') . '" THEN  "' . config('constants.user.status.value.inactive') . '"
                        ELSE " "
                    END) AS status'
                ),
            ])
            ->whereNull('users.deleted_at')
            ->groupBy('users.id');

        // Apply role_id filters
        if (isset($filters['select']['users']['role_id']) && $filters['select']['users']['role_id']) {
            $query->where('users.role_id', $filters['select']['users']['role_id']);
        }

        // Apply first_name filters
        if (isset($filters['input_text']['users']['first_name']) && $filters['input_text']['users']['first_name']) {
            $query->where('users.first_name', 'like', '%' . $filters['input_text']['users']['first_name'] . '%');
        }

        // Apply last_name filters
        if (isset($filters['input_text']['users']['last_name']) && $filters['input_text']['users']['last_name']) {
            $query->where('users.last_name', 'like', '%' . $filters['input_text']['users']['last_name'] . '%');
        }

        // Apply status filters
        if (isset($filters['select']['users']['status']) && $filters['select']['users']['status']) {
            $query->where('users.status', $filters['select']['users']['status']);
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('users.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('users.first_name', 'like', "%{$search}%");
                $q->orWhere('users.last_name', 'like', "%{$search}%");
                $q->orWhere('users.mobile_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('users.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->id ?? '',
            $row->role_name ?? '',
            $row->first_name ?? '',
            $row->last_name ?? '',
            $row->email ?? '',
            $row->mobile_number ?? '',
            $row->status ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Id","Role Id","First Name","Last Name","Email","Mobile Number","Status"';
    }

    public function getFilenamePrefix(): string
    {
        return 'UserReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-user');
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
