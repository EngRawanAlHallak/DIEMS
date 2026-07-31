<?php
// app/Actions/Visitor/GetCompaniesSearchBar.php
namespace App\Actions\Visitor;

use App\Http\Resources\BoothResource;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetCompaniesSearchBar
{
    /**
     * يدعم حالات:
     *  1. sector فقط          → cached
     *  2. search فقط          → no cache (dynamic, global search)
     */
    public function execute(?int $sectorId = null, ?string $search = null): Collection
    {
        $lang = app()->getLocale();
        // حالة البحث — دائماً بدون cache
        if ($search) {
            $companies = $this->fetchCompanies($sectorId, $search);
        }
        elseif ($sectorId) {
            Cache::forget("companies:by_sector:{$sectorId}");
            $companies = Cache::remember("companies:by_sector:{$sectorId}", now()->addMinutes(30),
                fn() => $this->fetchCompanies($sectorId)
            );
        } else {
            return collect();
        }

        return $companies->map(function ($company) use ($lang) {
            $sector = $company->sector_relation ?? null;

            return [
                'id'    => $company->id,
                'name'  => $company->getTranslation('name', $lang),
                'logo'  => $company->logo ? Storage::disk('s3')->url($company->logo) : null,
                'sector_name' => $sector->getTranslation('name', $lang),
                'sector_id' => $sector->id,
                'booths' => BoothResource::collection($company->booths)->resolve()
            ];
        });
    }

    private function fetchCompanies(?int $sectorId, ?string $search = null): Collection
    {
        return Company::query()
            ->active()
            ->when($sectorId, fn($q) => $q->where('sector_id', $sectorId))
            ->when($search,   fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->with([
                'sector_relation:id,name',
                'booths:id,company_id,booth_number,booth_type,sector_id,hall_id',
                'booths.sector:id,name',
                'booths.hall:id,name',
            ])
            ->select([
                'id', 'name', 'logo', 'sector_id'
            ])
            ->orderBy('name')
            ->get();
    }
}
