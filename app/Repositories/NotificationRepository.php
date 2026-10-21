<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository
{
    /**
     * Get paginated notifications for Admin with optional search and type filtering.
     */
    public function getAllPaginated(?string $search = null, ?string $type = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Notification::query()
            ->with(['user:id,name,email', 'admin:id,name'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get paginated notifications visible to a specific customer.
     */
    public function getForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::query()
            ->select('notifications.*')
            ->selectRaw('CASE WHEN notification_reads.id IS NOT NULL THEN 1 ELSE 0 END AS is_read')
            ->leftJoin('notification_reads', function ($join) use ($user) {
                $join->on('notification_reads.notification_id', '=', 'notifications.id')
                    ->where('notification_reads.user_id', '=', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where('notifications.target_type', 'all')
                    ->orWhere('notifications.user_id', $user->id);
            })
            ->latest('notifications.created_at')
            ->paginate($perPage);
    }

    /**
     * Get count of unread notifications for a customer.
     */
    public function getUnreadCountForUser(User $user): int
    {
        return Notification::query()
            ->where(function ($q) use ($user) {
                $q->where('notifications.target_type', 'all')
                    ->orWhere('notifications.user_id', $user->id);
            })
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }

    /**
     * Mark a single notification as read for a customer.
     */
    public function markAsReadForUser(User $user, int $notificationId): bool
    {
        NotificationRead::firstOrCreate(
            [
                'user_id' => $user->id,
                'notification_id' => $notificationId,
            ],
            [
                'read_at' => now(),
            ]
        );

        return true;
    }

    /**
     * Mark all notifications as read for a customer.
     */
    public function markAllAsReadForUser(User $user): int
    {
        $unreadNotificationIds = Notification::query()
            ->where(function ($q) use ($user) {
                $q->where('notifications.target_type', 'all')
                    ->orWhere('notifications.user_id', $user->id);
            })
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id');

        $now = now();
        $records = $unreadNotificationIds->map(fn ($id) => [
            'user_id' => $user->id,
            'notification_id' => $id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (! empty($records)) {
            NotificationRead::insertOrIgnore($records);
        }

        return count($records);
    }

    /**
     * Create a new notification record.
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Find notification by ID or fail.
     */
    public function findOrFail(int|string $id): Notification
    {
        return Notification::with(['user:id,name,email', 'admin:id,name'])->findOrFail($id);
    }

    /**
     * Delete notification.
     */
    public function delete(Notification $notification): bool
    {
        return (bool) $notification->delete();
    }

    /**
     * Get aggregate statistics for dashboard.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'total' => Notification::count(),
            'sent_today' => Notification::whereDate('created_at', today())->count(),
            'broadcast' => Notification::where('target_type', 'all')->count(),
            'targeted' => Notification::where('target_type', 'user')->count(),
        ];
    }
}
