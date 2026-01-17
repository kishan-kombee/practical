<?php

use App\Livewire\Dashboard;
use App\Livewire\Settings\Password;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

Route::post('upload-file', [App\Http\Controllers\API\UserAPIController::class, 'uploadFile'])->name('uploadFile');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Settings
    Route::get('settings/password', Password::class)->name('settings.password');

    Route::get('email-format', App\Livewire\EmailFormat\Edit::class)->name('email-format');
    Route::get('email-templates', App\Livewire\EmailTemplate\Index::class)->name('email-template.index');
    Route::get('email-template/{id}/edit', App\Livewire\EmailTemplate\Edit::class)->name('email-template.edit');

    // Permission Management
    Route::get('permission', App\Livewire\Permission\Edit::class)->name('permission');

    // SSE Export
    Route::get('export-stream/stream', [App\Http\Controllers\ExportStreamController::class, 'stream'])->name('export-stream.stream');
    Route::get('export-stream/status', [App\Http\Controllers\ExportStreamController::class, 'status'])->name('export-stream.status');
    Route::post('export-stream/cancel', [App\Http\Controllers\ExportStreamController::class, 'cancel'])->name('export-stream.cancel');
    Route::post('export-stream/cleanup', [App\Http\Controllers\ExportStreamController::class, 'cleanup'])->name('export-stream.cleanup');
    Route::get('export-progress/stream', [App\Http\Controllers\ExportProgressController::class, 'stream'])->name('export-progress.stream');
    Route::get('export-progress/download/{batchId}', [App\Http\Controllers\ExportProgressController::class, 'download'])->name('export.download');

    /* Admin - Role Module */
    Route::get('/role', App\Livewire\Role\Index::class)->name('role.index'); // Role Listing
    Route::get('/role-imports', App\Livewire\Role\Import\IndexImport::class)->name('role.imports'); // Import history

    /* Admin - User Module */
    Route::get('/user', App\Livewire\User\Index::class)->name('user.index'); // User Listing
    Route::get('/user/create', App\Livewire\User\Create::class)->name('user.create'); // Create User
    Route::get('/user/{id}/edit', App\Livewire\User\Edit::class)->name('user.edit'); // Edit User
    /* Admin - Category Module */
    Route::get('/category', App\Livewire\Category\Index::class)->name('category.index'); // Category Listing
    Route::get('/category/create', App\Livewire\Category\Create::class)->name('category.create'); // Create Category
    Route::get('/category/{id}/edit', App\Livewire\Category\Edit::class)->name('category.edit'); // Edit Category

    /* Admin - SubCategory Module */
    Route::get('/sub_category', App\Livewire\SubCategory\Index::class)->name('sub_category.index'); // SubCategory Listing
    Route::get('/sub_category/create', App\Livewire\SubCategory\Create::class)->name('sub_category.create'); // Create SubCategory
    Route::get('/sub_category/{id}/edit', App\Livewire\SubCategory\Edit::class)->name('sub_category.edit'); // Edit SubCategory

    /* Admin - Product Module */
    Route::get('/product', App\Livewire\Product\Index::class)->name('product.index'); // Product Listing
    Route::get('/product/create', App\Livewire\Product\Create::class)->name('product.create'); // Create Product
    Route::get('/product/{id}/edit', App\Livewire\Product\Edit::class)->name('product.edit'); // Edit Product

    /* Admin - Appointment Module */
    Route::get('/appointment', App\Livewire\Appointment\Index::class)->name('appointment.index'); // Appointment Listing
    Route::get('/appointment/create', App\Livewire\Appointment\Create::class)->name('appointment.create'); // Create Appointment
    Route::get('/appointment/{id}/edit', App\Livewire\Appointment\Edit::class)->name('appointment.edit'); // Edit Appointment
    /* Admin - SmsTemplate Module */
    Route::get('/sms-template', App\Livewire\SmsTemplate\Index::class)->name('sms-template.index'); // SmsTemplate Listing
    Route::get('/sms-template/create', App\Livewire\SmsTemplate\Create::class)->name('sms-template.create'); // Create SmsTemplate
    Route::get('/sms-template/{id}/edit', App\Livewire\SmsTemplate\Edit::class)->name('sms-template.edit'); // Edit SmsTemplate
});
