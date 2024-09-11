<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
        return User::all();
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
            return response()->json($validator->errors(), 400);
        }

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
    public function show(User $user)
    {
        return $user;
    }

    /**
     * PUT /api/admin/users/{user}
     *
     * Update a specific user
     */
    public function update(Request $request, User $user)
    {
        // $user = User::findOrFail($id);
        // dd($request->name);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|numeric|unique:users,phone,' . $user->id,
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->name;

        if ($request->password != null) {
            $user->password = Hash::make($request->password);
        }

        $user->role = $request->role;

        if ($request->hasFile('avatar')) {
            // first unlink the old avatar
            $parsedUrl = parse_url($user->avatar);
            $oldAvatar = basename($parsedUrl['path']);
            unlink(public_path('img/avatars') . '/' . $oldAvatar);

            // Next Update the avatar
            $avatar = $request->file('avatar');

            // Generate a unique filename
            $avatarName = time() . '_' . uniqid() . '.' . $avatar->getClientOriginalExtension();

            // Define the path where the image will be stored
            $destinationPath = public_path('img/avatars');

            // Move the image to the destination path
            $avatar->move($destinationPath, $avatarName);

            // Generate the full URL to the image
            $avatarUrl = config('app.url') . '/img/avatars/' . $avatarName;

            $user->avatar = $avatarUrl;
        }

        $user->update();

        return response()->json($user, Response::HTTP_OK);
    }


    /**
     * DELETE /api/admin/users/{user}
     *
     * Soft delete a specific user
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(["message" => 'User suspended successfully.'], 204);
    }

    /**
     * GET /api/admin/users/trashed
     *
     * Display all soft-deleted user
     */
    public function trashed()
    {
        $users = User::onlyTrashed()->get();
        return response()->json($users);
    }

    /**
     * POST /api/admin/users/{id}/restore
     *
     * Restore a soft-deleted user
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return response()->json(['message' => 'User restored successfully']);
    }

    /**
     * DELETE /api/admin/users/{id}/force-delete
     *
     * Permanently delete a user
     */
    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->forceDelete();
        return response()->json(null, 204);
    }
}
