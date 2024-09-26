<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    /**
     * GET /api/admin/categories
     *
     * Display a listing of categories (only non-deleted ones)
     */
    public function index()
    {
        // $categories = Category::all();
        $categories = Category::withTrashed()->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('category.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('category.all_records'),
                'errors' => [],
                'data' => $categories,
            ], Response::HTTP_OK);
        }
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
            return response()->json([
                'success' => false,
                'message' => __('category.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $imageUrl = null;
        // if ($request->hasFile('image')) {
        //     // Define the path where the image will be stored
        //     $destinationPath = public_path('img/categories');

        //     // Check if the directory exists, if not, create it
        //     if (!File::exists($destinationPath)) {
        //         File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
        //     }

        //     $image = $request->file('image');

        //     // Generate a unique filename
        //     $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        //     // Move the image to the destination path
        //     $image->move($destinationPath, $imageName);

        //     // Generate the full URL to the image
        //     $imageUrl = config('app.url') . '/img/categories/' . $imageName;
        // }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('img/categories/', $imageName, 's3');
            $imageUrl = Storage::disk('s3')->url('img/categories/' . $imageName);
        }

        $category = Category::create([
            'name' => $request->name,
            'image' => $imageUrl,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('category.added'),
            'errors' => [],
            'data' => $category
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/categories/{category}
     *
     * Display a specific category
     */
    public function show($id)
    {
        $category = Category::withTrashed()->find($id);

        if ($category) {
            return response()->json([
                'success' => true,
                'message' => __('category.found'),
                'errors' => [],
                'data' => $category
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/categories/{category}
     *
     * Update a specific category
     */
    public function update(Request $request, $id)
    {
        $category = Category::withTrashed()->find($id);

        if ($category) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('category.failed'),
                    'errors' => $validator->errors(),
                    'data' => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            $category->name = $request->name ?? $category->name;

            $imageUrl = null;

            // if ($request->hasFile('image')) {
            //     // Define the path where the image will be stored
            //     $destinationPath = public_path('img/categories');

            //     // Check if the directory exists, if not, create it
            //     if (!File::exists($destinationPath)) {
            //         File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
            //     }

            //     // first unlink the old avatar
            //     if ($category->image) {
            //         $parsedUrl = parse_url($category->image);
            //         $oldImage = basename($parsedUrl['path']);

            //         if (File::exists("{$destinationPath}/{$oldImage}")) {
            //             // unlink(public_path('img/categories') . '/' . $oldImage);
            //             unlink("{$destinationPath}/{$oldImage}");
            //         }
            //     }

            //     // Next Update the avatar
            //     $image = $request->file('image');

            //     // Generate a unique filename
            //     $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            //     // Move the image to the destination path
            //     $image->move($destinationPath, $imageName);

            //     // Generate the full URL to the image
            //     $imageUrl = config('app.url') . '/img/categories/' . $imageName;

            //     $category->image = $imageUrl ?? $category->image;
            // }

            if ($request->hasFile('image')) {
                // first unlink the old avatar
                if ($category->image) {
                    // Extract the S3 path from the avatar URL
                    $oldImagePath = parse_url($category->image, PHP_URL_PATH);
                    $oldImagePath = ltrim($oldImagePath, '/'); // Remove leading slash

                    // if (Storage::disk('s3')->exists($oldImagePath)) {
                    //     Storage::disk('s3')->delete($oldImagePath);
                    // }

                    Storage::disk('s3')->delete($oldImagePath);
                }

                // Next Update the avatar
                $image = $request->file('image');

                // Generate a unique filename
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                $image->storeAs('img/categories/', $imageName, 's3');

                // Generate the full URL to the image
                $imageUrl = Storage::disk('s3')->url('img/categories/' . $imageName);
            }

            $category->image = $imageUrl ?? $category->image;

            $category->update();

            return response()->json([
                'success' => true,
                'message' => __('category.updated'),
                'errors' => [],
                'data' => $category,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/categories/{category}
     *
     * Soft delete a specific store
     */
    public function destroy($id)
    {
        $category = Category::withoutTrashed()->find($id);

        if ($category) {
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => __('category.disabled'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK); // or 204
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/categories/trashed
     *
     * Display all soft-deleted category
     */
    public function trashed()
    {
        $categories = Category::onlyTrashed()->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('category.disabled_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('category.disabled_records'),
                'errors' => [],
                'data' => $categories
            ], Response::HTTP_OK);
        }
    }

    /**
     * POST /api/admin/categories/{id}/restore
     *
     * Restore a soft-deleted category
     */
    public function restore($id)
    {
        $category = Category::onlyTrashed()->find($id);

        if ($category) {
            $category->restore();

            return response()->json([
                'success' => true,
                'message' => __('category.restored'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/categories/{id}/force-delete
     *
     * Permanently delete a category
     */
    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->find($id);

        if ($category) {
            // if ($category->image) {
            //     $parsedUrl = parse_url($category->image);
            //     $oldImage = basename($parsedUrl['path']);
            //     unlink(public_path('img/categories') . '/' . $oldImage);
            // }

            if ($category->image) {
                // Extract the S3 path from the avatar URL
                $oldImagePath = parse_url($category->image, PHP_URL_PATH);
                $oldImagePath = ltrim($oldImagePath, '/'); // Remove leading slash

                // if (Storage::disk('s3')->exists($oldImagePath)) {
                //     Storage::disk('s3')->delete($oldImagePath);
                // }

                Storage::disk('s3')->delete($oldImagePath);
            }

            $category->forceDelete();

            return response()->json([
                'success' => true,
                'message' => __('category.deleted'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/categories
     *
     * Display a listing of categories (only non-deleted ones)
     */
    public function getAvailableCategories()
    {
        $categories = Category::withoutTrashed()->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('category.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('category.all_records'),
                'errors' => [],
                'data' => $categories,
            ], Response::HTTP_OK);
        }
    }

    /**
     * GET /api/admin/categories/{category}
     *
     * Display a specific category
     */
    public function getSingleCategory($id)
    {
        $category = Category::withoutTrashed()->find($id);

        if ($category) {
            return response()->json([
                'success' => true,
                'message' => __('category.found'),
                'errors' => [],
                'data' => $category
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('category.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
