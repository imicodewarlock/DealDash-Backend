<?php

namespace App\Http\Controllers;

use App\Models\RevokedAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\JWTService;

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
            //'role' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // $avatarPath = null;
        // if ($request->hasFile('avatar')) {
        //     $avatar = $request->file('avatar');
        //     $fileName = time() . '_' . $avatar->getClientOriginalName();
        //     $avatarPath = $avatar->storeAs('/images/avatars', $fileName, 'public');
        // }
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');

            // Generate a unique filename
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

            // Define the path where the image will be stored
            $destinationPath = public_path('img/avatars');

            // Move the image to the destination path
            $avatar->move($destinationPath, $avatarName);

            // Generate the full URL to the image
            $avatarUrl = config('app.url') . '/img/avatars/' . $avatarName;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            //'role' => $request->role,
            'avatar' => $avatarUrl
        ]);

        $token = $this->jwtService->createToken($user);

        // Update the user's remember_token field in the database
        // $user->remember_token = $token->toString();
        // $user->save();

        return response()->json([
            'user' => $user,
            'token' => $token->toString(),
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $this->jwtService->createToken($user);

        // Update the user's remember_token field in the database
        // $user->remember_token = $token->toString();
        // $user->save();

        return response()->json([
            'user' => $user,
            'token' => $token->toString(),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();  // Get the JWT token from the request

        // Add the token to the revoked tokens list
        RevokedAccessToken::create([
            'token' => $token,
        ]);
        return response()->json(['message' => 'Successfully logged out']);
    }
}
