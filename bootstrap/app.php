<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo(AppServiceProvider::HOME);
        $middleware->redirectGuestsTo('/');
        $middleware->append([
            App\Http\Middleware\HttpResponseHeaders::class,
            Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->web([
            App\Http\Middleware\HostVerificationMiddleware::class,
            App\Http\Middleware\VerifyCsrfToken::class,
            Illuminate\Session\Middleware\AuthenticateSession::class,
            App\Http\Middleware\SetLocale::class,
        ]);

        // Register API middleware aliases
        $middleware->alias([
            'check.permission' => App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
