<?php

namespace App\Http\Middleware;

use App\Models\RevokedAccessToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use App\Services\JWTService;
use Illuminate\Support\Facades\Auth;
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

        try {
            if (!$token || !$this->jwtService->validateToken($token)) {
                return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }

            $userId = $this->jwtService->getUUIDFromValidatedToken($token);

            if (!$userId) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            // Authenticate the user in the current request
            Auth::setUser($user);

            // Check if the token is revoked
            if (RevokedAccessToken::where('token', $token)->exists()) {
                return response()->json(['message' => 'Already signed out, try to sign in again.'], Response::HTTP_UNAUTHORIZED);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid Token'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
