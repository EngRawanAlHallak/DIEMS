<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class DeleteNotificationAction
{
    public function execute(int $notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->delete();

        // تدمير الكاش القديم
        Cache::increment('admin_notifications_version');

        return true;
    }
}
