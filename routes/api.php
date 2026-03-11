<?php

use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Customer\CartController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\ImageUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Api\V1\Customer\PromoController as CustomerPromoController;

Route::prefix('v1')->group(function () {

    // --- 1. Admin Routes (Dashboard) ---
    Route::middleware(['auth:admin-api'])->prefix('admin')->group(function () {
        Route::apiResource('products', AdminProductController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('users', AdminUserController::class)->only(['index']);
        Route::apiResource('promos', AdminPromoController::class);

        // Admin Auth actions
        Route::get('/profile', [AdminAuthController::class, 'getProfile']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        // Orders management
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{id}', [AdminOrderController::class, 'update']);

        // Uploads
        Route::post('/upload/product-image', [ImageUploadController::class, 'uploadProductImage']);
        Route::post('/upload/category-image', [ImageUploadController::class, 'uploadCategoryImage']);
        Route::post('/upload/promo-image', [ImageUploadController::class, 'uploadImage']);
        Route::post('/upload/image', [ImageUploadController::class, 'uploadImage']);
        Route::post('/upload/delete', [ImageUploadController::class, 'deleteImage']);
    });

    // Public Admin Login
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    // --- 2. Customer Routes (App) ---
    // Protected by auth
    Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
        Route::get('/profile', [AuthController::class, 'getProfile']); // Assuming typical profile fetch if needed, or use /user
        Route::apiResource('cart', CartController::class);
        Route::apiResource('orders', CustomerOrderController::class)->only(['index', 'show', 'store']);

        // Address Management
        Route::apiResource('addresses', \App\Http\Controllers\Api\V1\Customer\AddressController::class);
        Route::post('/addresses/{id}/set-default', [\App\Http\Controllers\Api\V1\Customer\AddressController::class, 'setDefault']);
    });

    // --- 3. Public Routes (Shop/Guest) ---
    // Read-only access for everyone
    Route::prefix('shop')->group(function () {
        Route::get('/promos', [CustomerPromoController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
    });

    // --- Auth Routes ---
    Route::prefix('auth')->group(function () {
        Route::post('/google-signin', [AuthController::class, 'googleSignIn']);

        // Rate-limited auth endpoints (5 attempts per minute)
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/register', [AuthController::class, 'register']);
        });

        Route::get('/test-login', [AuthController::class, 'testLogin']);
        Route::middleware('auth:sanctum')->post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    // Helper for authenticated user
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});
