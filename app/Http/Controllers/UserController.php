<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * GET /api/admin/users
     *
     * Display a listing of users (only non-deleted ones)
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * POST /api/admin/users
     *
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|unique:users|numeric',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            // Define the path where the image will be stored
            $destinationPath = public_path('img/avatars');

            // Check if the directory exists, if not, create it
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
            }

            $avatar = $request->file('avatar');

            // Generate a unique filename
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

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
            'role' => $request->role,
            'avatar' => $avatarUrl
        ]);

        return response()->json($user, Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/users/{user}
     *
     * Display a specific user
     */
    public function show($id)
    {
        $user = User::find($id);

        if ($user) {
            return response()->json($user, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/users/{user}
     *
     * Update a specific user
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if ($user) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'required|numeric|unique:users,phone,' . $user->id,
                'password' => 'required|string|min:8',
                'role' => 'required|string',
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
            }

            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;
            $user->phone = $request->name ?? $user->phone;
            $user->password = Hash::make($request->password) ?? $user->password;
            $user->role = $request->role ?? $user->role;

            if ($request->hasFile('avatar')) {
                // Define the path where the image will be stored
                $destinationPath = public_path('img/avatars');

                // Check if the directory exists, if not, create it
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
                }

                // first unlink the old avatar if exists
                if ($user->avatar) {
                    $parsedUrl = parse_url($user->avatar);
                    $oldAvatar = basename($parsedUrl['path']);

                    if (File::exists("{$destinationPath}/{$oldAvatar}")) {
                        // unlink($destinationPath . '/' . $oldAvatar);
                        unlink("{$destinationPath}/{$oldAvatar}");
                    }
                }

                // Next Update the avatar
                $avatar = $request->file('avatar');

                // Generate a unique filename
                $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

                // Move the image to the destination path
                $avatar->move($destinationPath, $avatarName);

                // Generate the full URL to the image
                $avatarUrl = config('app.url') . '/img/avatars/' . $avatarName;

                $user->avatar = $avatarUrl ?? $user->avatar;
            }

            $user->update();

            return response()->json($user, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/users/{user}
     *
     * Soft delete a specific user
     */
    public function destroy($id)
    {
        $user = User::withoutTrashed()->find($id);

        if ($user) {
            $user->delete();

            return response()->json(['message' => 'User suspended successfully'], Response::HTTP_OK); // or 204
        } else {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/users/trashed
     *
     * Display all soft-deleted user
     */
    public function trashed()
    {
        $users = User::onlyTrashed()->get();
        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * POST /api/admin/users/{id}/restore
     *
     * Restore a soft-deleted user
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->find($id);

        if ($user) {
            $user->restore();
            return response()->json(['message' => 'User restored successfully'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/users/{id}/force-delete
     *
     * Permanently delete a user
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->find($id);

        if ($user) {
            if ($user->avatar) {
                $parsedUrl = parse_url($user->avatar);
                $oldAvatar = basename($parsedUrl['path']);
                unlink(public_path('img/avatars') . '/' . $oldAvatar);
            }

            $user->forceDelete();

            return response()->json(['message' => 'User Permanently deleted successfully.'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
    }
}
