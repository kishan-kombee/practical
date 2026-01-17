<?php

namespace App\Services\Export;

use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class SmsTemplateExportService implements ExportServiceInterface
{
    /**
     * Build export query (mirrors table datasource logic)
     */
    public function buildQuery(array $filters, array $checkboxValues, ?string $search): Builder
    {
        $query = SmsTemplate::query()

            ->select([
                'sms_templates.dlt_message_id',
            ])
            ->whereNull('sms_templates.deleted_at')
            ->groupBy('sms_templates.id');

        // Apply dlt_message_id filters
        if (isset($filters['input_text']['smstemplates']['dlt_message_id']) && $filters['input_text']['smstemplates']['dlt_message_id']) {
            $query->where('sms_templates.dlt_message_id', 'like', '%' . $filters['input_text']['smstemplates']['dlt_message_id'] . '%');
        }

        // Apply checkbox filter (export only selected ids)
        if (! empty($checkboxValues)) {
            $query->whereIn('sms_templates.id', $checkboxValues);
        }

        // Apply global search across configured columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->orWhere('sms_templates.dlt_message_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sms_templates.id', 'desc');
    }

    /**
     * Map a single row to CSV format.
     */
    public function formatToCSV($row): string
    {
        $fields = [
            $row->dlt_message_id ?? '',
        ];

        return implode(',', array_map([$this, 'wrapInQuotes'], $fields));
    }

    public function getCSVHeader(): string
    {
        return '"Dlt Message Id"';
    }

    public function getFilenamePrefix(): string
    {
        return 'SmsTemplateReports_';
    }

    public function hasPermission(): bool
    {
        return Gate::allows('view-smstemplate');
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
