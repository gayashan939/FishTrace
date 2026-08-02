<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification
{
    use Queueable;

    public function __construct(private NotificationType $notificationType, private string $title, private string $message, private array $context = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => $this->notificationType->value, 'title' => $this->title, 'message' => $this->message, 'context' => $this->context];
    }
}
