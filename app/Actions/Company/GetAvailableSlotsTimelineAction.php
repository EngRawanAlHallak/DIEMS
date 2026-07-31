<?php

namespace App\Actions\Company;

use App\Http\Resources\SlotTimelineResource;
use App\Models\EventSlot;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class GetAvailableSlotsTimelineAction
{
    public function execute(): array
    {
        $cacheKey = "company:events_timeline:all";
       // Cache::forget($cacheKey);
        return Cache::remember($cacheKey, now()->addDays(1), function () {

            $slots = EventSlot::where('available','true')
                ->orderBy('slot_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

            $groupedTimeline = $slots->groupBy('slot_date')->map(function ($daySlots) {
                return SlotTimelineResource::collection($daySlots)->resolve();
            });

            return [
                'timeline' => $groupedTimeline,
            ];
        });
    }
}
