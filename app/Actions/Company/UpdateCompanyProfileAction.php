<?php

namespace App\Actions\Company;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UpdateCompanyProfileAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translateAction
    ) {}

    public function execute(Company $company, array $userData, array $companyData, $logo = null): Company
    {
        if (isset($companyData['name'])) {
            $companyData['name'] = $this->translateAction->execute($companyData['name']);
        }
        if (isset($companyData['company_description'])) {
            $companyData['company_description'] = $this->translateAction->execute($companyData['company_description']);
        }
        if (isset($companyData['address'])) {
            $companyData['address'] = $this->translateAction->execute($companyData['address']);
        }

        return $this->executeAction(function () use ($company, $userData, $companyData, $logo) {
            $company->user->update($userData);

            $userChanges = [];
            if ($company->user->wasChanged()) {
                $userChanges = [
                    'attributes' => $company->user->getChanges(),
                    'old' => array_intersect_key($company->user->getOriginal(), $company->user->getChanges())
                ];
            }
            if ($logo) {
                if ($company->logo) {
                    Storage::disk('s3')->delete($company->logo);
                }
                $companyData['logo'] = $logo->store('companies/logos', 's3');
            }
            $company->update($companyData);
            $properties = !empty($userChanges) ? ['user_updates' => $userChanges] : [];
            $properties['event_type'] = 'profile_updated';

            Cache::forget("company:detail:{$company->id}");

            return $company->fresh(['user']);

        }, [
            'ar' => "تم تحديث بيانات الملف الشخصي للشركة بنجاح",
            'en' => "Company profile information has been updated successfully"
        ], $properties ?? ['event_type' => 'profile_updated'], true); // تفعيل الـ Log بوضع true
    }
}
