<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Log;

class SSEExportLogger
{
    /**
     * Log channel for SSE exports
     */
    private const LOG_CHANNEL = 'sse_export';

    /**
     * Log export start
     */
    public static function logExportStart(string $exportId, string $exportType, int $userId, array $params = []): void
    {
        Log::channel(self::LOG_CHANNEL)->info('🚀 EXPORT STARTED', [
            'export_id' => $exportId,
            'export_type' => $exportType,
            'user_id' => $userId,
            'total_records' => $params['total'] ?? 0,
            'chunk_size' => $params['chunkSize'] ?? 100,
            'has_filters' => ! empty($params['filters']),
            'has_search' => ! empty($params['search']),
            'has_checkbox_values' => ! empty($params['checkboxValues']),
            'timestamp' => now()->toISOString(),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
        ]);
    }

    /**
     * Log export progress (reduced frequency to avoid noise)
     */
    public static function logProgress(string $exportId, int $processed, int $total, float $percentage, ?int $chunkNumber = null): void
    {
        // Only log progress at significant milestones to reduce noise
        $shouldLog = $percentage == 0 || // Start
                     $percentage >= 100 || // Complete
                     ($percentage > 0 && $percentage % 25 == 0) || // Every 25%
                     ($chunkNumber !== null && $chunkNumber % 10 == 0); // Every 10 chunks

        if ($shouldLog) {
            Log::channel(self::LOG_CHANNEL)->info('📊 EXPORT PROGRESS', [
                'export_id' => $exportId,
                'processed' => $processed,
                'total' => $total,
                'percentage' => round($percentage, 2),
                'chunk_number' => $chunkNumber,
                'remaining' => $total - $processed,
                'timestamp' => now()->toISOString(),
                'memory_usage' => self::formatBytes(memory_get_usage(true)),
            ]);
        }
    }

    /**
     * Log export completion
     */
    public static function logExportComplete(string $exportId, int $totalProcessed, ?float $durationSeconds = null): void
    {
        Log::channel(self::LOG_CHANNEL)->info('✅ EXPORT COMPLETED', [
            'export_id' => $exportId,
            'total_processed' => $totalProcessed,
            'duration_seconds' => $durationSeconds ? round($durationSeconds, 2) : null,
            'duration_formatted' => $durationSeconds ? self::formatDuration($durationSeconds) : null,
            'records_per_second' => $durationSeconds ? round($totalProcessed / $durationSeconds, 2) : null,
            'timestamp' => now()->toISOString(),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
            'peak_memory' => self::formatBytes(memory_get_peak_usage(true)),
        ]);
    }

    /**
     * Log export cancellation
     */
    public static function logExportCancelled(string $exportId, int $processedBeforeCancel, string $reason = 'User cancelled'): void
    {
        Log::channel(self::LOG_CHANNEL)->warning('❌ EXPORT CANCELLED', [
            'export_id' => $exportId,
            'processed_before_cancel' => $processedBeforeCancel,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
        ]);
    }

