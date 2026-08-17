<?php

namespace App\Actions\Admin\joinedCompanies;

use App\Actions\General\BaseAction;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class ActiveCompanyStatusAction extends BaseAction
{
    public function execute(Company $company): Company
    {
        return $this->executeAction(
            function () use ($company) {
                $company->is_active = !$company->is_active;
                $company->save();

                if (!$company->is_active && $company->user) {
                    // سحب صلاحيات الدخول: حذف جميع الـ Tokens لطرده فوراً
                    $company->user->tokens()->delete();
                }

                Cache::forget("admin:Joined_companies");
                Cache::forget("admin:company_details:{$company->id}");
                Cache::forget("company:detail:{$company->id}");

                return $company;
            },
            [
                'ar' => $company->is_active
                    ? "تم تفعيل حساب الشركة ({$company->name}) بنجاح"
                    : "تم إلغاء تفعيل حساب الشركة ({$company->name}) وإلغاء جلسات الدخول",
                'en' => $company->is_active
                    ? "Company ({$company->name}) activated successfully"
                    : "Company ({$company->name}) deactivated and sessions revoked"
            ],
            [
                'company_id' => $company->id,
                'is_active'  => $company->is_active,
            ],
            true
        );
    }
}
