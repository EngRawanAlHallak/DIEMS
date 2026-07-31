<?php

namespace App\Actions\Admin\Halls;

use App\Http\Resources\CompanyRequestResource;
use App\Models\Booth;
use App\Models\CompanyRequest;
use Illuminate\Support\Facades\DB;

class AutoAssignBoothsAction
{
    public function execute(): array
    {
        $results = [
            'assigned_count' => 0,
            'failed_count'   => 0,
            'failed_requests'=> [] // سنخزن هنا كائنات الطلبات الفاشلة
        ];

        // 1. جلب الطلبات مع تحميل علاقة الشركة مسبقاً (Eager Loading)
        $approvedRequests = CompanyRequest::with('company')
            ->where('request_status', 'approved')
            ->where('payment_status', 'paid')
            ->doesntHave('booth')
            ->orderBy('requested_area', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($approvedRequests as $request) {
            DB::transaction(function () use ($request, &$results) {

                // 2. البحث عن بوث يطابق المساحة تماماً (=)
                $suitableBooth = Booth::query()
                    ->where('sector_id', $request->company?->sector_id)
                    ->where('equipment_type', $request->setup_preference)
                    ->where('available', true)
                    ->where('size_sqm', '=', (float) $request->requested_area)
                    ->lockForUpdate() // حماية من التضارب
                    ->first();

                if ($suitableBooth) {
                    $suitableBooth->update([
                        'available'          => false,
                        'company_request_id' => $request->id,
                        'company_id'         => $request->company?->id,
                    ]);

                    $results['assigned_count']++;
                } else {
                    // 4. تسجيل الطلب كفاشل في حال عدم وجود بوث مطابق
                    $results['failed_count']++;
                    $results['failed_requests'][] = $request;
                }
            });
        }

        //return $results;
        return [
            'summary' => [
                'successfully_assigned' => $results['assigned_count'],
                'failed_to_assign'      => $results['failed_count'],
            ],
            'unassigned_requests' => CompanyRequestResource::collection($results['failed_requests'])
        ];
    }
}
