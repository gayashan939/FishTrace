<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationOperations
{
    public function directory(User $user, array $filters): LengthAwarePaginator
    {
        return $user->notifications()
            ->when(($filters['read'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when(($filters['read'] ?? null) === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('data->type', $type))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markRead(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();

        return $notification->fresh() ?? $notification;
    }

    public function markAllRead(User $user): int
    {
        $notifications = $user->unreadNotifications()->get();
        $notifications->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return $notifications->count();
    }
}
