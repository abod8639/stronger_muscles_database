<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\ImageUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('/auth/google-signin', [AuthController::class, 'googleSignIn']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('/auth/update-profile', [AuthController::class, 'updateProfile']);

    // Public routes (Read and Write for anyone)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Dashboard routes
    Route::get('/dashboard/users-stats', [DashboardController::class, 'index']);

    // Image Upload routes
    Route::post('/upload/product-image', [ImageUploadController::class, 'uploadProductImage']);
    Route::post('/upload/category-image', [ImageUploadController::class, 'uploadCategoryImage']);
    Route::post('/upload/image', [ImageUploadController::class, 'uploadImage']);
    Route::post('/upload/delete', [ImageUploadController::class, 'deleteImage']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Protected routes (Only those that explicitly need a user identity)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Cart routes
        Route::apiResource('cart', CartItemController::class);

        // Order creation (Keep protected if you want orders linked to Users)
        Route::post('/orders', [OrderController::class, 'store']);
    });
});


// Route::prefix('v1')->group(function () {

//     // --- 1. مسارات لوحة التحكم (Dashboard/Admin) ---
//     // يجب حمايتها بـ Middleware للتأكد من أن المستخدم "Admin"
//     Route::middleware(['auth:sanctum', 'isAdmin'])->prefix('admin')->group(function () {
//         Route::apiResource('products', AdminProductController::class);
//         Route::apiResource('categories', AdminCategoryController::class);
//         Route::get('/stats', [DashboardController::class, 'index']);
//         Route::post('/upload', [ImageUploadController::class, 'upload']);
//     });

//     // --- 2. مسارات التطبيق (Customer/Authenticated) ---
//     // خاصة بالمستخدم المسجل فقط
//     Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
//         Route::get('/profile', [AuthController::class, 'profile']);
//         Route::apiResource('cart', CartController::class);
//         Route::post('/orders', [OrderController::class, 'store']);
//         Route::get('/my-orders', [OrderController::class, 'userOrders']);
//     });

//     // --- 3. مسارات عامة (Public/Guest) ---
//     // متاحة للكل (تطبيق + موقع) للعرض فقط
//     Route::get('/shop/products', [PublicProductController::class, 'index']);
//     Route::get('/shop/categories', [PublicCategoryController::class, 'index']);
// });