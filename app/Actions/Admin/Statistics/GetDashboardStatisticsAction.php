<?php

namespace App\Actions\Admin\Statistics;

use App\Models\Company;
use App\Models\EventRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetDashboardStatisticsAction
{
    public function execute(): array
    {
        return Cache::remember('admin:dashboard:super_stats', now()->addMinutes(30), function () {

            $ticketStats = DB::table('mv_tickets_analytics')->first() ?? (object)[
                'total_tickets' => 0,
                'avg_hourly_rate' => 0,
                'peak_hour' => 9,
                'weekly_distribution' => '[]'
            ];

            $dailyEventsCount = EventRequest::where('request_status', 'approved')
                ->whereHas('slot', function ($query) {
                    $query->whereDate('slot_date', today());
                })->count();

            $requestStatusCounts = DB::table('company_requests')
                ->select('request_status', DB::raw('count(*) as total'))
                ->groupBy('request_status')
                ->pluck('total', 'request_status')
                ->toArray();

            $approvedRequests = $requestStatusCounts['approved'] ?? 0;
            $rejectedRequests = $requestStatusCounts['rejected'] ?? 0;
            $totalStatusCount = $approvedRequests + $rejectedRequests;

            $acceptedPercentage = $totalStatusCount > 0 ? round(($approvedRequests / $totalStatusCount) * 100, 2) : 0;
            $rejectedPercentage = $totalStatusCount > 0 ? round(($rejectedRequests / $totalStatusCount) * 100, 2) : 0;

            $localCompanies = DB::table('company_requests')
                ->where('foreign_local', 'local')
                ->where('request_status', 'approved')
                ->count();

            $foreignCompanies = DB::table('company_requests')
                ->where('foreign_local', 'foreign')
                ->where('request_status', 'approved')
                ->count();

            $sectorsStats = Company::join('sectors', 'companies.sector_id', '=', 'sectors.id')
                ->select('sectors.name', DB::raw('count(companies.id) as total'))
                ->groupBy('sectors.name')
                ->get()
                ->map(fn($item) => ['sector' => $item->name, 'count' => $item->total]);

            $mostActiveCompanies = Company::select('id', 'name')
                ->withCount(['products', 'promotions'])
                ->orderByRaw('
                    (SELECT count(*) FROM products WHERE products.company_id = companies.id) +
                    (SELECT count(*) FROM promotions WHERE promotions.company_id = companies.id)
                    DESC
                ')
                ->take(4)
                ->get()
                ->map(fn($company) => [
                    'name' => $company->name,
                    'activity_score' => $company->products_count + $company->promotions_count
                ]);

            $hourlyChartData = DB::table('tickets')
                ->select(DB::raw("EXTRACT(HOUR FROM used_at) as hour"), DB::raw("count(*) as total"))
                ->where('status', 'used')
                ->whereNotNull('used_at')
                ->groupBy('hour')
                ->orderBy('total', 'desc') // ترتيب تنازلي أولاً لمعرفة الأعلى
                ->get();

            $calculatedPeakHour = $hourlyChartData->first()->hour ?? 9;

            $finalHourlyChart = $hourlyChartData->sortBy('hour')->values()->toArray();
            $daysMapping = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            $rawWeeklyData = json_decode($ticketStats->weekly_distribution ?? '[]', true) ?? [];

            $weeklyChartTemplate = [];
            foreach ($daysMapping as $dayName) {
                $weeklyChartTemplate[$dayName] = 0;
            }

            foreach ($rawWeeklyData as $data) {
                $dayName = $daysMapping[(int)$data['day_index']] ?? 'Sun';
                $weeklyChartTemplate[$dayName] = (int)$data['tickets_count'];
            }

            $orderedDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $finalWeeklyChart = [];
            foreach ($orderedDays as $day) {
                $finalWeeklyChart[] = [
                    'day' => $day,
                    'tickets_booked' => $weeklyChartTemplate[$day]
                ];
            }

            return [
                'booking_tickets_number' => $ticketStats->total_tickets,
                'average_hourly_entry_rate' => round($ticketStats->avg_hourly_rate, 2),
                'daily_event_number' => $dailyEventsCount,

                'peak_hours_data' => [
                    'peak_hour_at' => $calculatedPeakHour . ":00",
                    'chart' => $finalHourlyChart
                ],

                'most_busy_days_chart' => $finalWeeklyChart,
                'company_acceptance_rate' => ['accepted' => $acceptedPercentage, 'rejected' => $rejectedPercentage],
                'companies_sectors' => $sectorsStats,
                'most_active_companies' => $mostActiveCompanies,
                'companies_type_count' => ['local' => $localCompanies, 'foreign' => $foreignCompanies]
            ];
        });
    }
}
