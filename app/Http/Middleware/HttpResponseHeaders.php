<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class HttpResponseHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Generate or Retrieve CSP Nonce
        // Default to a new random nonce
        $nonce = base64_encode(random_bytes(16));

        // In Production/UAT, we MUST use a persistent nonce per session.
        // If the nonce changes on every request, Livewire's 'wire:navigate' will see script tags
        // as "new" (different nonce attribute) and re-execute them, causing Flux UI to crash
        // with "CustomElementRegistry: name already used".
        if (! App::environment('local') && $request->hasSession()) {
            try {
                $sessionNonce = $request->session()->get('csp_nonce_val');
                if ($sessionNonce) {
                    $nonce = $sessionNonce;
                } else {
                    $request->session()->put('csp_nonce_val', $nonce);
                }
            } catch (\Exception $e) {
                // Fallback to random nonce if session fails
            }
        }

        // 2. Prepare View Variables
        // In local dev, we disable the nonce to prevent Hot Module Replacement (HMR) issues.
        $shouldUseNonce = ! App::environment('local');
        $viewNonce = $shouldUseNonce ? $nonce : '';

        // Share with all views (e.g. for @livewireScripts(['nonce' => $nonce]))
        \Illuminate\Support\Facades\View::share('nonce', $viewNonce);

        // Store in request attributes for Livewire access if needed
        if ($shouldUseNonce) {
            $request->attributes->set('csp_nonce', $nonce);
        }

        $response = $next($request);

        // 3. Set Security Headers (only in Non-Local environments)
        if ($shouldUseNonce) {
            // Remove Information Disclosure Headers
            $response->headers->remove('X-Powered-By');

            // Basic Security Headers
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN', true);
            $response->headers->set('X-Content-Type-Options', 'nosniff', true);

            // HSTS (Strict Transport Security)
            $appUrlScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
            if (App::isProduction() || $request->isSecure() || $appUrlScheme === 'https') {
                $response->headers->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains',
                    true
                );
            }

            // Content Security Policy (CSP)
            $nonceDirective = "'nonce-{$nonce}'";
            $csp = "default-src 'self'; " .
                "script-src 'self' {$nonceDirective} 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://unpkg.com https://www.google.com https://www.gstatic.com; " .
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://unpkg.com; " .
                "font-src 'self' data: https://fonts.bunny.net; " .
                "img-src 'self' data: https://fluxui.dev; " .
                "connect-src 'self' https://www.google.com https://www.gstatic.com; " .
                "base-uri 'self'; " .
                "frame-ancestors 'none'; " .
                "frame-src 'self' https://www.google.com; " .
                "form-action 'self';";

            $response->headers->set('Content-Security-Policy', $csp, true);

            $response->headers->set(
                'Permissions-Policy',
                'geolocation=(self)',
                true
            );
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', true);
        }

        return $response;
    }
}
