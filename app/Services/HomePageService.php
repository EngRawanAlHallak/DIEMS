<?php

namespace App\Services;

use App\Actions\Visitor\HomePage\GetExhibitionAddressAction;
use App\Actions\Visitor\HomePage\GetSpecialEventAction;
use App\Actions\Visitor\HomePage\GetSectorsAction;
use App\Actions\Visitor\HomePage\GetTopCompaniesAction;
use App\Actions\Visitor\HomePage\GetWeeklyEventsAction;
use App\Actions\Visitor\HomePage\GetParticipatingCompaniesAction;
use Carbon\Carbon;

class HomePageService
{
    public function __construct(
        private GetExhibitionAddressAction $addressAction,
        private GetSpecialEventAction $specialEventAction,
        private GetSectorsAction $sectorsAction,
        private GetTopCompaniesAction $topCompaniesAction,
        private GetWeeklyEventsAction $weeklyEventsAction,
        private GetParticipatingCompaniesAction $participatingCompaniesAction
    ) {}

    /**
     * تجميع بيانات الصفحة الرئيسية
     */
    public function getHomePageData(int $page): array
    {
        $lang = app()->getLocale();
        //$today = Carbon::today()->toDateString();
        $today = '2026-08-01'; // تاريخ تجريبي فيه بيانات
        //$nextWeek = Carbon::today()->addDays(7)->toDateString();
        $nextWeek = Carbon::parse($today)->addDays(7)->toDateString();

        return [
            'address'                 => $this->addressAction->execute($lang),
            'special_event'           => $this->specialEventAction->execute($today, $lang),
            'sectors'                 => $this->sectorsAction->execute($lang),
            'top_companies'           => $this->topCompaniesAction->execute($lang),
            'weekly_events'           => $this->weeklyEventsAction->execute($today, $nextWeek, $lang),

            // نمرر رقم الصفحة للـ Action الخاص بها
            'participating_companies' => $this->participatingCompaniesAction->execute($page),
        ];
    }
}
