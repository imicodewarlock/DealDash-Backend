<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * GET /api/admin/categories
     *
     * Display a listing of categories (only non-deleted ones)
     */
    public function index()
    {
        // $categories = Category::withoutTrashed()->get();
        // return response()->json($categories);
        return Category::all(); // Returns all stores except soft-deleted ones
    }

    /**
     * POST /api/admin/categories
     *
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Generate a unique filename
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Define the path where the image will be stored
            $destinationPath = public_path('img/categories');

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/categories/' . $imageName;
        }

        $category = Category::create([
            'name' => $request->name,
            'image' => $imageUrl,
        ]);

        return response()->json($category, 201);
    }

    /**
     * GET /api/admin/categories/{category}
     *
     * Display a specific category
     */
    public function show(Category $category)
    {
        return $category;
    }


    /**
     * PUT /api/admin/categories/{category}
     *
     * Update a specific category
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $category->name = $request->name;

        if ($request->hasFile('image')) {
            // first unlink the old avatar
            $parsedUrl = parse_url($category->image);
            $oldPicture = basename($parsedUrl['path']);
            unlink(public_path('img/categories') . '/' . $oldPicture);

            // Next Update the avatar
            $image = $request->file('image');

            // Generate a unique filename
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Define the path where the image will be stored
            $destinationPath = public_path('img/categories');

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/categories/' . $imageName;

            $category->image = $imageUrl;
        }

        $category->update();
        return response()->json($category, 200);
    }

    /**
     * DELETE /api/admin/categories/{category}
     *
     * Soft delete a specific store
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(["message" => 'Category disabled successfully.'], 204);
    }

    /**
     * GET /api/admin/categories/trashed
     *
     * Display all soft-deleted category
     */
    public function trashed()
    {
        $categories = Category::onlyTrashed()->get();
        return response()->json($categories);
    }

    /**
     * POST /api/admin/categories/{id}/restore
     *
     * Restore a soft-deleted category
     */
    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();
        return response()->json(['message' => 'Category restored successfully']);
    }

    /**
     * DELETE /api/admin/categories/{id}/force-delete
     *
     * Permanently delete a category
     */
    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->forceDelete();
        return response()->json(null, 204);
    }
}
