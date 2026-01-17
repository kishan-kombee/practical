<?php

namespace App\Http\Controllers\API;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\UploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Profile Details API Controller.
 *
 * This controller handles the user profile API endpoints:
 * - getProfileDetails: Get current user's profile
 * - updateProfileDetails: Update user profile information
 * - updateProfileImage: Update user profile image
 *
 * @package App\Http\Controllers\API
 */
class ProfileDetailsAPIController extends Controller
{
    use ApiResponseTrait;
    use UploadTrait;

    /**
     * Get current user's profile details.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->errorResponse(
                    __('messages.common_error_message'),
                    null,
                    config('constants.validation_codes.unauthorized')
                );
            }

            $profileDetails = User::find($user->id);

            if (! $profileDetails) {
                return $this->notFoundResponse(__('messages.record_not_found'));
            }

            // Eager load relationships if needed
            $profileDetails->load(['role:id,name']);

            return $this->successResponse(
                (new UserResource($profileDetails))->resolve(),
                __('messages.profile.profile_retrieved_successfully')
            );
        } catch (Throwable $th) {
            // Log error
            Helper::logCatchError($th, static::class, __FUNCTION__, [], $request->user());

            return $this->errorResponse(
                __('messages.common_error_message'),
                null,
                config('constants.validation_codes.internal_server_error')
            );
        }
    }

    /**
     * Update current user's profile details.
     *
     * @param UserUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileDetails(UserUpdateRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return $this->errorResponse(
                    __('messages.common_error_message'),
                    null,
                    config('constants.validation_codes.unauthorized')
                );
            }

            $profileDetails = User::find($user->id);

            if (! $profileDetails) {
                return $this->notFoundResponse(__('messages.record_not_found'));
            }

            $data = $request->all();

            // Handle file uploads
            // Handle profile image upload if provided
            if ($request->hasFile('profile')) {
                // Delete old file if exists
                if ($profileDetails->profile && Storage::exists($profileDetails->profile)) {
                    Storage::delete($profileDetails->profile);
                }
                $realPath = 'user/' . $profileDetails->id . '/profile/';
                $resizeImages = self::resizeImages($request->file('profile'), $realPath, false, false);
                $data['profile'] = $resizeImages['image'];
            }

            $profileDetails->fill($data);
            $profileDetails->save();

            // Eager load relationships
            $profileDetails->load(['role:id,name']);

            return $this->successResponse(
                (new UserResource($profileDetails))->resolve(),
                __('messages.profile.profile_updated_successfully')
            );
        } catch (Throwable $th) {
            // Log error
            Helper::logCatchError($th, static::class, __FUNCTION__, [], $request->user());

            return $this->errorResponse(
                __('messages.api.user.something_went_wrong'),
                null,
                config('constants.validation_codes.internal_server_error')
            );
        }
    }

    /**
     * Update current user's profile image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                __('messages.common_error_message'),
                null,
                config('constants.validation_codes.unauthorized')
            );
        }

        $profileDetails = User::find($user->id);

        if (! $profileDetails) {
            return $this->notFoundResponse(__('messages.record_not_found'));
        }

        // Validate image upload
        $request->validate([
            'profile' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        if (! $request->hasFile('profile')) {
            return $this->errorResponse(
                __('messages.profile.image_required'),
                null,
                config('constants.validation_codes.unprocessable_entity')
            );
        }

        // Handle file upload
        $data = [];
        // Delete old file if exists
        if ($profileDetails->profile && Storage::exists($profileDetails->profile)) {
            Storage::delete($profileDetails->profile);
        }
        $realPath = 'user/' . $profileDetails->id . '/profile/';
        $resizeImages = self::resizeImages($request->file('profile'), $realPath, false, false);
        $data['profile'] = $resizeImages['image'];

        // Update profile with uploaded file path
        $profileDetails->fill($data);
        $profileDetails->save();

        // Eager load relationships
        $profileDetails->load(['role:id,name']);

        return $this->successResponse(
            (new UserResource($profileDetails))->resolve(),
            __('messages.profile_updated_successfully')
        );
    }
}
