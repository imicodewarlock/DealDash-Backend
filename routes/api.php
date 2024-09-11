<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('auth')->group(function() {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware(JWTMiddleware::class);
});

Route::prefix('admin')->group(function() {

    // Users routes
    Route::apiResource('users', UserController::class);
    // Route::get('users', [UserController::class, 'index']);
    // Route::post('users', [UserController::class, 'store']);
    // Route::get('users/{user}', [UserController::class, 'show']);
    // Route::patch('users/{user}', [UserController::class, 'update']);
    // Route::delete('users/{user}', [UserController::class, 'destroy']);

    // Additional routes for soft delete functionality
    Route::get('users/trashed', [UserController::class, 'trashed']); // View soft-deleted stores
    Route::post('users/{id}/restore', [UserController::class, 'restore']); // Restore soft-deleted store
    Route::delete('users/{id}/force-delete', [UserController::class, 'forceDelete']); // Permanently delete store

    // Categories routes
    Route::apiResource('categories', CategoryController::class);
    // Additional routes for soft delete functionality
    Route::get('categories/trashed', [CategoryController::class, 'trashed']); // View soft-deleted stores
    Route::post('categories/{id}/restore', [CategoryController::class, 'restore']); // Restore soft-deleted store
    Route::delete('categories/{id}/force-delete', [CategoryController::class, 'forceDelete']); // Permanently delete store

    // Stores routes
    Route::apiResource('stores', StoreController::class);
    // Additional routes for soft delete functionality
    Route::get('stores/trashed', [StoreController::class, 'trashed']); // View soft-deleted stores
    Route::post('stores/{id}/restore', [StoreController::class, 'restore']); // Restore soft-deleted store
    Route::delete('stores/{id}/force-delete', [StoreController::class, 'forceDelete']); // Permanently delete store

    // Offers routes
    Route::apiResource('offers', OfferController::class);
    // Additional routes for soft delete functionality
    Route::get('offers/trashed', [OfferController::class, 'trashed']); // View soft-deleted stores
    Route::post('offers/{id}/restore', [OfferController::class, 'restore']); // Restore soft-deleted store
    Route::delete('offers/{id}/force-delete', [OfferController::class, 'forceDelete']); // Permanently delete store
});

Route::prefix('v1')->group(function() {
    Route::get('nearby-stores', [StoreController::class, 'getNearbyStores']);
    Route::get('nearby-offers', [OfferController::class, 'getNearbyOffers']);
});
