<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    // Authentication routes (rate limited: 5 requests per minute)
    Route::post('login', [App\Http\Controllers\API\LoginAPIController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('forgot-password', [App\Http\Controllers\API\ForgotPasswordAPIController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1');

    // Batch Request (rate limited: 10 requests per minute)
    Route::get('batch-request', [App\Http\Controllers\API\UserAPIController::class, 'batchRequest'])
        ->middleware('throttle:10,1');

    // Token refresh (rate limited: 10 requests per minute)
    Route::post('refreshing-tokens', [App\Http\Controllers\API\LoginAPIController::class, 'refreshingTokens'])
        ->middleware('throttle:10,1');

    // Authenticated routes (rate limited: 60 requests per minute)
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        // Batch Request - Authenticated
        Route::get('auth-batch-request', [App\Http\Controllers\API\UserAPIController::class, 'batchRequest']);

        Route::post('change-password', [App\Http\Controllers\API\LoginAPIController::class, 'changePassword']);
        Route::post('logout', [App\Http\Controllers\API\LoginAPIController::class, 'logout']);

        // Profile routes
        Route::get('profile', [App\Http\Controllers\API\ProfileDetailsAPIController::class, 'getProfileDetails']);
        Route::post('profile', [App\Http\Controllers\API\ProfileDetailsAPIController::class, 'updateProfileDetails']);
        Route::post('profile/image', [App\Http\Controllers\API\ProfileDetailsAPIController::class, 'updateProfileImage']);

        // Notification routes
        Route::get('notifications', [App\Http\Controllers\API\NotificationAPIController::class, 'index']);
        Route::get('notifications/count', [App\Http\Controllers\API\NotificationAPIController::class, 'count']);
        Route::post('notifications/{id}/read', [App\Http\Controllers\API\NotificationAPIController::class, 'read']);

        // API Routes for Product
        Route::apiResource('products', App\Http\Controllers\API\ProductAPIController::class);
        Route::post('products/delete-multiple', [App\Http\Controllers\API\ProductAPIController::class, 'deleteAll']);
        // API Routes for Appointment
        Route::apiResource('appointments', App\Http\Controllers\API\AppointmentAPIController::class);
        Route::post('appointments/delete-multiple', [App\Http\Controllers\API\AppointmentAPIController::class, 'deleteAll']);
    });
});
