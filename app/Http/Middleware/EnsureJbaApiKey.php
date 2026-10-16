<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJbaApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = trim((string) ($request->header('X-JBA-API-Key') ?: $request->bearerToken()));

        foreach (config('services.jba_api.clients', []) as $client) {
            $configuredKey = trim((string) ($client['key'] ?? ''));

            if ($providedKey !== '' && $configuredKey !== '' && hash_equals($configuredKey, $providedKey)) {
                $request->attributes->set('jba_api_client', (string) ($client['name'] ?? 'API client'));

                return $next($request);
            }
        }

        return response()->json([
            'message' => 'API key tidak valid.',
        ], 401);
    }
}
