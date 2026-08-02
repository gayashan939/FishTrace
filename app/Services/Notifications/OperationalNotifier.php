<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OperationalNotifier
{
    public function organization(string $organizationId, NotificationType $type, string $title, string $message, array $context = []): void
    {
        $users = User::query()->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))->where('status', 'ACTIVE')->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new OperationalNotification($type, $title, $message, $context));
        }
    }

    public function user(User $user, NotificationType $type, string $title, string $message, array $context = []): void
    {
        $user->notify(new OperationalNotification($type, $title, $message, $context));
    }

    public function organizationOnce(string $eventKey, string $organizationId, NotificationType $type, string $title, string $message, array $context = []): void
    {
        $inserted = DB::table('notification_dispatches')->insertOrIgnore(['id' => (string) Str::uuid(), 'organization_id' => $organizationId, 'event_key' => $eventKey, 'notification_type' => $type->value, 'created_at' => now()]);
        if ($inserted === 1) {
            $this->organization($organizationId, $type, $title, $message, $context);
        }
    }
}
