<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\ExhibitionProfile;
use Illuminate\Support\Facades\Cache;

class GetExhibitionAddressAction
{
    public function execute(string $lang)
    {
        Cache::forget("home:address:{$lang}");
        return Cache::remember("home:address:{$lang}", now()->addDays(1), function ()use ($lang) {
            $profile = ExhibitionProfile::select('address')->latest()->first();
            return $profile ?->getTranslation('address', $lang);
        });
    }
}
