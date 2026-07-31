<?php

namespace App\Actions\Company;

use App\Models\Company;
use Spatie\Activitylog\Models\Activity;

class GetCompanyDashboardStatsAction
{
    public function execute(Company $company): array
    {
        $locale = app()->getLocale();

        $recentActivities = Activity::where('causer_id', auth()->id())
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($activity) use ($locale) {
                $descriptionArray = json_decode($activity->description, true);
                $description = is_array($descriptionArray)
                    ? ($descriptionArray[$locale] ?? $descriptionArray['ar'])
                    : $activity->description;

                return [
                    'description' => $description,
                    'time' => $activity->created_at->diffForHumans(),
                ];
            });

        return [
            'products_count'    => $company->products()->count(),
            'promotions_count'  => $company->promotions()->count(),
            'status'            => $locale === 'ar' ? 'مقبول' : 'Approved',
            'recent_activities' => $recentActivities
        ];
    }
}
