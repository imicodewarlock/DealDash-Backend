<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OfferNotificationController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('auth')->group(function() {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.jwt');
});

Route::prefix('admin')->middleware('auth.jwt')->group(function() {

    // Users routes
    // Route::apiResource('users', UserController::class);
    Route::get('/users/list', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/create', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/show', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{id}/edit', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}/disable', [UserController::class, 'destroy'])->name('users.destroy');
    // Additional routes for soft delete functionality
    Route::get('/users/trashed', [UserController::class, 'trashed'])->name('users.trashed'); // View soft-deleted stores
    Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore'); // Restore soft-deleted store
    Route::delete('/users/{id}/force-delete', [UserController::class, 'forceDelete'])->name('users.delete'); // Permanently delete store

    // Categories routes
    // Route::apiResource('categories', CategoryController::class);
    Route::get('/categories/list', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories/create', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{id}/show', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{id}/edit', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}/disable', [CategoryController::class, 'destroy'])->name('categories.destroy');
    // Additional routes for soft delete functionality
    Route::get('categories/trashed', [CategoryController::class, 'trashed'])->name('categories.trashed'); // View soft-deleted stores
    Route::post('categories/{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore'); // Restore soft-deleted store
    Route::delete('categories/{id}/force-delete', [CategoryController::class, 'forceDelete'])->name('categories.delete'); // Permanently delete store

    // Stores routes
    // Route::apiResource('stores', StoreController::class);
    Route::get('/stores/list', [StoreController::class, 'index'])->name('stores.index');
    Route::post('/stores/create', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/{id}/show', [StoreController::class, 'show'])->name('stores.show');
    Route::put('/stores/{id}/edit', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{id}/disable', [StoreController::class, 'destroy'])->name('stores.destroy');
    // Additional routes for soft delete functionality
    Route::get('stores/trashed', [StoreController::class, 'trashed'])->name('stores.trashed'); // View soft-deleted stores
    Route::post('stores/{id}/restore', [StoreController::class, 'restore'])->name('stores.restore'); // Restore soft-deleted store
    Route::delete('stores/{id}/force-delete', [StoreController::class, 'forceDelete'])->name('stores.delete'); // Permanently delete store

    // Offers routes
    // Route::apiResource('offers', OfferController::class);
    Route::get('/offers/list', [OfferController::class, 'index'])->name('offers.index');
    Route::post('/offers/create', [OfferController::class, 'store'])->name('offers.store');
    Route::get('/offers/{id}/show', [OfferController::class, 'show'])->name('offers.show');
    Route::put('/offers/{id}/edit', [OfferController::class, 'update'])->name('offers.update');
    Route::delete('/offers/{id}/disable', [OfferController::class, 'destroy'])->name('offers.destroy');
    // Additional routes for soft delete functionality
    Route::get('offers/trashed', [OfferController::class, 'trashed'])->name('offers.trashed'); // View soft-deleted stores
    Route::post('offers/{id}/restore', [OfferController::class, 'restore'])->name('offers.restore'); // Restore soft-deleted store
    Route::delete('offers/{id}/force-delete', [OfferController::class, 'forceDelete'])->name('offers.delete'); // Permanently delete store
});

Route::prefix('v1')->middleware('auth.jwt')->group(function() {
    Route::prefix('user')->group(function(){
        Route::get('categories/list-all', [CategoryController::class, 'getAvailableCategories']);
        Route::get('categories/get-category/{id}', [CategoryController::class, 'getSingleCategory']);

        Route::get('stores/list-all', [StoreController::class, 'getAvailableStores']);
        Route::get('stores/get-store/{id}', [StoreController::class, 'getSingleStore']);
        Route::get('stores/nearby-stores', [StoreController::class, 'getNearbyStores']);
        Route::post('stores/{id}/favorite', [StoreController::class, 'toggleFavorite']);

        Route::get('offers/list-all', [OfferController::class, 'getAvailableOffers']);
        Route::get('offers/get-offer/{id}', [OfferController::class, 'getSingleOffer']);
        Route::get('offers/nearby-offers', [OfferController::class, 'getNearbyOffers']);

        // Retrieve all notifications for authenticated user
        Route::get('notifications/list-all', [OfferNotificationController::class, 'fetchAll']);
        // Mark a specific notification as read
        Route::put('notifications/{id}/read', [OfferNotificationController::class, 'markAsRead']);
    });
});
