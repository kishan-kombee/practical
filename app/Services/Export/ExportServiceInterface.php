<?php

namespace App\Services\Export;

interface ExportServiceInterface
{
    /**
     * Build the export query with filters
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): \Illuminate\Database\Eloquent\Builder;

    /**
     * Format a record to CSV row
     */
    public function formatToCSV($record): string;

    /**
     * Get CSV header row
     */
    public function getCSVHeader(): string;

    /**
     * Get export filename prefix
     */
    public function getFilenamePrefix(): string;

    /**
     * Check if user has permission to export
     */
    public function hasPermission(): bool;
}
