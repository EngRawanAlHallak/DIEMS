<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\Company;

class GetParticipatingCompaniesAction
{
    // نمرر رقم الصفحة هنا
    public function execute(int $page)
    {
        /*return Company::query()
            ->where('is_active', true)
            ->with(['sector_relation:id,name', 'sector_relation.halls:id,name'])
            ->select('id', 'name', 'logo', 'nationality', 'sector_id')
            ->paginate(3, ['*'], 'page', $page);*/
        return Company::query()
            ->where('is_active', true)
            ->with([
                'sector_relation:id,name',
                'booths:id,company_id,hall_id',
                'booths.hall:id,name',
            ])
            ->select('id', 'name', 'logo', 'nationality', 'sector_id')
            ->paginate(3, ['*'], 'page', $page);
    }
}
