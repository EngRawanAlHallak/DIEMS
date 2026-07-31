<?php

namespace App\Actions\Admin\joinedCompanies;

use App\Models\Company;
use App\Http\Resources\JoinedCompanyDetailResource;
use Illuminate\Support\Facades\Cache;

class GetJoinedCompanyDetailsAction
{
    public function execute(int $id): array
    {
        $cacheKey = "admin:company_details:{$id}";
         Cache::forget($cacheKey);

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($id) {

            $company = Company::query()
                // 1. تحديد حقول الشركة الأساسية لتقليل استهلاك الرام
                //->select('id', 'name', 'logo', 'responsible_person', 'is_active', 'sector_id','bio','nationality','address')
                ->with([
                    'sector_relation:id,name',
                    'requests',
                    'documents',/* => function ($query) {
                        $query->select('id', 'company_id', 'requested_area', 'setup_preference', 'request_status', 'payment_status', 'total_price', 'required_deposit', 'paid_amount', 'payment_due_date');
                    },*/
                    'requests.booth' => function ($query) {
                        $query->select('id', 'company_request_id', 'hall_id', 'sector_id', 'booth_number', 'booth_type', 'size_sqm')
                            ->with([
                                'hall:id,name',
                                'sector:id,name'
                            ]);
                    }
                ])
                ->findOrFail($id);

            return JoinedCompanyDetailResource::make($company)->resolve();
        });
    }
}
