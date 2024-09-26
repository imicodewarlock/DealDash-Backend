<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\OfferNotification;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

use function PHPUnit\Framework\isEmpty;

class OfferController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * GET /api/admin/offers
     *
     * Display a listing of offers (only non-deleted ones)
     */
    public function index()
    {
        $offers = Offer::withTrashed()->get();

        if ($offers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('offer.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('offer.all_records'),
                'errors' => [],
                'data' => $offers,
            ], Response::HTTP_OK);
        }
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
            'address' => 'required|string|max:255',
            'about' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('offer.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
        }

        $imageUrl = null;
        // if ($request->hasFile('image')) {
        //     // Define the path where the image will be stored
        //     $destinationPath = public_path('img/offers');

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
        //     $imageUrl = config('app.url') . '/img/offers/' . $imageName;
        // }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('img/offers/', $imageName, 's3');
            $imageUrl = Storage::disk('s3')->url('img/offers/' . $imageName);
        }

        $offer = Offer::create([
            'name' => $request->name,
            'store_id' => $request->store_id,
            'image' => $imageUrl,
            'address' => $request->address,
            'about' => $request->about,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        // $userTokens = User::withoutTrashed()->pluck('fcm_token')->whereNotNull('fcm_token')->toArray();
        // Fetch tokens of users to notify
        $users = User::withoutTrashed()
                    ->whereNotNull('fcm_token')
                    ->get();

        // if (!isEmpty($users)) {
        if ($users) {
            // Push notification to each user
            foreach ($users as $user) {
                OfferNotification::create([
                    'offer_id' => $offer->id,
                    'user_id' => $user->id,
                    'is_read' => false
                ]);

                // Send FCM notification
                $this->fcmService->sendNotification(
                    $offer->name,
                    $offer->about,
                    $user->offer,
                    ['offer_id' => $offer->id, 'offer_image' => $offer->image]
                );
            }

            return response()->json([
                'success' => true,
                'message' => __('offer.added_with_notification'),
                'errors' => [],
                'data' => $offer,
            ], Response::HTTP_CREATED);
        }

        return response()->json([
            'success' => true,
            'message' => __('offer.added'),
            'errors' => [],
            'data' => $offer,
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/admin/offers/{offer}
     *
     * Display a specific offer
     */
    public function show($id)
    {
        $offer = Offer::withTrashed()->find($id);

        if ($offer) {
            return response()->json([
                'success' => true,
                'message' => __('offer.found'),
                'errors' => [],
                'data' => $offer,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * PUT /api/admin/offers/{offer}
     *
     * Update a specific offer
     */
    public function update(Request $request, $id)
    {
        $offer = Offer::withTrashed()->find($id);

        if ($offer) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'store_id' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'address' => 'required|string|max:255',
                'about' => 'required',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'start_date' => 'required',
                'end_date' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('offer.failed'),
                    'errors' => $validator->errors(),
                    'data' => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            $offer->name = $request->name ?? $offer->name;
            $offer->store_id = $request->store_id ?? $offer->store_id;
            $offer->address = $request->address ?? $offer->address;
            $offer->about = $request->about ?? $offer->about;
            $offer->latitude = $request->latitude ?? $offer->latitude;
            $offer->longitude = $request->longitude ?? $offer->longitude;
            $offer->start_date = $request->start_date ?? $offer->start_date;
            $offer->end_date = $request->end_date ?? $offer->end_date;

            $imageUrl = null;
            // if ($request->hasFile('image')) {
            //     // Define the path where the image will be stored
            //     $destinationPath = public_path('img/offers');

            //     // Check if the directory exists, if not, create it
            //     if (!File::exists($destinationPath)) {
            //         File::makeDirectory($destinationPath, 0755, true); // Create the directory with the correct permissions
            //     }

            //     // first unlink the old avatar
            //     if ($offer->image) {
            //         $parsedUrl = parse_url($offer->image);
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
            //     $imageUrl = config('app.url') . '/img/offers/' . $imageName;

            //     $offer->image = $imageUrl ?? $offer->image;
            // }

            if ($request->hasFile('image')) {
                // first unlink the old avatar
                if ($offer->image) {
                    // Extract the S3 path from the avatar URL
                    $oldImagePath = parse_url($offer->image, PHP_URL_PATH);
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

                $image->storeAs('img/offers/', $imageName, 's3');

                // Generate the full URL to the image
                $imageUrl = Storage::disk('s3')->url('img/offers/' . $imageName);
            }

            $offer->image = $imageUrl ?? $offer->image;

            $offer->update();
            return response()->json([
                'success' => true,
                'message' => __('offer.updated'),
                'errors' => [],
                'data' => $offer,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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

            return response()->json([
                'success' => true,
                'message' => __('offer.disabled'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK); // or 204
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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

        if ($offers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('offer.disabled_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('offer.disabled_records'),
                'errors' => [],
                'data' => $offers,
            ], Response::HTTP_OK);
        }


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

            return response()->json([
                'success' => true,
                'message' => __('offer.restored'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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
            // if ($offer->image) {
            //     $parsedUrl = parse_url($offer->image);
            //     $oldImage = basename($parsedUrl['path']);
            //     unlink(public_path('img/offers') . '/' . $oldImage);
            // }

            if ($offer->image) {
                // Extract the S3 path from the avatar URL
                $oldImagePath = parse_url($offer->image, PHP_URL_PATH);
                $oldImagePath = ltrim($oldImagePath, '/'); // Remove leading slash

                // if (Storage::disk('s3')->exists($oldImagePath)) {
                //     Storage::disk('s3')->delete($oldImagePath);
                // }

                Storage::disk('s3')->delete($oldImagePath);
            }

            $offer->forceDelete();

            return response()->json([
                'success' => true,
                'message' => __('offer.deleted'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * GET /api/admin/offers
     *
     * Display a listing of offers (only non-deleted ones)
     */
    public function getAvailableOffers()
    {
        $offers = Offer::withoutTrashed()->get();

        if ($offers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('offer.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('offer.all_records_err'),
                'errors' => [],
                'data' => $offers,
            ], Response::HTTP_OK);
        }
    }

    /**
     * GET /api/admin/offers/{offer}
     *
     * Display a specific offer
     */
    public function getSingleOffer($id)
    {
        $offer = Offer::withoutTrashed()->find($id);

        if ($offer) {
            return response()->json([
                'success' => true,
                'message' => __('offer.found'),
                'errors' => [],
                'data' => $offer,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('offer.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
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
            return response()->json([
                'success' => false,
                'message' => __('offer.failed'),
                'errors' => $validator->errors(),
                'data' => [],
            ], Response::HTTP_BAD_REQUEST);
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
        if (!$offers) {
            return response()->json([
                'success' => false,
                'message' => __('offer.nearby_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        } else {
            return response()->json([
                'success' => true,
                'message' => __('offer.nearby_records'),
                'errors' => [],
                'data' => $offers,
            ], Response::HTTP_OK);
        }
    }
}
