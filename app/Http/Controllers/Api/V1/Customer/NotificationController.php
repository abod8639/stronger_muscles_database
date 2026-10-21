<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a paginated listing of notifications for the authenticated customer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 20);
        $notifications = $this->notificationService->getCustomerNotifications($request->user(), $perPage);

        return NotificationResource::collection($notifications)->additional([
            'status' => 'success',
            'unread_count' => $this->notificationService->getCustomerUnreadCount($request->user()),
        ]);
    }

    /**
     * Get the count of unread notifications for the customer.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $unreadCount = $this->notificationService->getCustomerUnreadCount($request->user());

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), (int) $id);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديد الإشعار كمقروء',
            'unread_count' => $this->notificationService->getCustomerUnreadCount($request->user()),
        ]);
    }

    /**
     * Mark all notifications as read for the customer.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updatedCount = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديد جميع الإشعارات كمقروءة',
            'updated_count' => $updatedCount,
            'unread_count' => 0,
        ]);
    }
}
