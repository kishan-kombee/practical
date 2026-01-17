<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Forgot Password API Controller.
 *
 * This controller handles password reset functionality:
 * - sendResetLinkEmail: Send password reset link to user's email
 *
 * @package App\Http\Controllers\API
 */
class ForgotPasswordAPIController extends Controller
{
    use ApiResponseTrait;

    /**
     * Send password reset link to user's email.
     *
     * @param ForgotPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->errorResponse(
                __('messages.login.invalid_email_error'),
                null,
                config('constants.validation_codes.not_found')
            );
        }

        if ($user->status !== config('constants.status.active')) {
            return $this->errorResponse(
                __('messages.login.account_inactive'),
                null,
                config('constants.validation_codes.forbidden')
            );
        }

        $response = Password::broker('users')->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Send successful password reset link response.
     *
     * @param Request $request
     * @param string $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, string $response): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(
            null,
            __('messages.api.forgotpassword_success')
        );
    }

    /**
     * Send failed password reset link response.
     *
     * @param Request $request
     * @param string $response
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    protected function sendResetLinkFailedResponse(Request $request, string $response): \Illuminate\Http\JsonResponse
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }

        return $this->errorResponse(
            trans($response),
            null,
            config('constants.validation_codes.unprocessable_entity')
        );
    }
}
