<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowCompanyAuthFrame
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $origin = (string) ($request->attributes->get('company_auth_origin')
            ?: $request->session()->get('company_auth_origin'));

        if (in_array($origin, config('services.company_auth.allowed_origins', []), true)) {
            $response->headers->remove('X-Frame-Options');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' {$origin}");
        }

        return $response;
    }
}
