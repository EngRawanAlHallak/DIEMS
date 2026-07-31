<?php

namespace App\Actions\Visitor;

use App\Models\ExhibitionProfile;
use App\Models\EventRequest;
use App\Models\Sector;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetHomePageDataAction
{
    public function execute(int $page = 1): array
    {
        $lang = app()->getLocale();
        $today = Carbon::today()->toDateString();
        $nextWeek = Carbon::today()->addDays(7)->toDateString();

        // 1. موقع المعرض (كاش طويل الأمد لأنها لا تتغير غالباً)
        Cache::forget("home:address:{$lang}");
        $exhibitionAddress = Cache::remember("home:address:{$lang}", now()->addDays(1), function () {
            return ExhibitionProfile::select('address')->latest()->first();
        });

        // 2. الفعالية الأكثر رواجاً لليوم (مميزة)
        // ملاحظة: قمنا بترتيبها برمجياً دون الحاجة لتحديث الـ DB في كل مرة
        $specialEvent = Cache::remember("home:special_event:{$today}:{$lang}", now()->addHours(2), function () use ($today) {
            return EventRequest::query()
                ->where('request_status', 'approved')
                ->where('payment_status', 'paid')
                ->whereHas('slot', fn($q) => $q->where('slot_date', $today))
                ->with('sector:id,name')
                // استخدام CAST لأن الحقل Expected_attendance نوعه string في الداتا بيز
                ->orderByRaw('CAST("Expected_attendance" AS INTEGER) DESC')
                ->first();
        });

        // 3. جميع القطاعات
        $sectors = Cache::remember("home:sectors:{$lang}", now()->addDays(1), function () {
            return Sector::select('id', 'name')->get();
        });

        // 4. أفضل 10 شركات (عشوائياً) - كاش لساعة لتجنب بطء inRandomOrder
        $topCompanies = Cache::remember("home:top_companies:{$lang}", now()->addHour(), function () {
            return Company::where('is_active', true)
                ->inRandomOrder()
                ->take(10)
                ->select('id', 'name', 'logo')
                ->get();
        });

        // 5. فعاليات هذا الأسبوع
        $weeklyEvents = Cache::remember("home:weekly_events:{$today}:{$lang}", now()->addHours(2), function () use ($today, $nextWeek) {
            return EventRequest::query()
                ->where('request_status', 'approved')
                ->where('payment_status', 'paid')
                ->whereHas('slot', fn($q) => $q->whereBetween('slot_date', [$today, $nextWeek]))
                ->with(['slot:id,slot_date,start_time', 'sector:id,name'])
                ->select('id', 'event_title', 'image', 'slot_id', 'sector_id')
                ->orderBy(
                    \App\Models\EventSlot::select('start_time')
                        ->whereColumn('event_slots.id', 'event_requests.slot_id')
                        ->limit(1)
                )
                ->get();
        });

        // 6. الشركات المشاركة (مع Pagination)
        // لا نضعها في الكاش لأن الـ page تتغير باستمرار
        $participatingCompanies = Company::query()
            ->where('is_active', true)
            ->with(['sector:id,name,hall_id', 'sector.hall:id,name']) // Eager Load للقطاع والقاعة
            ->select('id', 'name', 'logo', 'nationality', 'sector_id')
            ->paginate(10, ['*'], 'page', $page);

        return [
            'address'                 => $exhibitionAddress,
            'special_event'           => $specialEvent,
            'sectors'                 => $sectors,
            'top_companies'           => $topCompanies,
            'weekly_events'           => $weeklyEvents,
            'participating_companies' => $participatingCompanies,
        ];
    }
}
