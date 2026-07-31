<?php

namespace App\Actions\Visitor;

use App\Http\Resources\EventResource;
use App\Models\EventRequest;
use App\Models\EventSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetEventsByDateAction
{
    /**
     * جلب الفعاليات حسب التاريخ مع كامل العلاقات في query واحد.
     * Cache لكل تاريخ منفصل — TTL قصير لأن الأحداث قد تُعتمد خلال اليوم.
     */
    public function execute(string $date)
    {
        $lang = app()->getLocale();

        Cache::forget("events:by_date:{$date}:{$lang}");
        $events = Cache::remember("events:by_date:{$date}:{$lang}", now()->addMinutes(10), function () use ($date) {
            return EventRequest::query()
                ->paid()
                ->with([
                    'slot:id,slot_date,start_time,end_time',
                    'sector:id,name',
                    'hall:id,name',
                ])
                ->forDate($date)
                ->select(['id', 'event_title', 'image', 'slot_id', 'sector_id', 'hall_id'])
                ->orderBy(
                    EventSlot::select('start_time')
                        ->whereColumn('event_slots.id', 'event_requests.slot_id')
                        ->limit(1)
                )
                ->get();
        });
        return EventResource::collection($events)->resolve();
    }
}
