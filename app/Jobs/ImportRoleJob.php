<?php

namespace App\Jobs;

use App\Helper;
use App\Imports\RoleImport;
use App\Models\ImportLog;
use App\Models\Role;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportRoleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Fetch pending import logs for roles
            $importLogs = ImportLog::where('model_name', config('constants.import_csv_log.models.role'))
                ->where('status', config('constants.import_csv_log.status.key.pending'))
                ->where('import_flag', config('constants.import_csv_log.import_flag.key.pending'))
                ->get();

            // If there are pending import logs
            if (! $importLogs->isEmpty()) {
                foreach ($importLogs as $importLog) {
                    // Log initiation of role data import processing
                    Log::info('Initiate processing of role data import: ' . $importLog->file_name);

                    // Update status to Processing in import_logs table
                    $this->updateStatus($importLog, config('constants.import_csv_log.status.key.processing'));

                    // Log status modification in import_logs table
                    Log::info('Status has been modified to "processing" in the import_logs table.');

                    // Initialize roleImport model
                    $model = new RoleImport($importLog->user_id);

                    // Decode file path and retrieve the file name
                    $path = urldecode(parse_url($importLog->file_path, PHP_URL_PATH));

                    // Generate redirect link for display after import
                    $redirectLink = url('role-imports?filename=' . $importLog->file_name);

                    // Set email subject for import notification
                    $subject = config('constants.import_csv_log.subject.role');

                    // Initiate common import process for roles
                    Role::commonImport($model, $path, $importLog->model_name, $importLog->file_name, $redirectLink, $subject, $importLog);

                    // Log successful file import
                    Log::info('File Imported successfully.(' . $importLog->file_name . ')');
                }
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur during contractor import
            Log::error('Import role Exception Occurred: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            Helper::logCatchError($e, static::class, __FUNCTION__);
        }
    }

    /**
     * updateStatus
     */
    public function updateStatus($importLog, $status)
    {
        $importLog->status = $status;
        $importLog->update();
    }
}
