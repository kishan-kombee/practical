<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTimeoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get timeout value from constants
        $timeout = config('constants.api_timeout', 60);

        // Set the execution time limit for this request
        if ($timeout > 0) {
            set_time_limit($timeout);
        }

        return $next($request);
    }
}
