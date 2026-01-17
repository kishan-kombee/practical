<?php

namespace App\Http\Controllers\API;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\DataTrueResource;
use App\Http\Resources\LoginResource;
use App\Models\LoginHistory;
use App\Models\User;
use App\Rules\ReCaptcha;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

/**
 * Login API Controller.
 *
 * This controller handles the authentication API endpoints:
 * - login: Authenticate user and create token
 * - changePassword: Change user password
 * - refreshingTokens: Refresh access token
 * - logout: Invalidate current token
 *
 * @package App\Http\Controllers\API
 */
class LoginAPIController extends Controller
{
    use ApiResponseTrait;

    /**
     * Login user and create token.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        // ========================================================================
        // STEP 1: Verify Google reCAPTCHA if enabled
        // ========================================================================
        if (config('constants.check_google_recaptcha')) {
            $recaptchaToken = $request->input('recaptcha_token');

            if (! $recaptchaToken) {
                return $this->errorResponse(
                    __('messages.login.recaptchaError'),
                    null,
                    config('constants.validation_codes.bad_request')
                );
            }

            $recaptchaResponse = ReCaptcha::verify($recaptchaToken);

            if (! $recaptchaResponse['success']) {
                Helper::logInfo(static::class, __FUNCTION__, __('messages.login.recaptchaError'), [
                    'email' => $request->email,
                ]);

                return $this->errorResponse(
                    __('messages.login.recaptchaError'),
                    null,
                    config('constants.validation_codes.bad_request')
                );
            }
        }

        // ========================================================================
        // STEP 2: Validate user credentials
        // ========================================================================
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                __('messages.login.wrong_credentials'),
                null,
                config('constants.validation_codes.unauthorized')
            );
        }

        // Check if user status is active
        if ($user->status !== config('constants.status.active')) {
            return $this->errorResponse(
                __('messages.login.account_inactive'),
                null,
                config('constants.validation_codes.forbidden')
            );
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return $this->errorResponse(
                __('messages.login.email_not_verified'),
                null,
                config('constants.validation_codes.forbidden')
            );
        }

        // ========================================================================
        // STEP 3: Delete existing tokens and create new token
        // ========================================================================
        $user->tokens()->delete();

        // Get user permissions for token abilities
        $permissions = \App\Helper::getCachedPermissionsByRole($user->role_id);

        // Calculate token expiration (default: 24 hours from config)
        $expiresAt = now()->addSeconds(config('constants.token_expiry'));

        // Create token and set expiration
        /** @var \Laravel\Sanctum\NewAccessToken $tokenResult */
        $tokenResult = $user->createToken('Login Token', $permissions);
        $tokenModel = $tokenResult->accessToken;
        $tokenModel->expires_at = $expiresAt;
        $tokenModel->save();

