<?php

namespace App\Actions\Admin\joinedCompanies;

use App\Http\Resources\BoothDetailsResource;
use App\Models\Company;
use App\Models\Booth;
use App\Models\CompanyRequest;

class GetSuitableBoothsAction
{
    public function execute(CompanyRequest $companyRequest)
    {
        $sectorId = $companyRequest->company?->sector_id;
        $suitableBooths = Booth::query()
            ->with('hall:id,name')
            ->where('sector_id', $sectorId)
            ->where('available', true)
            ->where('equipment_type', $companyRequest->setup_preference)
            ->where('size_sqm', '>=', (float)$companyRequest->requested_area)
            ->orderBy('size_sqm', 'asc')
            ->get();

        return BoothDetailsResource::collection($suitableBooths)->resolve();
    }
}
