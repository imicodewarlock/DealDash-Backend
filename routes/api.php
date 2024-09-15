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

Route::prefix('admin')->middleware(JWTMiddleware::class)->group(function() {

    // Users routes
    Route::apiResource('users', UserController::class);
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

// Route::prefix('v1')->middleware('auth.jwt')->group(function() {
//     Route::get('/user/categories', [CategoryController::class, 'index']);
//     Route::get('/user/categories/{id}', [CategoryController::class, 'show']);

//     Route::get('/user/stores', [StoreController::class, 'index']);
//     Route::get('/user/stores/{id}', [StoreController::class, 'show']);
//     Route::get('/user/stores/nearby-stores', [StoreController::class, 'getNearbyStores']);
//     Route::post('/user/stores/{id}/favorite', [StoreController::class, 'toggleFavorite']);


//     Route::get('/user/offers', [OfferController::class, 'index']);
//     Route::get('/user/offers/{id}', [OfferController::class, 'show']);
//     Route::get('/user/offers/nearby-offers', [OfferController::class, 'getNearbyOffers']);
// });

Route::prefix('v1')->middleware('auth.jwt')->group(function() {
    Route::prefix('user')->group(function(){
        Route::get('categories/list-all', [CategoryController::class, 'index']);
        Route::get('categories/get-category/{id}', [CategoryController::class, 'show']);

        Route::get('stores/list-all', [StoreController::class, 'index']);
        Route::get('stores/get-store/{id}', [StoreController::class, 'show']);
        Route::get('stores/nearby-stores', [StoreController::class, 'getNearbyStores']);
        Route::post('stores/{id}/favorite', [StoreController::class, 'toggleFavorite']);

        Route::get('offers/list-all', [OfferController::class, 'index']);
        Route::get('offers/get-offer/{id}', [OfferController::class, 'show']);
        Route::get('offers/nearby-offers', [OfferController::class, 'getNearbyOffers']);
    });


    // Route::get('nearby-offers', [OfferController::class, 'getNearbyOffers']);

});
