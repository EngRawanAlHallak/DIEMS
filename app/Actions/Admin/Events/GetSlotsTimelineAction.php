<?php

namespace App\Actions\Admin\Events;

use App\Http\Resources\SlotTimelineResource;
use App\Models\EventSlot;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class GetSlotsTimelineAction
{
    public function execute(): array
    {
        $cacheKey = "admin:events_timeline:all";
        Cache::forget("admin:events_timeline:all");
        return Cache::remember($cacheKey, now()->addDays(1), function () {

            // 1. جلب كل الأوقات (Slots) مع الطلبات الموافق عليها فقط
            $slots = EventSlot::query()
                ->with(['event_requests' => function ($query) {
                    // نجلب فقط الطلب الموافق عليه داخل هذا الـ Slot مع اسم قاعته
                    $query->where('request_status', 'approved')
                        ->with('hall:id,name');
                }])
                ->orderBy('slot_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            // 2. تجميع البيانات وتمريرها للـ Resource الجديد
            $groupedTimeline = $slots->groupBy('slot_date')->map(function ($daySlots) {
                return SlotTimelineResource::collection($daySlots)->resolve();
            });

            // 3. ترتيب هيكل الاستجابة المطلوب
            return [
                'timeline' => $groupedTimeline,
                'halls'    => Hall::query()
                    ->select('id')
                    ->selectRaw("name->>'en' as name")
                    ->toBase()
                    ->get()
            ];
        });
    }
}
