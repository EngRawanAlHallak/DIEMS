<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\EventRequest;
use App\Models\EventSlot;
use Illuminate\Support\Facades\Cache;

class GetWeeklyEventsAction
{
    public function execute(string $startDate, string $endDate, string $lang)
    {
        Cache::forget("home:weekly_events:{$startDate}:{$lang}");
        return Cache::remember("home:weekly_events:{$startDate}:{$lang}", now()->addHours(2), function () use ($startDate, $endDate) {
            return EventRequest::query()
                ->paid() // استخدام الـ Scope الذي أنشأناه
                ->join('event_slots', 'event_requests.slot_id', '=', 'event_slots.id')
                ->whereBetween('event_slots.slot_date', [$startDate, $endDate])
                ->with(['slot:id,slot_date,start_time', 'sector:id,name', 'hall:id,name'])
                // نختار حقول الطلب فقط لتجنب تداخل الـ ID مع جدول السلوتس
                ->select('event_requests.id', 'event_requests.event_title', 'event_requests.image',
                    'event_requests.slot_id', 'event_requests.sector_id', 'event_requests.hall_id')
                ->orderBy('event_slots.slot_date')
                ->orderBy('event_slots.start_time')
                ->get();
        });
    }
}
