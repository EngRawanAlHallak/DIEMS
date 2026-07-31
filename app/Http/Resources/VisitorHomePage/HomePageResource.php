<?php

namespace App\Http\Resources\VisitorHomePage;

use App\Http\Resources\EventResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HomePageResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = app()->getLocale();

        return [
            // موقع المعرض
            'exhibition_location' => $this->resource['address'] ?? null,

            // الفعالية الأكثر رواجاً (نستخدم نفس الـ EventResource الذي ضبطناه سابقاً)
            'special_event' => $this->resource['special_event']
                ? new HomeEventResource($this->resource['special_event'])
                : null,

            // القطاعات
            'sectors' => $this->resource['sectors']->map(function($sector) {
                return [
                    'id'   => $sector->id,
                    'name' => $sector->name,
                ];
            }),

            // أفضل 10 شركات
            'top_companies' => $this->resource['top_companies']->map(function($company) {
                return [
                    'id'   => $company->id,
                    'name' => $company->name,
                    'logo' => $company->logo ? Storage::disk('s3')->url($company->logo) : null,
                ];
            }),

            // فعاليات الأسبوع
            'weekly_events' => $this->resource['weekly_events']
                ->groupBy(fn($event) => $event->slot->slot_date) // التجميع حسب التاريخ
                ->map(function ($events, $date) use ($lang) {
                    return [
                        'date'   => $date,
                        'day'    => $this->formatEventDay($date, $lang), // استخراج اليوم
                        'events' => HomeEventResource::collection($events), // قائمة الفعاليات لهذا اليوم
                    ];
                })->values(),

            // الشركات المشاركة (Pagination)
            'participating_companies' => [
                'items' => HomeCompaniesResource::collection($this->resource['participating_companies']->items()),
                'pagination' => [
                    'current_page' => $this->resource['participating_companies']->currentPage(),
                    'last_page'    => $this->resource['participating_companies']->lastPage(),
                    'total'        => $this->resource['participating_companies']->total(),
                ]
            ],
        ];
    }

    private function formatEventDay($date, $lang)
    {
        Carbon::setLocale($lang);
        return Carbon::parse($date)->translatedFormat('l');
    }
}
