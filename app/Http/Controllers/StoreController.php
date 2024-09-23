<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
        $stores = Store::withTrashed()->get();

        if (!$stores) {
            return response()->json([
                'success' => false,
                'message' => __('store.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('store.all_records'),
                'errors' => [],
                'data' => $stores
            ], Response::HTTP_OK);
        }
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
            'address' => 'required|string|max:255',
            'about' => 'required',
            'phone' => 'required|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('store.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            // Define the path where the image will be stored
            $destinationPath = public_path('img/stores');

            // Check if the directory exists, if not, create it
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
            }

            $image = $request->file('image');

            // Generate a unique filename
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/stores/' . $imageName;
        }

        $store = Store::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'image' => $imageUrl,
            'address' => $request->address,
            'about' => $request->about,
            'phone' => $request->phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('store.added'),
            'errors' => [],
            'data' => $store
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/stores/{store}
     *
     * Display a specific store
     */
    public function show($id)
    {
        $store = Store::withTrashed()->find($id);

        if ($store) {
            return response()->json([
                'success' => true,
                'message' => __('store.found'),
                'errors' => [],
                'data' => $store,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/stores/{store}
     *
     * Update a specific store
     */
    public function update(Request $request, $id)
    {
        $store = Store::withTrashed()->find($id);

        if ($store) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category_id' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'address' => 'required|string|max:255',
                'about' => 'required',
                'phone' => 'required|numeric',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('store.failed'),
                    'errors' => $validator->errors(),
                    'data' => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            $store->name = $request->name ?? $store->name;
            $store->category_id = $request->category_id ?? $store->category_id;
            $store->address = $request->address ?? $store->address;
            $store->about = $request->about ?? $store->about;
            $store->phone = $request->phone ?? $store->phone;
            $store->latitude = $request->latitude ?? $store->latitude;
            $store->longitude = $request->longitude ?? $store->longitude;

            if ($request->hasFile('image')) {
                // Define the path where the image will be stored
                $destinationPath = public_path('img/stores');

                // Check if the directory exists, if not, create it
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
                }

                // first unlink the old avatar
                if ($store->image) {
                    $parsedUrl = parse_url($store->image);
                    $oldImage = basename($parsedUrl['path']);

                    if (File::exists("{$destinationPath}/{$oldImage}")) {
                        // unlink(public_path('img/categories') . '/' . $oldImage);
                        unlink("{$destinationPath}/{$oldImage}");
                    }
                }

                // Next Update the avatar
                $image = $request->file('image');

                // Generate a unique filename
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                // Move the image to the destination path
                $image->move($destinationPath, $imageName);

                // Generate the full URL to the image
                $imageUrl = config('app.url') . '/img/stores/' . $imageName;

                $store->image = $imageUrl ?? $store->image;
            }

            $store->update();

            return response()->json([
                'success' => true,
                'message' => __('store.updated'),
                'errors' => [],
                'data' => $store,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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

            return response()->json([
                'success' => true,
                'message' => __('store.disabled'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK); // or 204
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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

        if ($stores->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('store.disabled_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('store.disabled_records'),
                'errors' => [],
                'data' => $stores
            ], Response::HTTP_OK);
        }
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
            return response()->json([
                'success' => true,
                'message' => __('store.restored'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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

            return response()->json([
                'success' => true,
                'message' => __('store.deleted'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/stores
     *
     * Display a listing of stores (only non-deleted ones)
     */
    public function getAvailableStores()
    {
        // $stores = Store::all();
        $stores = Store::withoutTrashed()
                        ->withCount(['favoriteByUsers as favorites_count'])
                        ->with('offers')
                        ->orderBy('favorites_count', 'desc')
                        ->get();

        if (!$stores) {
            return response()->json([
                'success' => false,
                'message' => __('store.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('store.all_records'),
                'errors' => [],
                'data' => $stores
            ], Response::HTTP_OK);
        }
    }

    /**
     * GET /api/admin/stores/{store}
     *
     * Display a specific store
     */
    public function getSingleStore($id)
    {
        $store = Store::withoutTrashed()
                        ->withCount(['favoriteByUsers as favorites_count'])
                        ->with('offers')
                        ->find($id);

        if ($store) {
            return response()->json([
                'success' => true,
                'message' => __('store.found'),
                'errors' => [],
                'data' => $store,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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
            return response()->json([
                'success' => false,
                'message' => __('store.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
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
            'stores.phone',
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
        if (!$stores) {
            return response()->json([
                'success' => false,
                'message' => __('store.nearby_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('store.nearby_records'),
                'errors' => [],
                'data' => $stores,
            ], Response::HTTP_OK);
        }
    }

    public function toggleFavorite(Request $request, $id)
    {
        $store = Store::withTrashed()->find($id);

        if ($store) {
            $user = $request->user();

            if ($user->favoriteStores()->where('store_id', $store->id)->exists()) {
                // Unfavorite if already favorite
                $user->favoriteStores()->detach($store->id);
                return response()->json([
                    'success' => true,
                    'message' => __('store.unfavourited'),
                    'errors' => [],
                    'data' => [
                        'favorites_count' => $store->favoritesCount(),
                    ],
                ], Response::HTTP_OK);
            } else {
                // Favorite the store
                $user->favoriteStores()->attach($store->id);
                return response()->json([
                    'success' => true,
                    'message' => __('store.favourited'),
                    'errors' => [],
                    'data' => [
                        'favorites_count' => $store->favoritesCount(),
                    ],
                ], Response::HTTP_CREATED);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => __('store.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
