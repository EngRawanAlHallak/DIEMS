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
        //Cache::forget($cacheKey);

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($id) {

            $company = Company::query()
                ->with([
                    'sector_relation:id,name',
                    'requests',
                    'documents',
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
