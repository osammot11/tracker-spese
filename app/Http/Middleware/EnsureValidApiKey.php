<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = trim((string) config('app.api_key', env('CHATGPT_API_KEY')));

        // Check Bearer Token, X-API-KEY header, or query param
        $bearerToken = $request->bearerToken();
        $customHeader = $request->header('X-API-KEY');
        $queryKey = $request->query('api_key');

        $providedKey = $bearerToken ?: ($customHeader ?: $queryKey);

        if (empty($expectedKey) || empty($providedKey) || !hash_equals($expectedKey, (string) $providedKey)) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorizzato: API Key mancante o non valida.',
            ], 401);
        }

        return $next($request);
    }
}
