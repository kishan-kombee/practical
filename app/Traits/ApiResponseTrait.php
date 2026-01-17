<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int|null $statusCode Optional status code, defaults to 200 from constants
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = '', ?int $statusCode = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $statusCode = $statusCode ?? config('constants.validation_codes.ok');

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int|null $statusCode Optional status code, defaults to 400 from constants
     * @return JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, ?int $statusCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $statusCode = $statusCode ?? config('constants.validation_codes.bad_request');

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse($errors, string $message = ''): JsonResponse
    {
        return $this->errorResponse(
            $message ?: __('messages.api.validation_errors'),
            $errors,
            config('constants.validation_codes.unprocessable_entity')
        );
    }

    /**
     * Return a not found JSON response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = ''): JsonResponse
    {
        return $this->errorResponse(
            $message ?: __('messages.api.not_found'),
            null,
            config('constants.validation_codes.not_found')
        );
    }

    /**
     * Return an unauthorized JSON response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = ''): JsonResponse
    {
        return $this->errorResponse(
            $message ?: __('messages.api.unauthorized'),
            null,
            config('constants.validation_codes.unauthorized')
        );
    }

    /**
     * Return a forbidden JSON response.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = ''): JsonResponse
    {
        return $this->errorResponse(
            $message ?: __('messages.api.forbidden'),
            null,
            config('constants.validation_codes.forbidden')
        );
    }

    /**
     * Return a created JSON response (201).
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse($data = null, string $message = ''): JsonResponse
    {
        return $this->successResponse(
            $data,
            $message,
            config('constants.validation_codes.created')
        );
    }
}
