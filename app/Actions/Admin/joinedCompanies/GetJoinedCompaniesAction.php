<?php

namespace App\Actions\Admin\joinedCompanies;

use App\Http\Resources\JoinedCompanyResource;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class GetJoinedCompaniesAction
{
    public function execute(?string $search = null)
    {
        // إذا كان هناك بحث، لا نستخدم الكاش لضمان دقة النتائج اللحظية
        if ($search) {
            $companies = $this->queryCompanies($search)->get();
            return JoinedCompanyResource::collection($companies)->resolve();
        }

        // إذا لم يكن هناك بحث، نجلب البيانات من الكاش
        Cache::forget("admin:Joined_companies");
        return Cache::remember('admin:Joined_companies', now()->addDays(1), function () {
            $companies = $this->queryCompanies()->get();
            return JoinedCompanyResource::collection($companies)->resolve();
        });
    }

    private function queryCompanies(?string $search = null)
    {
        return Company::query()
            ->with('sector_relation:id,name') // Eager Loading
            ->when($search, function ($query, $search) {
                $query->where('name->en', 'ILIKE', "%{$search}%")
                    ->orWhere('name->ar', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at');
    }
}