        // ========================================================================
        // STEP 4: Record login history
        // ========================================================================
        try {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
            ]);
        } catch (Throwable $th) {
            // Log error but don't fail the login if history recording fails
            Helper::logCatchError($th, static::class, __FUNCTION__, [
                'message' => 'Failed to record login history',
                'user_id' => $user->id,
            ]);
        }

        // ========================================================================
        // STEP 5: Build and return successful login response
        // ========================================================================
        $user->authorization = $tokenResult->plainTextToken;
        $user->refresh_token = $tokenModel->id;
        $user->token_expires_at = $tokenModel->expires_at;

        // Log successful token generation in local environment
        Helper::logSingleInfo(static::class, __FUNCTION__, 'Token generated successfully', [
            'user_email' => $request->email,
        ]);

        return $this->successResponse(
            (new LoginResource($user))->resolve(),
            __('messages.login.success')
        );
    }

    /**
     * Change password functionality.
     *
     * @param ChangePasswordRequest $request
     * @return DataTrueResource|\Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): DataTrueResource|\Illuminate\Http\JsonResponse
    {
        // get all updated data.
        $data = $request->all();
        $masterUser = User::where('email', $request->user()->email)->first();
        if (Hash::check($data['old_password'], $masterUser->password)) {
            $masterUser->password = Hash::make($data['new_password']);
            // update user password in master user table
            if ($masterUser->save()) {
                return $this->successResponse(
                    null,
                    __('messages.api.password_changed')
                );
            } else {
                return $this->errorResponse(
                    __('messages.api.something_wrong')
                );
            }
        } else {
            return $this->errorResponse(
                __('messages.api.invalid_old_password'),
                null,
                config('constants.validation_codes.unprocessable_entity')
            );
        }
    }

    /**
     * Refresh access token using refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshingTokens(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // ========================================================================
            // STEP 1: Validate refresh token
            // ========================================================================
            $request->validate([
                'refresh_token' => 'required',
            ]);

            // ========================================================================
            // STEP 2: Find token by ID and get user
            // ========================================================================
            // Refresh token is stored as the token ID, so find by ID
            $token = PersonalAccessToken::find($request->refresh_token);

            if (! $token) {
                return $this->errorResponse(
                    __('messages.api.login.invalid_refresh_token'),
                    null,
                    config('constants.validation_codes.unauthorized')
                );
            }

            $user = $token->tokenable;

            if (! $user || !($user instanceof User)) {
                Helper::logSingleInfo(static::class, __FUNCTION__, 'User not found for access token during refresh.', [
                    'token_id' => $request->refresh_token,
                ]);

                return $this->errorResponse(
                    __('messages.api.login.user_not_found'),
                    null,
                    config('constants.validation_codes.unauthorized')
                );
            }

            // Check if user status is active
            if ($user->status !== config('constants.status.active')) {
                return $this->errorResponse(
                    __('messages.login.account_inactive'),
                    null,
                    config('constants.validation_codes.forbidden')
                );
            }

            // Check if email is verified
            if (!$user->hasVerifiedEmail()) {
                return $this->errorResponse(
                    __('messages.login.email_not_verified'),
                    null,
                    config('constants.validation_codes.forbidden')
                );
            }

            // ========================================================================
            // STEP 3: Delete existing tokens and create new token
            // ========================================================================
            $user->tokens()->delete();

            // Get user permissions for token abilities
            $permissions = \App\Helper::getCachedPermissionsByRole($user->role_id);

            // Calculate token expiration (default: 24 hours from config)
            $expiresAt = now()->addSeconds(config('constants.token_expiry'));

            // Create token and set expiration
            /** @var \Laravel\Sanctum\NewAccessToken $tokenResult */
            $tokenResult = $user->createToken('Login Token', $permissions);
            $tokenModel = $tokenResult->accessToken;
            $tokenModel->expires_at = $expiresAt;
            $tokenModel->save();

            // ========================================================================
            // STEP 4: Build and return successful refresh response
            // ========================================================================
            $user->authorization = $tokenResult->plainTextToken;
            $user->refresh_token = $tokenModel->id;
            $user->token_expires_at = $tokenModel->expires_at;

            // Log successful token refresh in local environment
            Helper::logSingleInfo(static::class, __FUNCTION__, 'Token refreshed successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            return $this->successResponse([
                'authorization' => $user->authorization,
                'refresh_token' => $user->refresh_token,
                'token_expires_at' => $user->token_expires_at ? (is_string($user->token_expires_at) ? \Carbon\Carbon::parse($user->token_expires_at)->format(config('constants.api_datetime_format')) : $user->token_expires_at->format(config('constants.api_datetime_format'))) : '',
            ], __('messages.api.token_refreshed_successfully'));
        } catch (Throwable $th) {
            Helper::logCatchError($th, static::class, __FUNCTION__);

            return $this->errorResponse(
                __('messages.api.list_fail'),
                null,
                config('constants.validation_codes.internal_server_error')
            );
        }
    }

    /**
     * Logout user and invalidate current token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        // Delete the current access token
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            null,
            __('messages.login.logout')
        );
    }
}
