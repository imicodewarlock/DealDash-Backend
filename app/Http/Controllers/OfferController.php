<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class OfferController extends Controller
{
    /**
     * GET /api/admin/offers
     *
     * Display a listing of offers (only non-deleted ones)
     */
    public function index()
    {
        $offers = Offer::all();
        return response()->json($offers, Response::HTTP_OK);
    }

    /**
     * POST /api/admin/offers
     *
     * Store a newly created offer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'store_id' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'about' => 'required',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
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
            $destinationPath = public_path('img/offers');

            // Move the image to the destination path
            $image->move($destinationPath, $imageName);

            // Generate the full URL to the image
            $imageUrl = config('app.url') . '/img/offers/' . $imageName;
        }

        $offer = Offer::create([
            'name' => $request->name,
            'store_id' => $request->store_id,
            'image' => $imageUrl,
            'about' => $request->about,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json($offer, Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/offers/{offer}
     *
     * Display a specific offer
     */
    public function show($id)
    {
        $offer = Offer::find($id);

        if ($offer) {
            return response()->json($offer, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Offer not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/offers/{offer}
     *
     * Update a specific offer
     */
    public function update(Request $request, $id)
    {
        $offer = Offer::find($id);

        if ($offer) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'store_id' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'about' => 'required',
                'address' => 'required|string|max:255',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'start_date' => 'required',
                'end_date' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
            }

            $offer->name = $request->name ?? $offer->name;
            $offer->store_id = $request->store_id ?? $offer->store_id;
            $offer->about = $request->about ?? $offer->about;
            $offer->address = $request->address ?? $offer->address;
            $offer->latitude = $request->latitude ?? $offer->latitude;
            $offer->longitude = $request->longitude ?? $offer->longitude;
            $offer->start_date = $request->start_date ?? $offer->start_date;
            $offer->end_date = $request->end_date ?? $offer->end_date;

            if ($request->hasFile('image')) {
                // first unlink the old image
                $parsedUrl = parse_url($offer->image);
                $oldImage = basename($parsedUrl['path']);
                unlink(public_path('img/offers') . '/' . $oldImage);

                // Next Update the avatar
                $image = $request->file('image');

                // Generate a unique filename
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                // Define the path where the image will be stored
                $destinationPath = public_path('img/offers');

                // Move the image to the destination path
                $image->move($destinationPath, $imageName);

                // Generate the full URL to the image
                $imageUrl = config('app.url') . '/img/offers/' . $imageName;

                $offer->image = $imageUrl ?? $offer->image;
            }

            $offer->update();
            return response()->json($offer, Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Offer not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/offers/{offer}
     *
     * Soft delete a specific offer
     */
    public function destroy($id)
    {
        $offer = Offer::withoutTrashed()->find($id);

        if ($offer) {
            $offer->delete();

            return response()->json(['message' => 'Offer disabled successfully'], Response::HTTP_OK); // or 204
        } else {
            return response()->json(['message' => 'Offer not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/offers/trashed
     *
     * Display all soft-deleted offer
     */
    public function trashed()
    {
        $offers = Offer::onlyTrashed()->get();
        return response()->json($offers);
    }

    /**
     * POST /api/admin/offers/{id}/restore
     *
     * Restore a soft-deleted offer
     */
    public function restore($id)
    {
        $offer = Offer::onlyTrashed()->find($id);

        if ($offer) {
            $offer->restore();
            return response()->json(['message' => 'Offer restored successfully'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Offer not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * DELETE /api/admin/offers/{id}/force-delete
     *
     * Permanently delete a offer
     */
    public function forceDelete($id)
    {
        $offer = Offer::onlyTrashed()->find($id);

        if ($offer) {
            if ($offer->image) {
                $parsedUrl = parse_url($offer->image);
                $oldImage = basename($parsedUrl['path']);
                unlink(public_path('img/offers') . '/' . $oldImage);
            }

            $offer->forceDelete();

            return response()->json(['message' => 'Offer deleted permanently.'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Offer not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/v1/nearby-offers?latitude=40.7128&longitude=-74.0060&radius=10
     */
    public function getNearbyOffers(Request $request)
    {
        // Validate the request input (latitude and longitude)
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'numeric|nullable', // Optional radius in kilometers
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $latitude = $request['latitude'];
        $longitude = $request['longitude'];
        $radius = $request['radius'] ?? 10; // Default to 10km if not provided

        // Haversine formula to calculate distance
        $offers = Offer::select(
            'offers.id',
            // 'stores.name as store_name',
            'offers.name',
            'offers.image',
            'offers.address',
            'offers.about',
            'offers.latitude',
            'offers.longitude',
            'offers.start_date',
            'offers.end_date',
            DB::raw(
                "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude)))) AS distance"
            )
        )
        // ->join('stores', 'offers.store_id', '=', 'stores.id')
        ->having('distance', '<=', $radius) // Filter by radius
        ->orderBy('distance', 'asc') // Sort by closest
        ->get();

        // Return the stores as a JSON response
        return response()->json($offers, Response::HTTP_OK);
    }
}
