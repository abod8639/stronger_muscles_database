<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\StoreNotificationRequest;
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
     * Display a paginated listing of notifications with statistics.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $perPage = (int) $request->query('per_page', 15);

        $notifications = $this->notificationService->getAdminNotifications($search, $type, $perPage);

        return NotificationResource::collection($notifications)->additional([
            'status' => 'success',
            'stats' => $this->notificationService->getStats(),
        ]);
    }

    /**
     * Store and broadcast/send a newly created notification.
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->sendFromAdmin(
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الإشعار بنجاح',
            'data' => new NotificationResource($notification->load(['user:id,name,email,photo_url', 'admin:id,name,photo_url'])),
        ], 201);
    }

    /**
     * Delete the specified notification.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->notificationService->deleteNotification($id);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الإشعار بنجاح',
        ]);
    }
}
