<?php

namespace App\Http\Middleware;

use App\Models\RevokedAccessToken;
use Closure;
use Illuminate\Http\Request;
use App\Services\JWTService;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Symfony\Component\HttpFoundation\Response;

class JWTMiddleware
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || !$this->jwtService->validateToken($token)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the token is revoked
        if (RevokedAccessToken::where('token', $token)->exists()) {
            return response()->json(['message' => 'Already signed out, try to sign in again.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
