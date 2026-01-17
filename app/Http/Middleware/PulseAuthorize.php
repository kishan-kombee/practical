<?php

namespace App\Http\Middleware;

use App\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PulseAuthorize
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return app()->environment('local') ||
        in_array(Helper::getIp(), explode(',', config('constants.allowed_ip_addresses.pulse')))
            ? $next($request) : abort(403, 'Forbidden: Pulse');
    }
}
