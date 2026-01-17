<?php

namespace App;

use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class Helper
{
    public static function formatDate($dateTime, $format = null)
    {
        $format = is_null($format) ? config('constants.date_formats.default') : $format;

        return Carbon::parse($dateTime)->format($format);
    }

    public static function getImportStatusText($status)
    {
        if ($status == config('constants.import_csv_log.status.key.success')) {
            return Blade::render('<x-flux::badge color="green">' . config('constants.import_csv_log.status.value.success') . '</x-flux::badge>');
        } elseif ($status == config('constants.import_csv_log.status.key.fail')) {
            return Blade::render('<x-flux::badge color="red">' . config('constants.import_csv_log.status.value.fail') . '</x-flux::badge>');
        } elseif ($status == config('constants.import_csv_log.status.key.pending')) {
            return Blade::render('<x-flux::badge color="yellow">' . config('constants.import_csv_log.status.value.pending') . '</x-flux::badge>');
        } elseif ($status == config('constants.import_csv_log.status.key.processing')) {
            return Blade::render('<x-flux::badge color="blue">' . config('constants.import_csv_log.status.value.processing') . '</x-flux::badge>');
        } elseif ($status == config('constants.import_csv_log.status.key.convert_decrypted')) {
            return Blade::render('<x-flux::badge color="blue">' . config('constants.import_csv_log.status.value.convert_decrypted') . '</x-flux::badge>');
        }

        return '-';
    }

    public static function getIp()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return request()->ip(); // it will return server ip when no client ip found
    }

    /**
     * Log validation errors.
     *
     * @param string $controller_name Name of the controller where the error occurred.
     * @param string $function_name Name of the function where the error occurred.
     * @param Validator $validator Validator instance containing error details.
     * @param string $channel Optional log channel (default: 'validation').
     */
    public static function logValidationError(string $controller_name, string $function_name, Validator $validator, $user = null, string $channel = 'validation'): void
    {
        try {
            // Log detailed validation errors
            Log::channel($channel)->error("$controller_name: $function_name: Validation error occurred. :", [
                "\nerrors_message" => $validator->errors()->all(),
                "\nkey_failed" => $validator->failed(),
                "\nall_request" => request()->all(),
                "\ndefault_auth_detail" => $user,
                "\nall_headers" => request()->headers->all(),
                "\nip_address" => self::getIp(),
            ]);
        } catch (Throwable $th) {
            // Log exception details if logging fails
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'validator' => $validator,
                'channel' => $channel,
            ]);
        }
    }

    /**
     * Log exceptions or errors with stack trace and additional details.
     *
     * @param Throwable $th The exception to be logged.
     * @param string $controller_name Name of the controller where the exception occurred.
     * @param string $function_name Name of the function where the exception occurred.
     * @param array $extra_param Optional additional parameters to include in the log.
     * @param string|null $channel Optional log channel (default: null).
     */
    public static function logCatchError(Throwable $th, string $controller_name, string $function_name, array $extra_param = [], $user = null, ?string $channel = null): void
    {
        try {
            // Prepare data for logging
            $dataArray = [
                "\nException" => $th->getMessage(),
                "\nTraceAsString" => $th->getTraceAsString(),
                "\nExtraParam" => $extra_param,
                "\nall_request" => request()->all(),
                "\ndefault_auth_detail" => $user,
                "\nall_headers" => request()->headers->all(),
                "\nip_address" => self::getIp(),
            ];

            // Log the exception
            Log::channel($channel)->error("$controller_name: $function_name: Throwable:", $dataArray);

            // Notify Bugsnag of the exception with additional metadata
            // Bugsnag::notifyException($th, function ($report) use ($dataArray) {
            //     $report->setMetaData(['Additional Information' => $dataArray]);
            // });
        } catch (Throwable $th) {
            // Log details of any error occurring within this method
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'extra_param' => $extra_param,
                'channel' => $channel,
            ]);
        }
    }

    public static function logSingleError($controller_name, $function_name, $message, $extra_param = [], $user = null, $channel = null): void
    {
        try {
            $loggerMessage = "$controller_name: $function_name: $message";
            $dataArray = [
                "\nExtraParam" => $extra_param,
            ];
            Log::channel($channel)->error($loggerMessage, $dataArray);
        } catch (Throwable $th) {
            // Log exception details for debugging purposes
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'message' => $message,
                'extra_param' => $extra_param,
                'channel' => $channel,
            ]);
        }
    }

    /**
     * Log general error messages with additional context.
     *
     * @param string $controller_name Name of the controller.
     * @param string $function_name Name of the function.
     * @param string $message Error message to log.
     * @param array $extra_param Optional additional parameters.
     * @param string|null $channel Optional log channel (default: null).
     */
    public static function logError(string $controller_name, string $function_name, string $message, array $extra_param = [], $user = null, ?string $channel = null): void
    {
        try {
            // Format the log message
            $loggerMessage = "$controller_name: $function_name: $message";

            // Prepare data for logging
            $dataArray = [
                "\nExtraParam" => $extra_param,
                "\nall_request" => request()->all(),
                "\ndefault_auth_detail" => $user,
                "\nall_headers" => request()->headers->all(),
                "\nip_address" => self::getIp(),
            ];

            // Log the error message
            Log::channel($channel)->error($loggerMessage, $dataArray);

            // Notify Bugsnag with error details
            // Bugsnag::notifyError(__FUNCTION__, $loggerMessage, function ($report) use ($dataArray) {
            //     $report->setMetaData(['Additional Information' => $dataArray]);
            // });
        } catch (Throwable $th) {
            // Log exception details if logging fails
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'message' => $message,
                'extra_param' => $extra_param,
                'channel' => $channel,
            ]);
        }
    }

    /**
     * Log a single informational message with optional parameters.
     *
     * @param string $controller_name Name of the controller.
     * @param string $function_name Name of the function.
     * @param string $message Informational message to log.
     * @param array $extra_param Optional additional parameters.
     * @param string|null $channel Optional log channel (default: null).
     */
    public static function logSingleInfo(string $controller_name, string $function_name, string $message, array $extra_param = [], $user = null, ?string $channel = null): void
    {
        try {
            // Format the log message
            $loggerMessage = "$controller_name: $function_name: $message";

            // Prepare data for logging
            $dataArray = [
                "\nExtraParam" => $extra_param,
            ];

            // Log the informational message
            Log::channel($channel)->info($loggerMessage, $dataArray);
        } catch (Throwable $th) {
            // Log exception details if logging fails
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'message' => $message,
                'extra_param' => $extra_param,
                'channel' => $channel,
            ]);
        }
    }

    /**
     * Log general informational messages with additional context.
     *
     * @param string $controller_name Name of the controller.
     * @param string $function_name Name of the function.
     * @param string $message Informational message to log.
     * @param array $extra_param Optional additional parameters.
     * @param string|null $channel Optional log channel (default: null).
     */
    public static function logInfo(string $controller_name, string $function_name, string $message, array $extra_param = [], $user = null, ?string $channel = null): void
    {
        try {
            // Format the log message
            $loggerMessage = "$controller_name: $function_name: $message";

            // Prepare data for logging
            $dataArray = [
                "\nExtraParam" => $extra_param,
                "\nall_request" => request()->all(),
                "\ndefault_auth_detail" => $user,
                "\nall_headers" => request()->headers->all(),
                "\nip_address" => self::getIp(),
            ];

            // Log the informational message
            Log::channel($channel)->info($loggerMessage, $dataArray);
        } catch (Throwable $th) {
            // Log exception details if logging fails
            Log::error(static::class . ': ' . __FUNCTION__ . ': Throwable', [
                'Message' => $th->getMessage(),
                'TraceAsString' => $th->getTraceAsString(),
                'controller_name' => $controller_name,
                'function_name' => $function_name,
                'message' => $message,
                'extra_param' => $extra_param,
                'channel' => $channel,
            ]);
        }
    }

    public static function getAllLegends()
    {
        try {
            $userModel = new User();
            $legendsArray['Users'] = $userModel->legend;

            return $legendsArray;
        } catch (Throwable $th) {
            self::logCatchError($th, static::class, __FUNCTION__);
        }
    }

    public static function getAllRoles()
    {
        return Cache::rememberForever('getAllRoles', function () {
            return Role::pluck('name', 'id')->toArray();
        });
    }

    /**
     * Get all roles as collection (for dropdowns that need full objects).
     * This method returns Role models instead of just name/id pairs.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllRole()
    {
        return Cache::rememberForever('getAllRole', function () {
            return Role::select('id', 'name')->get();
        });
    }

    public static function getAllPermissions()
    {
        return Cache::rememberForever('getAllPermissions', function () {
            return Permission::select('id', 'name', 'guard_name', 'label')->get()->toArray();
        });
    }

    public static function getCachedPermissionsByRole($roleId)
    {
        return Cache::rememberForever("getCachedPermissionsByRole:$roleId", function () use ($roleId) {
            return PermissionRole::leftJoin('permissions', 'permissions.id', '=', 'permission_role.permission_id')
                ->where('permission_role.role_id', $roleId)->pluck('permissions.name')->toArray();
        });
    }

    public static function getStatusBadge($status)
    {
        if ($status == config('constants.email_template.status.active')) {
            return Blade::render('<x-flux::badge color="green">' . config('constants.email_template.status_message.active') . '</x-flux::badge>');
        } elseif ($status == config('constants.email_template.status.inactive')) {
            return Blade::render('<x-flux::badge color="red">' . config('constants.email_template.status_message.inactive') . '</x-flux::badge>');
        }

        return '-';
    }

    public static function processProgressOfExport($functionParams)
    {
        try {
            // Decode function parameters
            $params = json_decode($functionParams, true);

            // Check if batch ID is provided
            if (! $params['batchId']) {
                logger()->error('app/Helper.php: downloadExportFile: batchId not found', ['functionParams' => $functionParams, 'params' => $params]);

                return ['status' => 0, 'message' => __('messages.common_error_message')];
            }

            // Find batch and check for failed jobs
            $batch = Bus::findBatch($params['batchId']);
            if ($batch->failedJobs) {
                logger()->error('app/Helper.php: downloadExportFile: failedJobs', ['functionParams' => $functionParams, 'params' => $params, 'failedJobs' => $batch->failedJobs]);

                return ['status' => 0, 'message' => __('messages.common_error_message')];
            }

            // Check if file is downloadable and batch is finished
            if (isset($params['isFileDownloadable']) && $params['isFileDownloadable'] && $batch->finished()) {
                $downloadableResponse = Helper::downloadExportFile($functionParams);

                return ['status' => 2, 'message' => __('messages.export.exporting_successfully'), 'data' => $downloadableResponse];

                /*$downloadableResponse = Helper::mergeExportFile($functionParams);
            if ($downloadableResponse['status']) {

            return ['status' => 2, 'message' => 'Exporting Successfully.', 'data' => $downloadableResponse['data']];
            } else {

            return ['status' => 0, 'message' => $downloadableResponse['message']];
            }*/
            }

            // Get export progress and update parameters
            $exportProgress = $batch->progress();
            if (isset($params['exportProgress']) && ($params['exportProgress'] != $exportProgress)) {
                // Messages are only change when percentage will change
                $params['waitingMessage'] = Helper::getRandomExportWaitingMessage();
            }

            $params['exportProgress'] = $exportProgress;
            // We are displaying 100% first, after we will process download file. For that we have added isFileDownloadable condition.
            $params['isFileDownloadable'] = $exportProgress == 100 ? 1 : 0;

            return ['status' => 1, 'data' => json_encode($params)];
        } catch (Throwable $e) {
            // Log any exceptions during export progress processing
            logger()->error('app/Helper.php: downloadExportFile: Throwable', ['Message' => $e->getMessage(), 'TraceAsString' => $e->getTraceAsString(), 'functionParams' => $functionParams]);

            return ['status' => 0, 'message' => __('messages.common_error_message')];
        }
    }

    public static function getAllCategory()
    {
        return Cache::rememberForever('getAllCategory', function () {
            return Models\Category::pluck('name', 'id')->toArray();
        });
    }

    public static function getAllActiveCategory()
    {
        return Cache::rememberForever('getAllActiveCategory', function () {
            return Models\Category::where('status', (string) config('constants.category.status.key.active'))
                ->whereNull('deleted_at')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    public static function getAllActiveSubCategory()
    {
        return Cache::rememberForever('getAllActiveSubCategory', function () {
            return Models\SubCategory::where('status', (string) config('constants.sub_category.status.key.active'))
                ->whereNull('deleted_at')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    public static function getSubCategoriesByCategory($categoryId)
    {
        return Cache::rememberForever("getSubCategoriesByCategory:{$categoryId}", function () use ($categoryId) {
            return Models\SubCategory::where('category_id', $categoryId)
                ->where('status', (string) config('constants.sub_category.status.key.active'))
                ->whereNull('deleted_at')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get all active users (clinicians) for appointment assignment.
     *
     * @return array
     */
    public static function getAllActiveClinicians()
    {
        return Cache::rememberForever('getAllActiveClinicians', function () {
            return Models\User::where('status', 'Y')
                ->whereNull('deleted_at')
                ->select('id', 'first_name', 'last_name')
                ->get()
                ->mapWithKeys(function ($user) {
                    return [$user->id => $user->first_name . ' ' . $user->last_name];
                })
                ->toArray();
        });
    }

    /**
     * Format product price with currency symbol and thousand separators.
     *
     * @param float|string|null $price The price to format
     * @param string $currency The currency symbol (default: empty, can be '$', '€', etc.)
     * @param int $decimals Number of decimal places (default: 2)
     * @param string $decimalSeparator Decimal separator (default: '.')
     * @param string $thousandsSeparator Thousands separator (default: ',')
     * @return string Formatted price string
     */
    public static function formatProductPrice($price, string $currency = '', int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        if ($price === null || $price === '') {
            return $currency . '0' . $decimalSeparator . str_repeat('0', $decimals);
        }

        $price = (float) $price;
        $formatted = number_format($price, $decimals, $decimalSeparator, $thousandsSeparator);

        return $currency . $formatted;
    }

    /**
     * Generate a unique item code for products.
     *
     * @param string $prefix The prefix for the item code (default: 'PRD')
     * @param int $length The length of the numeric part (default: 6)
     * @param bool $includeTimestamp Whether to include timestamp for uniqueness (default: false)
     * @return string Generated unique item code
     */
    public static function generateItemCode(string $prefix = 'PRD', int $length = 6, bool $includeTimestamp = false): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            if ($includeTimestamp) {
                // Generate code with timestamp for better uniqueness
                $timestamp = now()->format('YmdHis');
                $random = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
                $itemCode = strtoupper($prefix) . '-' . $timestamp . '-' . $random;
            } else {
                // Generate code with random numbers
                $random = str_pad((string) random_int(0, (int) str_repeat('9', $length)), $length, '0', STR_PAD_LEFT);
                $itemCode = strtoupper($prefix) . '-' . $random;
            }

            // Check if item code already exists
            $exists = Models\Product::where('item_code', $itemCode)->exists();
            $attempt++;

            if (!$exists) {
                return $itemCode;
            }
        } while ($attempt < $maxAttempts);

        // If all attempts failed, add timestamp to ensure uniqueness
        $timestamp = now()->format('YmdHis');
        $random = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);

        return strtoupper($prefix) . '-' . $timestamp . '-' . $random;
    }

    /**
     * Get status label for products.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string The status label
     */
    public static function getProductStatusLabel(?string $status): string
    {
        if ($status === null) {
            return 'Unknown';
        }

        $statusOptions = Models\Product::available_status();
        $statusOption = $statusOptions->firstWhere('key', $status);

        return $statusOption ? $statusOption['label'] : 'Unknown';
    }

    /**
     * Get status label for categories.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string The status label
     */
    public static function getCategoryStatusLabel(?string $status): string
    {
        if ($status === null) {
            return 'Unknown';
        }

        $statusOptions = Models\Category::status();
        $statusOption = $statusOptions->firstWhere('key', $status);

        return $statusOption ? $statusOption['label'] : 'Unknown';
    }

    /**
     * Get status label for subcategories.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string The status label
     */
    public static function getSubCategoryStatusLabel(?string $status): string
    {
        if ($status === null) {
            return 'Unknown';
        }

        $statusOptions = Models\SubCategory::status();
        $statusOption = $statusOptions->firstWhere('key', $status);

        return $statusOption ? $statusOption['label'] : 'Unknown';
    }

    /**
     * Get status badge HTML for products.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string HTML badge
     */
    public static function getProductStatusBadge(?string $status): string
    {
        $label = self::getProductStatusLabel($status);
        $color = $status === '1' ? 'green' : 'red';

        return Blade::render("<x-flux::badge color=\"{$color}\">{$label}</x-flux::badge>");
    }

    /**
     * Get status badge HTML for categories.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string HTML badge
     */
    public static function getCategoryStatusBadge(?string $status): string
    {
        $label = self::getCategoryStatusLabel($status);
        $color = $status === '1' ? 'green' : 'red';

        return Blade::render("<x-flux::badge color=\"{$color}\">{$label}</x-flux::badge>");
    }

    /**
     * Get status badge HTML for subcategories.
     *
     * @param string|null $status The status value ('0' or '1')
     * @return string HTML badge
     */
    public static function getSubCategoryStatusBadge(?string $status): string
    {
        $label = self::getSubCategoryStatusLabel($status);
        $color = $status === '1' ? 'green' : 'red';

        return Blade::render("<x-flux::badge color=\"{$color}\">{$label}</x-flux::badge>");
    }
}
