<?php

namespace App\Http\Controllers;

use App\Services\Export\ExportServiceFactory;
use App\Services\Export\SSEExportLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExportStreamController extends Controller
{
    /**
     * Cache TTL for export state (30 minutes)
     */
    private const EXPORT_CACHE_TTL = 1800; // 30 minutes

    /**
     * Get cache key for export state
     */
    private function getCacheKey(string $exportId, int $userId): string
    {
        return "export_state_{$userId}_{$exportId}";
    }

    /**
     * Store export state in cache
     */
    private function storeExportState(string $exportId, int $userId, array $state): void
    {
        $cacheKey = $this->getCacheKey($exportId, $userId);
        Cache::put($cacheKey, $state, self::EXPORT_CACHE_TTL);
    }

    /**
     * Get export state from cache
     */
    private function getExportState(string $exportId, int $userId): ?array
    {
        $cacheKey = $this->getCacheKey($exportId, $userId);

        return Cache::get($cacheKey);
    }

    /**
     * Delete export state from cache
     */
    private function deleteExportState(string $exportId, int $userId): void
    {
        $cacheKey = $this->getCacheKey($exportId, $userId);
        Cache::forget($cacheKey);
    }

    /**
     * Stream export data using Server-Sent Events
     * Streams data directly from database to client without creating server files
     * Supports multiple export types (user, role, brand, etc.)
     *
     * @return StreamedResponse
     */
    public function stream(Request $request)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get export type (default to 'user' for backward compatibility)
        $exportType = $request->get('exportType', 'user');
        $exportId = $request->get('exportId', uniqid()); // Unique ID for this export session
        $userId = auth()->id();

        try {
            // Create export service for the specified type
            $exportService = ExportServiceFactory::create($exportType);

            // Check permission
            if (! $exportService->hasPermission()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Get export parameters
        $filtersJson = $request->get('filters', '{}');
        $filters = json_decode($filtersJson, true);

        // Ensure filters is an array (can be empty array or associative array)
        if (! is_array($filters)) {
            $filters = [];
        }

        $checkboxValuesJson = $request->get('checkboxValues', '[]');
        $checkboxValues = json_decode($checkboxValuesJson, true);

        // Ensure checkboxValues is an array
        if (! is_array($checkboxValues)) {
            $checkboxValues = [];
        }

        $search = $request->get('search', '') ?? '';
        $chunkSize = (int) $request->get('chunkSize', 100); // Default 100 records per chunk
        $reconnect = $request->get('reconnect', false); // Check if this is a reconnection
        $requestedProcessed = $request->get('processed', null); // Get processed count from request (for reconnection)

        // Check if this is a reconnection and export is already complete
        if ($reconnect) {
            $existingState = $this->getExportState($exportId, $userId);
            if ($existingState) {
                if ($existingState['status'] === 'complete') {
                    // Export already completed, send completion immediately
                    return response()->stream(function () use ($existingState, $exportId) {
                        echo 'data: ' . json_encode([
                            'status' => 'complete',
                            'message' => 'Export completed successfully',
                            'processed' => $existingState['processed'] ?? 0,
                            'total' => $existingState['total'] ?? 0,
                            'filename' => $existingState['filename'] ?? '',
                            'exportId' => $exportId,
                        ]) . "\n\n";
                        flush();
                    }, 200, [
                        'Content-Type' => 'text/event-stream',
                        'Cache-Control' => 'no-cache',
                        'Connection' => 'keep-alive',
                        'X-Accel-Buffering' => 'no',
                    ]);
                } elseif ($existingState['status'] === 'processing') {
                    // Export is still processing, but we can't resume SSE stream
                    // We need to restart the stream, but use existing state for progress
                    // The stream will continue from server-side, we just reconnect to it
                    // This is handled below by continuing with the normal stream flow
                }
            }
        }

        // Get existing state if reconnecting (before closure)
        $existingStateForReconnect = null;
        if ($reconnect) {
            $existingStateForReconnect = $this->getExportState($exportId, $userId);
        }

        $controller = $this; // Store $this reference for use in closure

        return response()->stream(function () use ($exportService, $filters, $checkboxValues, $search, $chunkSize, $exportType, $exportId, $userId, $controller, $reconnect, $existingStateForReconnect, $requestedProcessed) {
            // Disable time limit for long-running SSE connection
            set_time_limit(0);

            // Store original memory limit to restore it later
            $originalMemoryLimit = ini_get('memory_limit');

            // Increase memory limit for large exports (1M+ records)
            ini_set('memory_limit', '1024M');

            // Track session start time for performance logging
            $sessionStartTime = microtime(true);

            // Log stream start
            logger()->info('ExportStreamController: Stream function started', [
                'exportId' => $exportId,
                'exportType' => $exportType,
                'userId' => $userId,
                'reconnect' => $reconnect,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            ]);

            try {
                // Log export start or reconnection
                if ($reconnect) {
                    SSEExportLogger::logReconnectionAttempt($exportId, $existingStateForReconnect['processed'] ?? 0, $requestedProcessed);
                } else {
                    SSEExportLogger::logExportStart($exportId, $exportType, $userId, [
                        'total' => 0, // Will be updated after query
                        'chunkSize' => $chunkSize,
                        'filters' => $filters,
                        'search' => $search,
                        'checkboxValues' => $checkboxValues,
                    ]);
                }

                // Build query using export service
                $query = $exportService->buildQuery($filters, $checkboxValues, $search);

                // Get total count for progress calculation
                // CRITICAL: On reconnection, use the stored total count to maintain consistency
                // If we recalculate, the count might change (records added/deleted), causing skip issues
                if ($reconnect && $existingStateForReconnect && isset($existingStateForReconnect['total'])) {
                    // Use stored total from initial export
                    $totalRecords = $existingStateForReconnect['total'];
                    SSEExportLogger::logReconnectionSuccess($exportId, $existingStateForReconnect['processed'] ?? 0, $totalRecords);
                } else {
                    // Initial export - calculate total count
                    // Since the query uses GROUP BY, we need to count the grouped results
                    // Use a subquery approach to count efficiently without loading all records
                    try {
                        $countQuery = $exportService->buildQuery($filters, $checkboxValues, $search);

                        // Count grouped results using subquery - memory efficient for large datasets
                        // This ensures we get the exact count that will be exported
                        $totalRecords = DB::table(DB::raw("({$countQuery->toSql()}) as subquery"))
                            ->mergeBindings($countQuery->getQuery())
                            ->count();

                        // Update log with actual total
                        SSEExportLogger::logExportStart($exportId, $exportType, $userId, [
                            'total' => $totalRecords,
                            'chunkSize' => $chunkSize,
                            'filters' => $filters,
                            'search' => $search,
                            'checkboxValues' => $checkboxValues,
                        ]);
                    } catch (\Exception $countException) {
                        // Log count query failure with context
                        logger()->error('ExportStreamController: Count query failed', [
                            'exportId' => $exportId,
                            'exportType' => $exportType,
                            'error' => $countException->getMessage(),
                            'trace' => $countException->getTraceAsString(),
                            'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                        ]);

                        // Send specific error to client
                        echo 'data: ' . json_encode([
                            'status' => 'error',
                            'message' => 'Failed to count records. The dataset may be too large. Please try applying filters to reduce the number of records.',
                            'exportId' => $exportId,
                        ]) . "\n\n";
                        flush();

                        // Restore original memory limit before early return
                        ini_set('memory_limit', $originalMemoryLimit);

                        return;
                    }
                }

                if ($totalRecords === 0) {
                    $noRecordsMessage = __('messages.export.no_records_found');
                    SSEExportLogger::logExportError($exportId, $noRecordsMessage, [
                        'filters' => $filters,
                        'search' => $search,
                        'checkboxValues' => $checkboxValues,
                    ]);

                    $controller->storeExportState($exportId, $userId, [
                        'status' => 'error',
                        'message' => $noRecordsMessage,
                        'exportId' => $exportId,
                        'exportType' => $exportType,
                    ]);

                    echo 'data: ' . json_encode([
                        'status' => 'error',
                        'message' => $noRecordsMessage,
                        'exportId' => $exportId,
                    ]) . "\n\n";
                    flush();

                    // Restore original memory limit before early return
                    ini_set('memory_limit', $originalMemoryLimit);

                    return;
                }

                // Initialize or restore export state
                $headerSent = false;
                $processedRecords = 0;

                if ($reconnect && $existingStateForReconnect && $existingStateForReconnect['status'] === 'processing') {
                    // Restore from existing state
                    $headerSent = $existingStateForReconnect['headerSent'] ?? false;
                    $storedProcessed = $existingStateForReconnect['processed'] ?? 0;

                    // Use the maximum of requested processed (from client) and stored processed
                    // This ensures we continue from the highest point reached
                    if ($requestedProcessed !== null) {
                        $requestedProcessed = (int) $requestedProcessed;
                        $processedRecords = max($requestedProcessed, $storedProcessed);
                    } else {
                        $processedRecords = $storedProcessed;
                    }

                    SSEExportLogger::logReconnectionSuccess($exportId, $processedRecords, $totalRecords);
                }

                $controller->storeExportState($exportId, $userId, [
                    'status' => 'processing',
                    'exportId' => $exportId,
                    'exportType' => $exportType,
                    'total' => $totalRecords,
                    'processed' => $processedRecords,
                    'percentage' => $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0,
                    'headerSent' => $headerSent,
                ]);

                // Log state persistence
                SSEExportLogger::logStatePersistence($exportId, 'STORED_INITIAL_STATE', [
                    'status' => 'processing',
                    'total' => $totalRecords,
                    'processed' => $processedRecords,
                ]);

                // Send initial connection and total records
                // If reconnecting, include current progress
                $connectionData = [
                    'status' => 'connected',
                    'total' => $totalRecords,
                    'chunkSize' => $chunkSize,
                    'exportType' => $exportType,
                    'exportId' => $exportId,
                    'reconnect' => $reconnect,
                    'processed' => $processedRecords,
                    'percentage' => $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0,
                ];

                logger()->info('ExportStreamController: Sending connection message', [
                    'exportId' => $exportId,
                    'totalRecords' => $totalRecords,
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                ]);

                echo 'data: ' . json_encode($connectionData) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Log client connection
                SSEExportLogger::logClientConnection($exportId, 'connected', [
                    'total_records' => $totalRecords,
                    'is_reconnection' => $reconnect,
                    'starting_from_record' => $processedRecords,
                ]);

                // Send CSV header using export service (only if header not already sent)
                if (! $headerSent) {
                    $headerContent = $exportService->getCSVHeader() . "\n";
                    $headerSent = true;

                    echo 'data: ' . json_encode([
                        'status' => 'header',
                        'content' => $headerContent,
                        'exportId' => $exportId,
                    ]) . "\n\n";
                    flush();

                    // Update state with header
                    $controller->storeExportState($exportId, $userId, [
                        'status' => 'processing',
                        'exportId' => $exportId,
                        'exportType' => $exportType,
                        'total' => $totalRecords,
                        'processed' => $processedRecords,
                        'percentage' => $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0,
                        'headerSent' => $headerSent,
                    ]);
                } else {
                    // Reconnecting with existing content - send progress update to sync client
                    $percentage = $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0;
                    echo 'data: ' . json_encode([
                        'status' => 'progress',
                        'processed' => $processedRecords,
                        'total' => $totalRecords,
                        'percentage' => $percentage,
                        'exportId' => $exportId,
                    ]) . "\n\n";
                    flush();
                }

                // Calculate starting point for chunk processing
                // If reconnecting, we need to skip already processed records
                $initialProcessed = $processedRecords; // Store the initial processed count
                $startingChunk = (int) floor($processedRecords / $chunkSize); // Calculate which chunk to start from
                $skipRecords = $processedRecords; // Number of records to skip
                $chunkNumber = $startingChunk; // Start from the calculated chunk
                $lastProgressUpdate = $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0; // Track last progress percentage sent

                // Log chunk processing start (only at the beginning)
                SSEExportLogger::logProgress($exportId, $processedRecords, $totalRecords, $lastProgressUpdate, $chunkNumber);

                // Process data in chunks
                // Note: With GROUP BY users.id, each record in the chunk represents one user
                // If reconnecting, skip to the chunk where we left off
                $query->skip($skipRecords)->chunk($chunkSize, function ($records) use ($exportService, &$processedRecords, &$chunkNumber, &$lastProgressUpdate, $totalRecords, $exportId, $userId, $controller, $exportType, $chunkSize) {
                    $chunkStartTime = microtime(true);

                    // Check if export was cancelled before processing this chunk
                    $currentState = $controller->getExportState($exportId, $userId);
                    $isCancelled = $currentState && $currentState['status'] === 'cancelled';

                    if ($isCancelled) {
                        // Log cancellation detection only when it actually happens
                        SSEExportLogger::logCancellationCheck($exportId, true, $processedRecords);
                        SSEExportLogger::logExportCancelled($exportId, $processedRecords, 'Backend detected cancellation during chunk processing');

                        echo 'data: ' . json_encode([
                            'status' => 'cancelled',
                            'message' => 'Export was cancelled by user',
                            'exportId' => $exportId,
                        ]) . "\n\n";
                        flush();

                        return false; // Stop chunk processing
                    }

                    $chunkNumber++;
                    $csvRows = [];
                    $chunkStartRecords = $processedRecords; // Track start of chunk

                    foreach ($records as $record) {
                        // Check cancellation status periodically (every 50 records instead of 10 to reduce noise)
                        // Only log when cancellation is actually detected, not on every check
                        if (($processedRecords % 50) === 0) {
                            $currentState = $controller->getExportState($exportId, $userId);
                            $isCancelled = $currentState && $currentState['status'] === 'cancelled';

                            if ($isCancelled) {
                                // Log only when cancellation is detected
                                SSEExportLogger::logCancellationCheck($exportId, true, $processedRecords);
                                SSEExportLogger::logExportCancelled($exportId, $processedRecords, 'Backend detected cancellation during record processing');

                                echo 'data: ' . json_encode([
                                    'status' => 'cancelled',
                                    'message' => 'Export was cancelled by user',
                                    'exportId' => $exportId,
                                ]) . "\n\n";
                                flush();

                                return false; // Stop processing
                            }
                            // Don't log successful checks - they create too much noise
                        }

                        $row = $exportService->formatToCSV($record);
                        $csvRows[] = $row;
                        $processedRecords++;

                        // Send progress update every few records for smoother progress bar
                        // Update every 5 records OR when progress increases by at least 2%
                        $currentPercentage = $totalRecords > 0 ? round(($processedRecords / $totalRecords) * 100, 2) : 0;

                        // Send update if:
                        // 1. We've processed 5 more records, OR
                        // 2. Progress increased by at least 2% (reduced frequency)
                        $shouldUpdate = ($processedRecords - $chunkStartRecords) % 5 === 0
                            || ($currentPercentage - $lastProgressUpdate) >= 2;

                        if ($shouldUpdate && $currentPercentage > $lastProgressUpdate) {
                            $lastProgressUpdate = $currentPercentage;

                            // Send progress update (without data, just progress)
                            echo 'data: ' . json_encode([
                                'status' => 'progress',
                                'processed' => $processedRecords,
                                'total' => $totalRecords,
                                'percentage' => min(100, $currentPercentage),
                                'exportId' => $exportId,
                            ]) . "\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();

                            // Small delay to allow animation to catch up
                            usleep(50000); // 50ms delay between progress updates
                        }
                    }

                    // MEMORY FIX: Send chunk data directly without accumulating in memory
                    $csvContent = implode("\n", $csvRows) . "\n";

                    // Calculate final percentage for this chunk
                    $percentage = $totalRecords > 0 ? min(100, round(($processedRecords / $totalRecords) * 100, 2)) : 0;
                    $lastProgressUpdate = $percentage;

                    // Calculate chunk processing time
                    $chunkDuration = microtime(true) - $chunkStartTime;

                    // Log chunk completion (only every 5 chunks to reduce noise)
                    if ($chunkNumber % 5 === 0 || $percentage >= 100) {
                        SSEExportLogger::logChunkProcessing($exportId, $chunkNumber, $chunkSize, count($records), $chunkDuration);
                    }

                    // Update state in cache
                    $controller->storeExportState($exportId, $userId, [
                        'status' => 'processing',
                        'exportId' => $exportId,
                        'exportType' => $exportType,
                        'total' => $totalRecords,
                        'processed' => $processedRecords,
                        'percentage' => $percentage,
                        'headerSent' => true,
                    ]);

                    echo 'data: ' . json_encode([
                        'status' => 'data',
                        'content' => $csvContent,
                        'processed' => $processedRecords,
                        'total' => $totalRecords,
                        'percentage' => $percentage,
                        'chunk' => $chunkNumber,
                        'exportId' => $exportId,
                    ]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    // MEMORY FIX: Clear variables after sending to free memory
                    unset($csvRows, $csvContent);

                    // Small delay to prevent overwhelming the connection
                    usleep(10000); // 10ms delay
                });

                // Calculate total session duration
                $sessionDuration = microtime(true) - $sessionStartTime;

                // Send completion
                $filename = $exportService->getFilenamePrefix() . date('dmY') . '.csv';

                // Log export completion
                SSEExportLogger::logExportComplete($exportId, $processedRecords, $sessionDuration);

                // MEMORY FIX: Estimate file size (average ~500 bytes per record) instead of storing actual content
                $estimatedFileSize = $processedRecords * 500;

                //  Store completed state in cache (without fileContent to save memory)
                $controller->storeExportState($exportId, $userId, [
                    'status' => 'complete',
                    'exportId' => $exportId,
                    'exportType' => $exportType,
                    'total' => $totalRecords,
                    'processed' => $processedRecords,
                    'percentage' => 100,
                    'filename' => $filename,
                    'completed_at' => now()->toISOString(),
                    'duration_seconds' => $sessionDuration,
                    'estimated_file_size' => $estimatedFileSize,
                ]);

                // Log session summary
                SSEExportLogger::logSessionSummary($exportId, [
                    'export_type' => $exportType,
                    'total_records' => $totalRecords,
                    'processed_records' => $processedRecords,
                    'duration_seconds' => round($sessionDuration, 2),
                    'records_per_second' => round($processedRecords / $sessionDuration, 2),
                    'was_reconnection' => $reconnect,
                    'estimated_file_size_bytes' => $estimatedFileSize,
                    'chunks_processed' => $chunkNumber,
                ]);

                echo 'data: ' . json_encode([
                    'status' => 'complete',
                    'message' => 'Export completed successfully',
                    'processed' => $processedRecords,
                    'total' => $totalRecords,
                    'filename' => $filename,
                    'exportId' => $exportId,
                ]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Restore original memory limit after successful export
                ini_set('memory_limit', $originalMemoryLimit);
            } catch (Throwable $e) {
                // Log the error with full context
                $errorContext = [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'export_type' => $exportType ?? 'unknown',
                    'user_id' => $userId,
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                    'execution_time' => round((microtime(true) - $sessionStartTime), 2) . 's',
                ];

                SSEExportLogger::logExportError($exportId, $e->getMessage(), $errorContext);

                logger()->error('ExportStreamController: stream: Throwable', [
                    'Message' => $e->getMessage(),
                    'TraceAsString' => $e->getTraceAsString(),
                    'exportType' => $exportType,
                    'exportId' => $exportId,
                    'memory_usage' => $errorContext['memory_usage'],
                    'memory_peak' => $errorContext['memory_peak'],
                    'execution_time' => $errorContext['execution_time'],
                ]);

                // Store error state
                $controller->storeExportState($exportId, $userId, [
                    'status' => 'error',
                    'exportId' => $exportId,
                    'exportType' => $exportType ?? 'user',
                    'message' => 'An error occurred during export: ' . $e->getMessage(),
                    'error_at' => now()->toISOString(),
                ]);

                echo 'data: ' . json_encode([
                    'status' => 'error',
                    'message' => 'An error occurred during export: ' . $e->getMessage(),
                    'exportId' => $exportId,
                ]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                // Restore original memory limit after error
                ini_set('memory_limit', $originalMemoryLimit);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Check export status (for reconnection)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $exportId = $request->get('exportId');
        $userId = auth()->id();

        if (! $exportId) {
            return response()->json(['error' => 'Export ID is required'], 400);
        }

        $state = $this->getExportState($exportId, $userId);

        if (! $state) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Export not found or expired',
            ]);
        }

        return response()->json($state);
    }

    /**
     * Cancel an active export
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $exportId = $request->get('exportId');
        $userId = auth()->id();

        if (! $exportId) {
            return response()->json(['error' => 'Export ID is required'], 400);
        }

        // Get current state to log processed count before cancellation
        $existingState = $this->getExportState($exportId, $userId);
        $processedBeforeCancel = $existingState['processed'] ?? 0;

        // Log cancellation request
        SSEExportLogger::logExportCancelled($exportId, $processedBeforeCancel, 'User requested cancellation via API');

        if ($existingState) {
            // Mark export as cancelled in cache
            $this->storeExportState($exportId, $userId, array_merge($existingState, [
                'status' => 'cancelled',
                'cancelled_at' => now()->toISOString(),
                'cancelled_by_user' => $userId,
            ]));

            // Log state update
            SSEExportLogger::logStatePersistence($exportId, 'UPDATED_FOR_CANCELLATION', [
                'status' => 'cancelled',
                'processed_before_cancel' => $processedBeforeCancel,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Export cancelled successfully']);
    }

    /**
     * Clean up export state (optional cleanup endpoint)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup(Request $request)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $exportId = $request->get('exportId');
        $userId = auth()->id();

        if (! $exportId) {
            return response()->json(['error' => 'Export ID is required'], 400);
        }

        $this->deleteExportState($exportId, $userId);

        return response()->json(['success' => true, 'message' => 'Export state cleaned up']);
    }
}
