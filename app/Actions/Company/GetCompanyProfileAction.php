<?php

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class GetCompanyProfileAction
{
    public function execute(): Company
    {
        return Company::where('user_id', Auth::id())
            ->with([
                'sector_relation',
                'user:id,name,email,phonenumber'
            ])
            ->firstOrFail();
    }
}
