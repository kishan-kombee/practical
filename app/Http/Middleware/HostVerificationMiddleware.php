<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HostVerificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $serverName = $_SERVER['SERVER_NAME'];
        $httpHost = $_SERVER['HTTP_HOST'];

        // Use Laravel's url() helper to get the full URL
        $fullUrl = url('');
        $parsedUrl = parse_url($fullUrl);
        $urlHost = $parsedUrl['host'] ?? '';

        // Compare server name and URL host
        if ($serverName !== $urlHost) {
            abort(403, 'Forbidden: Host header mismatch');
        }

        return $next($request);
    }
}
