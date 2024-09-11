<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    /**
     * GET /api/admin/stores
     *
     * Display a listing of stores (only non-deleted ones)
     */
    public function index()
    {
        return Store::all();
    }

    /**
     * POST /api/admin/stores
     *
     * Store a newly created store
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'about' => 'required',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
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
            $destinationPath = public_path('img/stores');

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/stores/' . $imageName;
        }

        $category = Store::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'image' => $imageUrl,
            'about' => $request->about,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);
        return response()->json($category, 201);
    }

    /**
     * GET /api/admin/stores/{store}
     *
     * Display a specific store
     */
    public function show(Store $store)
    {
        return $store;
    }

    /**
     * PUT /api/admin/stores/{store}
     *
     * Update a specific store
     */
    public function update(Request $request, Store $store)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'about' => 'required',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $store->name = $request->name;
        $store->category_id = $request->store_id;
        $store->about = $request->about;
        $store->address = $request->address;
        $store->latitude = $request->latitude;
        $store->longitude = $request->longitude;

        if ($request->hasFile('image')) {
            // first unlink the old image
            $parsedUrl = parse_url($store->image);
            $oldImage = basename($parsedUrl['path']);
            unlink(public_path('img/stores') . '/' . $oldImage);

            // Next Update the avatar
            $image = $request->file('image');

            // Generate a unique filename
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Define the path where the image will be stored
            $destinationPath = public_path('img/stores');

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/stores/' . $imageName;

            $store->image = $imageUrl;
        }

        $store->update();

        return response()->json($store, 200);
    }

    /**
     * DELETE /api/admin/stores/{store}
     *
     * Soft delete a specific store
     */
    public function destroy(Store $store)
    {
        $store->delete();
        return response()->json(["message" => 'Store disabled successfully.'], 204);
    }

    /**
     * GET /api/admin/stores/trashed
     *
     * Display all soft-deleted store
     */
    public function trashed()
    {
        $stores = Store::onlyTrashed()->get();
        return response()->json($stores);
    }

    /**
     * POST /api/admin/stores/{id}/restore
     *
     * Restore a soft-deleted store
     */
    public function restore($id)
    {
        $store = Store::withTrashed()->findOrFail($id);
        $store->restore();
        return response()->json(['message' => 'Store restored successfully']);
    }

    /**
     * DELETE /api/admin/stores/{id}/force-delete
     *
     * Permanently delete a store
     */
    public function forceDelete($id)
    {
        $store = Store::onlyTrashed()->findOrFail($id);
        $store->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/nearby-stores?latitude=40.7128&longitude=-74.0060&radius=10
     */
    public function getNearbyStores(Request $request)
    {
        // Validate the request input (latitude and longitude)
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'numeric|nullable', // Optional radius in kilometers
        ]);

        $latitude = $validated['latitude'];
        $longitude = $validated['longitude'];
        $radius = $validated['radius'] ?? 10; // Default to 10km if not provided

        // Haversine formula to calculate distance
        $stores = Store::select(
            'name',
            'address',
            'latitude',
            'longitude',
            DB::raw(
                "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude)))) AS distance"
            )
        )
        ->having('distance', '<=', $radius) // Filter by radius
        ->orderBy('distance', 'asc') // Sort by closest
        ->get();

        // Return the stores as a JSON response
        return response()->json($stores);
    }
}
