<?php

namespace App\Http\Controllers;

use App\Models\RevokedAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\JWTService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    protected $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|unique:users|numeric',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $avatarUrl = null;
        // if ($request->hasFile('avatar')) {
        //     // Define the path where the image will be stored
        //     $destinationPath = public_path('img/avatars');

        //     // Check if the directory exists, if not, create it
        //     if (!File::exists($destinationPath)) {
        //         File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
        //     }

        //     $avatar = $request->file('avatar');

        //     // Generate a unique filename
        //     $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

        //     // Move the image to the destination path
        //     $avatar->move($destinationPath, $avatarName);

        //     // Generate the full URL to the image
        //     $avatarUrl = config('app.url') . '/img/avatars/' . $avatarName;
        // }

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();
            $avatar->storeAs('img/avatars/', $avatarName, 's3');
            $avatarUrl = Storage::disk('s3')->url('img/avatars/' . $avatarName);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'avatar' => $avatarUrl
        ]);

        $token = $this->jwtService->createToken($user);

        return response()->json([
            'success' => true,
            'message' => __('auth.signed_up'),
            'errors' => [],
            'data' => [
                'user' => $user,
                'token' => $token->toString(),
            ],
        ], Response::HTTP_OK);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => null,
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.password'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtService->createToken($user);

        return response()->json([
            'success' => true,
            'message' => __('auth.signed_in'),
            'errors' => [],
            'data' => [
                'user' => $user,
                'token' => $token->toString(),
            ],
        ], Response::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();  // Get the JWT token from the request

        // Add the token to the revoked tokens list
        RevokedAccessToken::create([
            'token' => $token,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('auth.signed_out'),
            'errors' => [],
            'data' => [],
        ], Response::HTTP_OK);
    }
}
