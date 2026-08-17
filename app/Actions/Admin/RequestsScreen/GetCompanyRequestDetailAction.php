<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Http\Resources\AdminCompanyRequestDetailResource;
use App\Models\CompanyRequest;
use Illuminate\Support\Facades\Cache;

class GetCompanyRequestDetailAction
{
    public function execute(int $id)
    {
        $cacheKey = "admin:company_request_detail:{$id}";
        //Cache::forget($cacheKey);
        return Cache::remember($cacheKey, now()->addHours(4),function () use ($id) {

            $companyRequest = CompanyRequest::query()
                ->with('documents')
                ->findOrFail($id);

            return AdminCompanyRequestDetailResource::make($companyRequest)->resolve();
        });
    }
}
