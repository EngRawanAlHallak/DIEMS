<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class GetTopCompaniesAction
{
    public function execute(string $lang)
    {
        return Cache::remember("home:top_companies:{$lang}", now()->addHour(5), function () {
            return Company::where('is_active', true)
                ->inRandomOrder()
                ->take(10)
                ->select('id', 'name', 'logo')
                ->get();
        });
    }
}
