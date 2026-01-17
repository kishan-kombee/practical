<?php

namespace App\Traits;

use App\Models\ImportLog;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

trait ImportTrait
{
    use Mailable;

    /**
     * Common Import Method
     */
    public static function commonImport($model, $path, $modelType, $filename, $redirectLink, $subject, $importLog = null)
    {
        Excel::import($model, $path);
        if (count($model->getErrors()) > 0) {
            $newPath = str_replace('new', 'fail', $path);
            $newPath = str_replace('.csv', '_' . config('constants.calender.import_format') . '_fail.csv', $newPath);
            Storage::move($path, $newPath);

            self::commonForImportLogAndEmail($filename, $newPath, $modelType, config('constants.import_csv_log.status.key.fail'), null, json_encode($model->getErrors()), $importLog);
            self::sendImportFail($model, ['{{model_type}}' => $modelType, '{{file_name}}' => $filename, '{{subject}}' => $subject], $redirectLink);
        } else {
            $newPath = str_replace('new', 'success', $path);
            $newPath = str_replace('.csv', '_' . config('constants.calender.import_format') . '_success.csv', $newPath);
            Storage::move($path, $newPath);

            self::commonForImportLogAndEmail($filename, $newPath, $modelType, config('constants.import_csv_log.status.key.success'), $model->getRowCount(), null, $importLog);

            self::sendImportSuccess($model, ['{{row_count}}' => $model->getRowCount(), '{{model_type}}' => $modelType, '{{file_name}}' => $filename, '{{subject}}' => $subject], $redirectLink);
        }
    }

    /**
     * Common method for import log stored and send email of success or failure
     */
    public static function commonForImportLogAndEmail($filename, $path, $modelType, $status, $no_of_rows, $error_log, $importLog)
    {
        if (! is_null($importLog)) {
            $importLog->import_flag = config('constants.import_csv_log.import_flag.key.success');
            $importLog->status = $status; // Update status to Success in import_logs table
            $importLog->no_of_rows = $no_of_rows; // Update insert number of rows in import_logs table
            $importLog->error_log = $error_log;
            $importLog->update();
        } else {
            ImportLog::create([
                'file_name' => $filename,
                'file_path' => $path,
                'model_name' => $modelType,
                'status' => $status,
                'import_flag' => config('constants.import_csv_log.import_flag.key.success'),
                'no_of_rows' => $no_of_rows,
                'error_log' => $error_log,
            ]);
        }
    }
}
