<?php

use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\AdminThemeController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Api\V1\Admin\StoreSettingController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\Customer\CartController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\V1\Customer\PaymentController;
use App\Http\Controllers\Api\V1\Customer\PromoController as CustomerPromoController;
use App\Http\Controllers\Api\V1\Customer\WishlistController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\Webhook\PaymentWebhookController;
use App\Http\Controllers\ImageUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // --- 1. Admin Routes (Dashboard) ---
    Route::middleware(['auth:admin-api', 'isAdmin'])->prefix('admin')->group(function () {
        Route::apiResource('products', AdminProductController::class)->except(['destroy']);
        Route::apiResource('categories', AdminCategoryController::class)->except(['destroy']);
        Route::apiResource('promos', AdminPromoController::class)->except(['destroy']);
        Route::apiResource('brands', \App\Http\Controllers\Api\V1\Admin\BrandController::class)->except(['destroy']);

        // Restricted Actions (Super Admin & Admin only)
        Route::middleware(['admin.role:super_admin,admin'])->group(function () {
            Route::delete('products/{product}', [AdminProductController::class, 'destroy']);
            Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy']);
            Route::delete('promos/{promo}', [AdminPromoController::class, 'destroy']);
            Route::delete('brands/{brand}', [\App\Http\Controllers\Api\V1\Admin\BrandController::class, 'destroy']);
            Route::apiResource('users', AdminUserController::class)->only(['index']);
            Route::put('/settings', [StoreSettingController::class, 'update']);
        });

        // Dashboard home, sidebar badges and store settings
        Route::get('/dashboard', [DashboardController::class, 'stats']);
        Route::get('/navigation/badges', [DashboardController::class, 'navigationBadges']);
        Route::get('/settings', [StoreSettingController::class, 'show']);

        // Admin Auth actions
        Route::get('/profile', [AdminAuthController::class, 'getProfile']);
        Route::get('/profile/theme', [AdminThemeController::class, 'show']);
        Route::put('/profile/theme', [AdminThemeController::class, 'update']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);

        // Orders management
        Route::get('/orders/alerts', [AdminOrderController::class, 'alerts']);
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{id}', [AdminOrderController::class, 'update']);
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        // Notifications management
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\Admin\NotificationController::class, 'index']);
        Route::post('/notifications', [\App\Http\Controllers\Api\V1\Admin\NotificationController::class, 'store']);
        Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\V1\Admin\NotificationController::class, 'destroy']);

        // Support & Complaints management
        Route::get('/support/tickets/stats', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'stats']);
        Route::get('/support/tickets', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'index']);
        Route::get('/support/tickets/{id}', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'show']);
        Route::post('/support/tickets/{id}/messages', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'addMessage']);
        Route::patch('/support/tickets/{id}/status', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'updateStatus']);
        Route::patch('/support/tickets/{id}/priority', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'updatePriority']);
        Route::patch('/support/tickets/{id}/assign', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'assign']);
        Route::delete('/support/tickets/{id}', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'destroy']);

        // Uploads
        Route::post('/upload/product-image', [ImageUploadController::class, 'uploadProductImage']);
        Route::post('/upload/category-image', [ImageUploadController::class, 'uploadCategoryImage']);
        Route::post('/upload/promo-image', [ImageUploadController::class, 'uploadImage']);
        Route::post('/upload/image', [ImageUploadController::class, 'uploadImage']);
        Route::post('/upload/delete', [ImageUploadController::class, 'deleteImage']);
    });

    // Public Admin Auth Routes
    Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/register', [AdminAuthController::class, 'register']);

    // --- 2. Customer Routes (App) ---
    // Protected by auth
    Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
        Route::get('/profile', [AuthController::class, 'getProfile']); // Assuming typical profile fetch if needed, or use /user
        Route::apiResource('cart', CartController::class);

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
        Route::apiResource('orders', CustomerOrderController::class)->only(['index', 'show', 'store']);

        // Address Management
        Route::apiResource('addresses', \App\Http\Controllers\Api\V1\Customer\AddressController::class);
        Route::post('/addresses/{id}/set-default', [\App\Http\Controllers\Api\V1\Customer\AddressController::class, 'setDefault']);

        // Payment
        Route::post('/orders/{id}/pay', [PaymentController::class, 'initiatePayment']);

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\Customer\NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\V1\Customer\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\Customer\NotificationController::class, 'markAllAsRead']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\V1\Customer\NotificationController::class, 'markAsRead']);
        Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

        // Reviews
        Route::post('/products/{productId}/reviews', [\App\Http\Controllers\Api\V1\Customer\ReviewController::class, 'store']);
        Route::get('/products/{productId}/user-review', [\App\Http\Controllers\Api\V1\Customer\ReviewController::class, 'userReview']);
        Route::delete('/reviews/{id}', [\App\Http\Controllers\Api\V1\Customer\ReviewController::class, 'destroy']);

        // Support Tickets & Complaints
        Route::get('/support/tickets', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'index']);
        Route::post('/support/tickets', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'store']);
        Route::get('/support/tickets/{id}', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'show']);
        Route::post('/support/tickets/{id}/messages', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'addMessage']);
        Route::post('/support/tickets/{id}/close', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'close']);
    });

    // --- 3. Public Routes (Shop/Guest) ---
    // Read-only access for everyone
    Route::prefix('shop')->group(function () {
        Route::get('/promos', [CustomerPromoController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/products/{id}/reviews', [\App\Http\Controllers\Api\V1\ReviewController::class, 'index']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);

        // Guest support ticket submission
        Route::post('/support/tickets', [\App\Http\Controllers\Api\V1\Customer\SupportTicketController::class, 'store']);
    });

    // --- 4. Webhooks ---
    Route::post('/webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');

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
        Route::middleware('auth:sanctum')->post('/fcm-token', [AuthController::class, 'updateFcmToken']);
    });

    // Helper for authenticated user
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});
