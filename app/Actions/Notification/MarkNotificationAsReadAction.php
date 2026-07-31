<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class MarkNotificationAsReadAction
{
    public function execute(int $notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->update(['status' => 'read']);

        // تدمير الكاش القديم بلمحة بصر
        Cache::increment('admin_notifications_version');

        return $notification;
    }
}
