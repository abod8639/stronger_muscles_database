<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Repositories\NotificationRepository;
use App\Services\Notification\PushNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        protected NotificationRepository $notificationRepository,
        protected PushNotificationService $pushNotificationService
    ) {}

    /**
     * Send notification from admin dashboard and dispatch push notification.
     */
    public function sendFromAdmin(array $data, ?int $adminId = null): Notification
    {
        $payload = [
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'general',
            'target_type' => $data['target_type'] ?? 'all',
            'user_id' => ($data['target_type'] ?? 'all') === 'user' ? ($data['user_id'] ?? null) : null,
            'data' => $data['data'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'sent_by' => $adminId,
        ];

        $notification = $this->notificationRepository->create($payload);

        // Push notification delivery
        $pushData = array_merge($notification->data ?? [], [
            'notification_id' => (string) $notification->id,
            'type' => $notification->type,
        ]);

        if ($notification->target_type === 'user' && $notification->user_id) {
            $user = User::find($notification->user_id);
            if ($user) {
                $this->pushNotificationService->sendToUser(
                    $user,
                    $notification->title,
                    $notification->body,
                    $pushData
                );
            }
        } elseif ($notification->target_type === 'all') {
            User::query()
                ->whereNotNull('fcm_token')
                ->chunkById(100, function ($users) use ($notification, $pushData) {
                    foreach ($users as $user) {
                        $this->pushNotificationService->sendToUser(
                            $user,
                            $notification->title,
                            $notification->body,
                            $pushData
                        );
                    }
                });
        }

        return $notification;
    }

    /**
     * Record an automated order notification and dispatch push.
     */
    public function recordOrderNotification(User $user, Order $order, string $title, string $body, string $status): Notification
    {
        $data = [
            'order_id' => (string) $order->id,
            'status' => $status,
        ];

        $notification = $this->notificationRepository->create([
            'title' => $title,
            'body' => $body,
            'type' => 'order',
            'target_type' => 'user',
            'user_id' => $user->id,
            'data' => $data,
            'sent_by' => null,
        ]);

        $this->pushNotificationService->sendToUser(
            $user,
            $title,
            $body,
            array_merge($data, [
                'notification_id' => (string) $notification->id,
                'type' => 'order',
            ])
        );

        return $notification;
    }

    /**
     * Customer: Get notifications.
     */
    public function getCustomerNotifications(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->notificationRepository->getForUser($user, $perPage);
    }

    /**
     * Customer: Get unread count.
     */
    public function getCustomerUnreadCount(User $user): int
    {
        return $this->notificationRepository->getUnreadCountForUser($user);
    }

    /**
     * Customer: Mark single as read.
     */
    public function markAsRead(User $user, int $notificationId): bool
    {
        return $this->notificationRepository->markAsReadForUser($user, $notificationId);
    }

    /**
     * Customer: Mark all as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Admin: Get paginated notifications.
     */
    public function getAdminNotifications(?string $search = null, ?string $type = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->getAllPaginated($search, $type, $perPage);
    }

    /**
     * Admin: Get stats.
     */
    public function getStats(): array
    {
        return $this->notificationRepository->getStats();
    }

    /**
     * Admin: Delete notification.
     */
    public function deleteNotification(int|string $id): bool
    {
        $notification = $this->notificationRepository->findOrFail($id);

        return $this->notificationRepository->delete($notification);
    }
}
