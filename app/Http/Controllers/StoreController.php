<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    /**
     * GET /api/admin/stores
     *
     * Display a listing of stores (only non-deleted ones)
     */
    public function index()
    {
        // $stores = Store::all();
        $stores = Store::withCount(['favoriteByUsers as favorites_count'])
                        ->with('offers')
                        ->orderBy('favorites_count', 'desc')
                        ->get();

        // Handle null values (if any) in the collection
        $stores->map(function ($store) {
            $store->favorites_count = $store->favorites_count ?? 0;
            return $store;
        });

        return response()->json($stores, Response::HTTP_OK);
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
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
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

        return response()->json($category, Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/stores/{store}
     *
     * Display a specific store
     */
    public function show($id)
    {
        $store = Store::withCount(['favoriteByUsers as favorites_count'])
                        ->with('offers')
                        ->find($id);

        if ($store) {
            return response()->json($store, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/stores/{store}
     *
     * Update a specific store
     */
    public function update(Request $request, $id)
    {
        $store = Store::find($id);

        if ($store) {
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
                return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
            }

            $store->name = $request->name ?? $store->name;
            $store->category_id = $request->category_id ?? $store->category_id;
            $store->about = $request->about ?? $store->about;
            $store->address = $request->address ?? $store->address;
            $store->latitude = $request->latitude ?? $store->latitude;
            $store->longitude = $request->longitude ?? $store->longitude;

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

                $store->image = $imageUrl ?? $store->image;
            }

            $store->update();

            return response()->json($store, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/stores/{store}
     *
     * Soft delete a specific store
     */
    public function destroy($id)
    {
        $store = Store::withoutTrashed()->find($id);

        if ($store) {
            $store->delete();

            return response()->json(['message' => 'Store disabled successfully'], Response::HTTP_OK); // or 204
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/stores/trashed
     *
     * Display all soft-deleted store
     */
    public function trashed()
    {
        $stores = Store::onlyTrashed()->get();
        return response()->json($stores, Response::HTTP_OK);
    }

    /**
     * POST /api/admin/stores/{id}/restore
     *
     * Restore a soft-deleted store
     */
    public function restore($id)
    {
        $store = Store::onlyTrashed()->find($id);

        if ($store) {
            $store->restore();
            return response()->json(['message' => 'Store restored successfully'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/stores/{id}/force-delete
     *
     * Permanently delete a store
     */
    public function forceDelete($id)
    {
        $store = Store::onlyTrashed()->find($id);

        if ($store) {
            if ($store->image) {
                $parsedUrl = parse_url($store->image);
                $oldImage = basename($parsedUrl['path']);
                unlink(public_path('img/stores') . '/' . $oldImage);
            }

            $store->forceDelete();

            return response()->json(['message' => 'Store deleted permanently.'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/v1/nearby-stores?latitude=40.7128&longitude=-74.0060&radius=10
     */
    public function getNearbyStores(Request $request)
    {
        // Validate the request input (latitude and longitude)
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'numeric|nullable', // Optional radius in kilometers
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
        }

        $latitude = $request['latitude'];
        $longitude = $request['longitude'];
        $radius = $request['radius'] ?? 10; // Default to 10km if not provided

        // Haversine formula to calculate distance
        $stores = Store::select(
            'stores.id',
            'stores.name',
            'categories.name as category',
            'stores.image',
            'stores.address',
            'stores.about',
            'stores.latitude',
            'stores.longitude',
            DB::raw(
                "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude)))) AS distance"
            )
        )
        ->join('categories', 'stores.category_id', '=', 'categories.id')
        ->withCount(['favoriteByUsers as favorites_count'])
        ->with('offers')
        ->having('distance', '<=', $radius) // Filter by radius
        ->orderBy('distance', 'asc') // Sort by closest
        ->get();

        // Return the stores as a JSON response
        return response()->json($stores, Response::HTTP_OK);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $store = Store::find($id);

        if ($store) {
            $user = $request->user();
            // $user = auth()->user();
            // dd($user);

            if ($user->favoriteStores()->where('store_id', $store->id)->exists()) {
                // Unfavorite if already favorite
                $user->favoriteStores()->detach($store->id);
                return response()->json(['message' => 'Store unfavorite.', 'favorites_count' => $store->favoritesCount()], Response::HTTP_OK);
            } else {
                // Favorite the store
                $user->favoriteStores()->attach($store->id);
                return response()->json(['message' => 'Store favorite.', 'favorites_count' => $store->favoritesCount()], Response::HTTP_CREATED);
            }
        } else {
            return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
        }
    }

    // public function getFavoritesCount($id)
    // {
    //     $store = Store::find($id);

    //     if ($store) {
    //         $count = $store->favoritesCount();
    //         return response()->json([
    //             'store_id' => $store->id,
    //             'favorites_count' => $count
    //         ]);
    //     } else {
    //         return response()->json(['message' => 'Store not found'], Response::HTTP_NOT_FOUND);
    //     }
    // }
}
