<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class GetCompanyPromotionsAction
{
    /**
     * جلب كافة العروض الخاصة بشركة محددة
     */
    public function execute(Company $company): Collection
    {
        return $company->promotions()
            ->with('products')//عم جيب المنتجات هون مشان eager loading لانو بدي اياهم مع العروض
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
