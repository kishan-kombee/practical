<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use App\Observers\SubCategoryObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (App::environment(['local'])) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Service Container Bindings
        $this->registerServiceBindings();
    }

    /**
     * Register service container bindings for interfaces and implementations.
     *
     * @return void
     */
    private function registerServiceBindings(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(
            \App\Services\Contracts\ProductServiceInterface::class,
            \App\Services\ProductService::class
        );

        $this->app->bind(
            \App\Services\Contracts\AppointmentServiceInterface::class,
            \App\Services\AppointmentService::class
        );

        // Register services as singletons for better performance
        // (Same instance will be reused across the application)
        $this->app->singleton(
            \App\Services\ProductService::class,
            function ($app) {
                return new \App\Services\ProductService();
            }
        );

        $this->app->singleton(
            \App\Services\AppointmentService::class,
            function ($app) {
                return new \App\Services\AppointmentService();
            }
        );

        // Bind ActivityLogService as singleton
        $this->app->singleton(
            \App\Services\ActivityLogService::class,
            function ($app) {
                return new \App\Services\ActivityLogService();
            }
        );

        // Contextual binding example: Different implementations based on context
        // This allows you to bind different implementations in different contexts
        // For example, you might want a different ProductService for API vs Web

        // Example contextual binding (commented out - uncomment if needed):
        // $this->app->when(\App\Http\Controllers\API\ProductAPIController::class)
        //     ->needs(\App\Services\Contracts\ProductServiceInterface::class)
        //     ->give(\App\Services\ProductService::class);

        // $this->app->when(\App\Livewire\Product\Create::class)
        //     ->needs(\App\Services\Contracts\ProductServiceInterface::class)
        //     ->give(\App\Services\ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register Model Observers
        Category::observe(CategoryObserver::class);
        SubCategory::observe(SubCategoryObserver::class);
        Product::observe(ProductObserver::class);

        $permissions = [
            'edit-emailformats',
            'view-emailformats',
            'edit-emailtemplates',
            'show-emailtemplates',
            'view-emailtemplates',
            'view-role',
            'show-role',
            'add-role',
            'edit-role',
            'delete-role',
            'bulkDelete-role',
            'import-role',
            'export-role',
            'role-imports',
            'view-user',
            'show-user',
            'add-user',
            'edit-user',
            'delete-user',
            'bulkDelete-user',
            'import-user',
            'export-user',
            'user-imports',
            'view-category',
            'show-category',
            'add-category',
            'edit-category',
            'delete-category',
            'bulkDelete-category',
            'export-category',
            'view-sub_category',
            'show-sub_category',
            'add-sub_category',
            'edit-sub_category',
            'delete-sub_category',
            'bulkDelete-sub_category',
            'export-sub_category',
            'view-product',
            'show-product',
            'add-product',
            'edit-product',
            'delete-product',
            'bulkDelete-product',
            'export-product',
            'view-appointment',
            'show-appointment',
            'add-appointment',
            'edit-appointment',
            'delete-appointment',
            'bulkDelete-appointment',
            'import-appointment',
            'export-appointment',
            'appointment-imports',
            'view-sms-template',
            'show-sms-template',
            'add-sms-template',
            'edit-sms-template',
            'delete-sms-template',
            'bulkDelete-sms-template',
            'import-sms-template',
            'export-sms-template',
            'sms-template-imports',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission, $user->role_id);
            });
        }
    }
}
