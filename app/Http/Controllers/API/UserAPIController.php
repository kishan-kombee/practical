<?php

namespace App\Http\Controllers\API;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\UploadTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

class UserAPIController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * @return JsonResponse
     */
    public function uploadFile(Request $request)
    {
        try {
            if ($request->get('modelName') == config('constants.import_csv_log.models.cash_token_point')) {
                $validation = 'required|max:10240'; // 10 MB
            } else {
                $validation = 'required|mimes:csv,txt|max:10240'; // 10 MB
            }

            $request->validate([
                'file' => $validation,
            ]);

            if ($request->hasFile('file')) {
                $uploadFilePath = User::uploadOne($request->file('file'), $request->get('folderName'));
                if ($uploadFilePath != false) {
                    /* Insert data into the import_logs table */
                    ImportLog::create(
                        [
                            'file_name' => basename($uploadFilePath),
                            'file_path' => $uploadFilePath,
                            'model_name' => $request->get('modelName'),
                            'status' => config('constants.import_csv_log.status.key.pending'),
                            'import_flag' => config('constants.import_csv_log.import_flag.key.pending'),
                            'user_id' => $request->get('userId'),
                        ]
                    );

                    return $this->successResponse(
                        null,
                        __('messages.import_history.messages.success')
                    );
                } else {
                    return $this->errorResponse(
                        __('messages.something_went_wrong'),
                        null,
                        config('constants.validation_codes.unprocessable_entity')
                    );
                }
            } else {
                return $this->errorResponse(
                    __('messages.import_history.messages.validate_error'),
                    null,
                    config('constants.validation_codes.unprocessable_entity')
                );
            }
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__);

            return $this->errorResponse($th->getMessage());
        }
    }

    public function uploadEditorFile(Request $request)
    {
        try {
            $file = null;
            $fieldName = '';

            // Check which field contains the file
            if ($request->hasFile('upload')) {
                $file = $request->file('upload');
                $fieldName = 'upload_editor_image';
            } elseif ($request->hasFile('file')) {
                $file = $request->file('file');
                $fieldName = 'file_editor_image';
            } else {
                return response()->json([
                    'success' => false,
                    'uploaded' => false,
                    'error' => [
                        'message' => 'No file was uploaded',
                    ],
                ], config('constants.validation_codes.bad_request'));
            }

            // Process the file using UploadTrait
            $realPath = 'image_bucket/editor_image/';
            $result = $this->compressAndUploadToS3($file, $realPath, true);

            if (! isset($result['image'])) {
                return response()->json([
                    'success' => false,
                    'uploaded' => false,
                    'error' => [
                        'message' => 'Failed to upload image',
                    ],
                ], 500);
            }

            $imageUrl = Storage::url($result['image']);

            // Return the response in the format expected by TinyMCE
            return response()->json([
                'success' => true,
                'uploaded' => true,
                'url' => $imageUrl, // Add the 'url' field that TinyMCE expects
                'location' => $imageUrl,
            ]);
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__);

            return response()->json([
                'success' => false,
                'uploaded' => false,
                'error' => [
                    'message' => $th->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Batch Request - Process multiple API requests in a single call
     * Maximum 10 requests allowed per batch
     *
     * @return JsonResponse
     */
    public function batchRequest(Request $requestObj)
    {
        try {
            $requests = $requestObj->get('request'); // Get request array
            $output = [];
            $cnt = 0;

            if (! is_array($requests) || empty($requests)) {
                return $this->errorResponse('Invalid request format. Expected array of requests.');
            }

            foreach ($requests as $request) {
                // Limit maximum call to 10 requests
                if ($cnt == 10) {
                    break;
                }

                // Convert array request to object
                $request = (object) $request;

                // Handle relative URLs - prepend base URL if not absolute
                $requestUrl = $request->url;
                if (! preg_match('#^https?://#', $requestUrl)) {
                    // Relative URL - prepend base URL from current request
                    $baseUrl = $requestObj->getSchemeAndHttpHost();
                    $requestUrl = rtrim($baseUrl, '/') . '/' . ltrim($requestUrl, '/');
                }

                // Parse URL and query strings
                $url = parse_url($requestUrl);
                $query = [];
                if (isset($url['query'])) {
                    parse_str($url['query'], $query);
                }

                // Prepare server variables
                $server = [
                    'HTTP_HOST' => preg_replace('#^https?://#', '', URL::to('/')),
                    'HTTPS' => 'on',
                ];

                // Create internal request
                $req = Request::create($requestUrl, 'GET', $query, [], [], $server);

                // Set accept header
                $req->headers->set('Accept', 'application/json');

                // Pass Authorization header if user is authenticated
                if ($requestObj->user() && $requestObj->header('Authorization')) {
                    $req->headers->set('Authorization', $requestObj->header('Authorization'));
                }

                // Handle the request
                $res = app()->handle($req);

                // Store response with request_id if provided, otherwise use index
                if (isset($request->request_id)) {
                    $output[$request->request_id] = json_decode($res->getContent());
                } else {
                    $output[] = json_decode($res->getContent());
                }

                $cnt++; // Increment request counter
            }

            return $this->successResponse(['response' => $output], 'Batch request processed successfully');
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__);

            return $this->errorResponse($th->getMessage());
        }
    }
}
