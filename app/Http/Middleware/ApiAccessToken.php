<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAccessToken
{
    /**
     * Simple token gate for internal API access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedToken = $request->bearerToken() ?? $request->header('X-API-TOKEN');
        $expectedToken = (string) config('services.api_access_token');

        if ($expectedToken === '') {
            return response()->json(['message' => 'API token não configurado.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (! is_string($providedToken) || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json(['message' => 'Não autorizado.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
