<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesApiFilters
{
    /**
     * Apply filter handling logic to a query builder.
     * This method extracts the duplicate filter handling code from API controllers.
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filter = $request->get('filters');
        if (! is_null($filter)) {
            // If filter is already an array (e.g., filters[name]=test), convert to object
            if (is_array($filter)) {
                $filter = (object) $filter;
            } elseif (is_string($filter)) {
                // Check if it's base64 encoded
                $decoded = base64_decode($filter, true);
                if ($decoded !== false && base64_encode($decoded) === $filter) {
                    $filter = json_decode(urldecode($decoded)); // IF YOU USE URL-ENCODING
                } else {
                    $filter = json_decode($filter); // IF YOU DO NOT USE URL-ENCODING
                }
            }

            // Apply filter to query (handle both array and object)
            if (is_array($filter) && ! empty($filter)) {
                foreach ($filter as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $column = (string) $key;
                        $query->where($column, '=', $value);
                    }
                }
            } elseif (is_object($filter) && ! empty((array) $filter)) {
                foreach ($filter as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $column = (string) $key;
                        $query->where($column, '=', $value);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * Apply sorting to a query builder.
     *
     * @param Builder $query
     * @param Request $request
     * @param string $defaultColumn
     * @param string $defaultDirection
     * @return Builder
     */
    protected function applySorting(Builder $query, Request $request, string $defaultColumn = 'created_at', string $defaultDirection = 'DESC'): Builder
    {
        if (isset($request->sort_by) && isset($request->sort_order) && ! empty($request->sort_by) && ! empty($request->sort_order)) {
            $query->orderBy($request->sort_by, $request->sort_order);
        } else {
            $query->orderBy($defaultColumn, $defaultDirection);
        }

        return $query;
    }

    /**
     * Apply pagination to a query builder.
     *
     * @param Builder $query
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function applyPagination(Builder $query, Request $request)
    {
        $perPage = $request->get('per_page', config('constants.apiPerPage'));
        $page = $request->get('page', config('constants.apiPage'));

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Parse filters from request.
     * Handles base64 encoded, JSON string, and array formats.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function parseFilters(Request $request): array
    {
        $filters = [];
        $requestFilters = $request->get('filters');

        if ($requestFilters) {
            if (is_string($requestFilters)) {
                $decoded = base64_decode($requestFilters, true);
                $requestFilters = $decoded !== false ? json_decode(urldecode($decoded), true) : json_decode($requestFilters, true);
            }
            if (is_array($requestFilters)) {
                $filters = $requestFilters;
            }
        }

        return $filters;
    }
}
