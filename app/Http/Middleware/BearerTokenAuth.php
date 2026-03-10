<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BearerTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $validToken = config('auth.bearer_token', env('BEARER_TOKEN', 'mytoken123'));

        if ($request->bearerToken() !== $validToken) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}