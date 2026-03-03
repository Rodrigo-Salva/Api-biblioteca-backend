<?php

namespace App\Http\Service;

use App\Models\Notification;

class NotificationService
{
    public function createNotification($userId, $type, $title, $message)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    public function getNotificationsForUser($user)
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return $notification;
    }
}
