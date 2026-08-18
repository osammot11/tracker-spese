<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('pin_verified')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso non autorizzato. È richiesto il PIN.',
                ], 401);
            }

            if (!$request->is('pin*')) {
                session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('pin.show');
        }

        return $next($request);
    }
}
