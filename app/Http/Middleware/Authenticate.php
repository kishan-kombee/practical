<?php

namespace App\Http\Middleware;

use App\Traits\CreatedbyUpdatedby;
use Closure;
use Exception;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class Authenticate extends Middleware
{
    /**
     * @var array<int, string>
     */
    protected $guards = [];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string[] ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->guards = $guards;

        try {
            $this->authenticate($request, $guards);
            $res = parent::handle($request, $next, ...$guards);

            // Activity log started
            $requestURI = $request->getRequestUri();
            $requestURIArr = explode('?', $requestURI);
            $realURI = str_replace('api/v1/', '', $request->path());

            $response = null;
            $isLogActivity = false;

            // Skip activity logging for batch request endpoints
            if ($realURI != 'batch-request' && $realURI != 'auth-batch-request' && $realURI != 'get-activities') {
                if (Str::contains($realURI, 'import-bulk')) {
                    $response = [
                        'description' => 'imported',
                        'properties' => [
                            'attributes' => $res->getData(),
                        ],
                    ];

                    CreatedbyUpdatedby::createLog($request->user(), str_replace('s-import-bulk', '', Str::singular($realURI)), $response, config('constants.activity_log.response_type_enum.3'), true);
                } else {
                    $indexDescription = '';

                    if (isset($requestURIArr[1])) {
                        $queryStringArr = explode('&', $requestURIArr[1]);
                        $indexDescription = CreatedbyUpdatedby::getIndexDescription($queryStringArr, str_replace('s-export', '', Str::singular($realURI)), $indexDescription); // get index method description

                        if ($indexDescription != '' && ! Str::contains($realURI, 'export')) {
                            $response = [
                                'description' => rtrim($indexDescription, '|'),
                                'properties' => [
                                    'attributes' => $res->getData(),
                                ],
                            ];

                            $isLogActivity = true;
                        }
                    }

                    if (Str::contains($realURI, 'export')) {
                        if ($indexDescription != '') {
                            $lblFile = $res->getFile();
                            $filePathArr = explode('/export', $lblFile->getRealPath());

                            $attribute = [
                                'filename' => $lblFile->getFilename(),
                                'file_path' => '/storage/export' . (isset($filePathArr[1]) ? $filePathArr[1] : Str::after($filePathArr[0], '\\export\\')),
                            ];

                            $response = [
                                'description' => 'exported(' . rtrim($indexDescription, '|') . ')',
                                'properties' => [
                                    'attributes' => $attribute,
                                ],
                            ];

                            CreatedbyUpdatedby::createLog($request->user(), str_replace('s-export', '', Str::singular($realURI)), $response, config('constants.activity_log.response_type_enum.3'), true);
                        }
                    } else {
                        if ($isLogActivity && $response !== null) {
                            CreatedbyUpdatedby::createLog($request->user(), Str::singular($realURI), $response, config('constants.activity_log.response_type_enum.2'), true);
                        }
                    }
                }
            }
            // Activity log ended

            return $res;
        } catch (Exception $e) {
            return Response::make('Authorization Token not found', config('constants.validation_codes.unauthorized'));
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return ''; // return route('login');
        }

        return '';
    }
}
