<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;

class GetAdminNotificationsAction
{
    public function execute(array $filters)
    {
        // جلب رقم الإصدار الحالي للكاش (أو إنشاءه بـ 1)
        $version = Cache::rememberForever('admin_notifications_version', fn () => 1);

        $cacheKey = "admin:notifications:v{$version}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters) {
            $query = Notification::query();

            // الفلترة الديناميكية
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            /*if (!empty($filters['time']) && !empty($filters['time'])) {
                $query->where('created_at');
            }*/

            // الترتيب والترقيم السريع
            $paginator = $query->latest()->simplePaginate(20);

            // تحويل البيانات لمصفوفة لكي يحفظها Redis كـ Array جاهزة (أعلى أداء)
            return [
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ]
            ];
        });
    }
}
