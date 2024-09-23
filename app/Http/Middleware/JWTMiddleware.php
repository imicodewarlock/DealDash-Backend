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
                return response()->json([
                    'success' => false,
                    'message' => __('auth.token_not_found'),
                    'errors' => [],
                    'data' => [],
                ], Response::HTTP_UNAUTHORIZED);
            }

            $userId = $this->jwtService->getUUIDFromValidatedToken($token);

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.invalid_token'),
                    'errors' => [],
                    'data' => [],
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.user_not_found'),
                    'errors' => [],
                    'data' => [],
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Authenticate the user in the current request
            Auth::setUser($user);

            // Check if the token is revoked
            if (RevokedAccessToken::where('token', $token)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.already_signed_out'),
                    'errors' => [],
                    'data' => [],
                ], Response::HTTP_UNAUTHORIZED);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('auth.invalid_token'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
