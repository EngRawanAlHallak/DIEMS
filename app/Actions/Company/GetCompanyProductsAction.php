<?php
namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class GetCompanyProductsAction
{
    public function execute(Company $company): Collection
    {
        return $company->products()
            ->with('images')//for eager loading
            ->latest()
            ->get();
    }
}
