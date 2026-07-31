<?php

namespace App\Actions\Admin\joinedCompanies;

use App\Actions\General\BaseAction;
use App\Models\Company;
use App\Models\Booth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AssignBoothToCompanyAction extends BaseAction
{
    public function execute(int $boothId, int $companyId, int $requestId): Company
    {
        return $this->executeAction(
            function () use ($boothId, $companyId, $requestId) {
                $booth = Booth::findOrFail($boothId);
                $company = Company::findOrFail($companyId);

                // حماية النظام: التأكد أن البوث متاح
                if (!$booth->available && $booth->company_id !== null && $booth->company_request_id !== null) {
                    throw ValidationException::withMessages([
                        'booth_id' => ['عذراً، هذا الجناح (Booth) محجوز لشركة أخرى بالفعل.']
                    ]);
                }

                // 1. ربط البوث بالشركة وإخراجه من التوفر
                $booth->update([
                    'company_id'         => $company->id,
                    'company_request_id' => $requestId,
                    'available'          => false
                ]);

                // 2. معالجة وتحديث المساحة النهائية للشركة
                $currentArea = (float) ($company->final_area ?? 0);
                $boothSize   = (float) ($booth->size_sqm ?? 0);

                $company->update([
                    'final_area' => $currentArea + $boothSize
                ]);

                // مسح الكاش
                Cache::forget("company:detail:{$companyId}");
                Cache::forget("admin:company_details:{$companyId}");

                return $company;
            },
            [
                'ar' => "تم إسناد الجناح رقم (#{$boothId}) للشركة رقم (#{$companyId}) بنجاح",
                'en' => "Booth (#{$boothId}) assigned to company (#{$companyId}) successfully"
            ],
            [
                'booth_id'    => $boothId,
                'company_id'  => $companyId,
                'request_id'  => $requestId,
            ],
            true
        );
    }
}
