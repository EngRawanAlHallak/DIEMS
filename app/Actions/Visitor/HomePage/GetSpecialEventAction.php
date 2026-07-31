<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\EventRequest;
use Illuminate\Support\Facades\Cache;

class GetSpecialEventAction
{
    public function execute(string $date, string $lang)
    {
        Cache::forget("home:special_event:{$date}:{$lang}");
        return Cache::remember("home:special_event:{$date}:{$lang}", now()->addHours(2), function () use ($date) {
            return EventRequest::query()
                ->where('request_status', 'approved')
                ->where('payment_status', 'paid')
                ->whereHas('slot', fn($q) => $q->where('slot_date', $date))
                ->with('sector:id,name','hall:id,name')
                ->orderBy('Expected_attendance', 'DESC')
                ->first();
        });
    }
}
