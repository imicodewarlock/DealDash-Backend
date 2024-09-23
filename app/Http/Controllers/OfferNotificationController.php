<?php

namespace App\Http\Controllers;

use App\Models\OfferNotification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfferNotificationController extends Controller
{
    // Retrieve notifications for the authenticated user
    public function fetchAll(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Retrieve the user's notifications
        $notifications = OfferNotification::with('offer')
            ->where('user_id', $user->id)
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('notification.all_records_err'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => __('notification.all_records'),
            'errors' => [],
            'data' => $notifications,
        ], Response::HTTP_OK);
    }

    // Mark notification as read
    public function markAsRead(Request $request, $id)
    {
        // Get the authenticated user
        $user = $request->user();

        $notification = OfferNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => __('notification.not_found'),
                'errors' => [],
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => __('notification.read'),
            'errors' => [],
            'data' => [],
        ], Response::HTTP_OK);
    }
}
