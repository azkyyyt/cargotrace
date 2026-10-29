<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function send(User $user, string $title, string $message, string $type = 'info', ?string $link = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'link' => $link,
        ]);
    }

    public static function broadcastToOperatorsAndAdmins(string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $recipients = User::whereIn('role', ['admin', 'operator'])->get();
        foreach ($recipients as $recipient) {
            self::send($recipient, $title, $message, $type, $link);
        }
    }
}
