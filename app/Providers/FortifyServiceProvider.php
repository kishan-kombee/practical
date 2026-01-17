<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        /** @var view-string $confirmPasswordView */
        $confirmPasswordView = 'livewire.auth.confirm-password';

        Fortify::confirmPasswordView(fn () => view($confirmPasswordView));

        // RateLimiter::for('two-factor', function (Request $request) {
        //     return Limit::perMinute(5)->by($request->session()->get('login.id'));
        // });
    }
}
