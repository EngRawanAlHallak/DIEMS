<?php
// app/Actions/Visitor/GetCompanyDetailAction.php
namespace App\Actions\Visitor;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetCompanyDetailAction
{
    public function execute(Company $company): Company
    {
        Cache::forget("company:detail:{$company->id}");
        return Cache::remember("company:detail:{$company->id}", now()->addMinutes(15),
                fn () => $this->loadRelations($company)
            );
    }
    private function loadRelations(Company $company): Company
    {
        // Step 1 — Load booths first (lightweight) to decide
        $company->load([
            'booths' => fn ($q) => $q
                ->select(['id', 'company_id', 'booth_number', 'booth_type', 'sector_id','hall_id'])
                ->with([
                    'sector:id,name',
                    'hall:id,name',
                ]),
        ]);

        $hasSalesBooth = $company->booths->contains('booth_type', 'sales');

        //Step 2 —Promotions (and their products) are only fetched when the company actually owns a sales booth
        $loads = [
            'sector_relation:id,name',

            'products' => fn ($q) => $q
                ->select(['id', 'company_id', 'name', 'description', 'price'])
                ->with([
                    'images' => fn ($q) => $q
                        ->select(['id', 'product_id', 'image_path', 'is_primary'])
                        ->orderByDesc('is_primary'),
                ]),
        ];

        if ($hasSalesBooth) {

            $loads['promotions'] = fn ($q) => $q
                ->where('is_active', true)
                ->select(['id', 'company_id', 'type', 'discount_percentage',
                    'total_package_price', 'start_date', 'end_date', 'is_active'])
                ->with([
                    'products' => fn ($q) => $q
                        ->select(['products.id', 'products.company_id',
                            'products.name', 'products.price'])
                        ->with([
                            'images' => fn ($q) => $q
                                ->select(['id', 'product_id', 'image_path', 'is_primary'])
                                ->where('is_primary', true)
                                ->limit(1),
                        ]),
                ]);
        }

        $company->load($loads);
        $company->setAttribute('has_sales_booth', $hasSalesBooth);

        return $company;
    }
}