    /**
     * Log reconnection attempt
     */
    public static function logReconnectionAttempt(string $exportId, int $storedProcessed, ?int $requestedProcessed = null): void
    {
        Log::channel(self::LOG_CHANNEL)->info('🔄 RECONNECTION ATTEMPT', [
            'export_id' => $exportId,
            'stored_processed' => $storedProcessed,
            'requested_processed' => $requestedProcessed,
            'will_resume_from' => max($storedProcessed, $requestedProcessed ?? 0),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log successful reconnection
     */
    public static function logReconnectionSuccess(string $exportId, int $resumedFrom, int $totalRecords): void
    {
        Log::channel(self::LOG_CHANNEL)->info('✅ RECONNECTION SUCCESS', [
            'export_id' => $exportId,
            'resumed_from_record' => $resumedFrom,
            'total_records' => $totalRecords,
            'remaining_records' => $totalRecords - $resumedFrom,
            'progress_percentage' => round(($resumedFrom / $totalRecords) * 100, 2),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log client connection events
     */
    public static function logClientConnection(string $exportId, string $event, array $data = []): void
    {
        $eventEmojis = [
            'connected' => '🔗',
            'disconnected' => '🔌',
            'error' => '⚠️',
            'timeout' => '⏰',
        ];

        Log::channel(self::LOG_CHANNEL)->info("{$eventEmojis[$event]} CLIENT {$event}", array_merge([
            'export_id' => $exportId,
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ], $data));
    }

    /**
     * Log chunk processing
     */
    public static function logChunkProcessing(string $exportId, int $chunkNumber, int $chunkSize, int $recordsInChunk, ?float $chunkDuration = null): void
    {
        Log::channel(self::LOG_CHANNEL)->debug('📦 CHUNK PROCESSED', [
            'export_id' => $exportId,
            'chunk_number' => $chunkNumber,
            'chunk_size' => $chunkSize,
            'records_in_chunk' => $recordsInChunk,
            'chunk_duration_ms' => $chunkDuration ? round($chunkDuration * 1000, 2) : null,
            'records_per_second' => $chunkDuration ? round($recordsInChunk / $chunkDuration, 2) : null,
            'timestamp' => now()->toISOString(),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
        ]);
    }

    /**
     * Log export error
     */
    public static function logExportError(string $exportId, string $error, array $context = []): void
    {
        Log::channel(self::LOG_CHANNEL)->error('💥 EXPORT ERROR', array_merge([
            'export_id' => $exportId,
            'error_message' => $error,
            'timestamp' => now()->toISOString(),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
        ], $context));
    }

    /**
     * Log cancellation check (proof that backend checks for cancellation)
     * Only logs when cancellation is actually detected to reduce noise
     */
    public static function logCancellationCheck(string $exportId, bool $isCancelled, int $currentRecord): void
    {
        if ($isCancelled) {
            Log::channel(self::LOG_CHANNEL)->warning('🛑 CANCELLATION DETECTED', [
                'export_id' => $exportId,
                'cancelled_at_record' => $currentRecord,
                'action' => 'Stopping export processing immediately',
                'timestamp' => now()->toISOString(),
            ]);
        }
        // Don't log successful checks - they create noise in logs
        // The fact that processing continues is proof that checks are working
    }

    /**
     * Log queue operations
     */
    public static function logQueueOperation(string $operation, string $exportId, string $exportType, array $data = []): void
    {
        $operationEmojis = [
            'added' => '➕',
            'removed' => '➖',
            'processed' => '▶️',
            'skipped' => '⏭️',
        ];

        Log::channel(self::LOG_CHANNEL)->info("{$operationEmojis[$operation]} QUEUE {$operation}", array_merge([
            'export_id' => $exportId,
            'export_type' => $exportType,
            'operation' => $operation,
            'timestamp' => now()->toISOString(),
        ], $data));
    }

    /**
     * Log state persistence operations
     */
    public static function logStatePersistence(string $exportId, string $operation, array $state = []): void
    {
        Log::channel(self::LOG_CHANNEL)->debug("💾 STATE {$operation}", [
            'export_id' => $exportId,
            'operation' => $operation,
            'state_keys' => array_keys($state),
            'state_size_bytes' => strlen(json_encode($state)),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Format duration to human readable format
     */
    private static function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            return $minutes . 'm ' . round($remainingSeconds, 1) . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $remainingSeconds = $seconds % 60;

            return $hours . 'h ' . $minutes . 'm ' . round($remainingSeconds, 1) . 's';
        }
    }

    /**
     * Create a session summary log entry
     */
    public static function logSessionSummary(string $exportId, array $summary): void
    {
        Log::channel(self::LOG_CHANNEL)->info('📋 EXPORT SESSION SUMMARY', array_merge([
            'export_id' => $exportId,
            'timestamp' => now()->toISOString(),
        ], $summary));
    }
}
