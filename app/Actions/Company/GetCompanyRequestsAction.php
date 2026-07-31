<?php

namespace App\Actions\Company;

use App\Http\Resources\CompanyPaymentDetailResource;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class GetCompanyRequestsAction
{
    public function execute(Company $company): array
    {
        // مفتاح كاش مخصص لهذه الشركة بالتحديد
        $cacheKey = "company:{$company->id}:requests_dashboard";
        Cache::forget($cacheKey);

        return Cache::tags(['company_requests', "company_{$company->id}_requests"])
            ->remember($cacheKey, now()->addHours(2), function () use ($company) {

                $requests = $company->requests()
                    ->with([
                        // جلب الدفعات الناجحة فقط لعرضها في السجل المالي
                        'payments' => function ($query) {
                            $query->where('status', 'paid')->orderBy('created_at', 'desc');
                        },
                        'booth'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                // إرجاع المصفوفة جاهزة ومحلولة لـ High Performance
                return CompanyPaymentDetailResource::collection($requests)->resolve();
            });
    }
}
