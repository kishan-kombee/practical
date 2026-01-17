<?php

namespace App\Http\Controllers;

use App\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ExportProgressController extends Controller
{
    /**
     * Stream export progress using Server-Sent Events
     *
     * @return StreamedResponse
     */
    public function stream(Request $request)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $batchId = $request->get('batchId');
        $functionParams = $request->get('functionParams');

        if (! $batchId) {
            return response()->json(['error' => 'Batch ID is required'], 400);
        }

        return response()->stream(function () use ($batchId, $functionParams) {
            // Disable time limit for long-running SSE connection
            set_time_limit(0);

            // Set headers for SSE
            echo 'data: ' . json_encode(['status' => 'connected']) . "\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $lastProgress = -1; // Initialize to -1 to ensure first update is sent
            $maxIterations = 600; // Maximum 10 minutes (600 * 1 second)
            $iteration = 0;
            $lastUpdateTime = 0; // Track last update time to force periodic updates

            while ($iteration < $maxIterations) {
                try {
                    // Decode function parameters if provided
                    $params = $functionParams ? json_decode($functionParams, true) : ['batchId' => $batchId];
                    $params['batchId'] = $batchId;

                    // Process export progress
                    $result = Helper::processProgressOfExport(json_encode($params));

                    if ($result['status'] == 0) {
                        // Error occurred
                        echo 'data: ' . json_encode([
                            'status' => 'error',
                            'message' => $result['message'] ?? 'An error occurred during export',
                        ]) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    } elseif ($result['status'] == 1) {
                        // Progress update
                        $progressData = json_decode($result['data'], true);
                        $currentProgress = $progressData['exportProgress'] ?? 0;
                        $currentTime = time();

                        // Always send progress updates to ensure smooth animation
                        // This helps even if batch completes quickly
                        echo 'data: ' . json_encode([
                            'status' => 'progress',
                            'data' => $progressData,
                        ]) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();

                        $lastProgress = $currentProgress;
                        $lastUpdateTime = $currentTime;

                        // Check if export is complete
                        if (isset($progressData['isFileDownloadable']) && $progressData['isFileDownloadable'] == 1) {
                            // Export complete, send completion with download info
                            $downloadFileName = $params['downloadPrefixFileName'] . date('dmY') . '.' . config('constants.export_csv_file_type');

                            echo 'data: ' . json_encode([
                                'status' => 'complete',
                                'message' => 'Export completed successfully',
                                'downloadUrl' => route('export.download', ['batchId' => $batchId]),
                                'downloadFileName' => $downloadFileName,
                                'functionParams' => json_encode($params),
                            ]) . "\n\n";

                            if (ob_get_level() > 0) {
                                ob_flush();
                            }
                            flush();
                            break;
                        }
                    } elseif ($result['status'] == 2) {
                        // Export complete - extract filename from params
                        $downloadFileName = $params['downloadPrefixFileName'] . date('dmY') . '.' . config('constants.export_csv_file_type');

                        echo 'data: ' . json_encode([
                            'status' => 'complete',
                            'message' => $result['message'] ?? 'Export completed successfully',
                            'downloadUrl' => route('export.download', ['batchId' => $batchId]),
                            'downloadFileName' => $downloadFileName,
                            'functionParams' => json_encode($params),
                        ]) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    // Check if batch is finished or cancelled
                    $batch = Bus::findBatch($batchId);
                    if (! $batch) {
                        echo 'data: ' . json_encode([
                            'status' => 'error',
                            'message' => 'Export batch not found',
                        ]) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    if ($batch->cancelled()) {
                        echo 'data: ' . json_encode([
                            'status' => 'cancelled',
                            'message' => 'Export was cancelled',
                        ]) . "\n\n";

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    if ($batch->finished()) {
                        // Final check for completion
                        $finalProgress = Helper::processProgressOfExport(json_encode($params));
                        $downloadFileName = $params['downloadPrefixFileName'] . date('dmY') . '.' . config('constants.export_csv_file_type');

                        if ($finalProgress['status'] == 2) {
                            echo 'data: ' . json_encode([
                                'status' => 'complete',
                                'message' => 'Export completed successfully',
                                'downloadUrl' => route('export.download', ['batchId' => $batchId]),
                                'downloadFileName' => $downloadFileName,
                                'functionParams' => json_encode($params),
                            ]) . "\n\n";
                        } else {
                            // Get final progress data
                            $finalProgressData = json_decode($finalProgress['data'] ?? '{}', true);
                            if (isset($finalProgressData['isFileDownloadable']) && $finalProgressData['isFileDownloadable'] == 1) {
                                echo 'data: ' . json_encode([
                                    'status' => 'complete',
                                    'message' => 'Export completed successfully',
                                    'downloadUrl' => route('export.download', ['batchId' => $batchId]),
                                    'downloadFileName' => $downloadFileName,
                                    'functionParams' => json_encode($params),
                                ]) . "\n\n";
                            } else {
                                echo 'data: ' . json_encode([
                                    'status' => 'complete',
                                    'message' => 'Export completed',
                                    'downloadUrl' => route('export.download', ['batchId' => $batchId]),
                                    'downloadFileName' => $downloadFileName,
                                    'functionParams' => json_encode($params),
                                ]) . "\n\n";
                            }
                        }

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                        break;
                    }

                    // Wait 1 second before next check
                    sleep(1);
                    $iteration++;
                } catch (Throwable $e) {
                    logger()->error('ExportProgressController: stream: Throwable', [
                        'Message' => $e->getMessage(),
                        'TraceAsString' => $e->getTraceAsString(),
                        'batchId' => $batchId,
                    ]);

                    echo 'data: ' . json_encode([
                        'status' => 'error',
                        'message' => 'An error occurred while processing export',
                    ]) . "\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    break;
                }
            }

            // Timeout reached
            if ($iteration >= $maxIterations) {
                echo 'data: ' . json_encode([
                    'status' => 'timeout',
                    'message' => 'Export progress monitoring timed out',
                ]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable buffering in Nginx
        ]);
    }

    /**
     * Download the exported file
     *
     * @param string $batchId
     * @return StreamedResponse
     */
    public function download(Request $request, $batchId)
    {
        // Ensure user is authenticated
        if (! auth()->check()) {
            abort(401, 'Unauthorized');
        }

        try {
            $functionParams = $request->get('functionParams');

            if (! $functionParams) {
                // Try to get from session or reconstruct from batch
                $batch = Bus::findBatch($batchId);
                if (! $batch) {
                    abort(404, 'Export batch not found');
                }

                // Reconstruct params from batch metadata if available
                // For now, return error if functionParams not provided
                abort(400, 'Export parameters not found');
            }

            $params = json_decode($functionParams, true);
            $params['batchId'] = $batchId;

            // Process and download the file
            $result = Helper::processProgressOfExport(json_encode($params));

            if ($result['status'] == 2) {
                return $result['data'];
            }

            // If not ready, try one more time
            $finalResult = Helper::processProgressOfExport(json_encode($params));
            if ($finalResult['status'] == 2) {
                return $finalResult['data'];
            }

            abort(404, 'Export file not ready for download');
        } catch (Throwable $e) {
            logger()->error('ExportProgressController: download: Throwable', [
                'Message' => $e->getMessage(),
                'TraceAsString' => $e->getTraceAsString(),
                'batchId' => $batchId,
            ]);

            abort(500, 'An error occurred while downloading the export file');
        }
    }
}
