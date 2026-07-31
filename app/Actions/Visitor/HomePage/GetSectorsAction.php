<?php

namespace App\Actions\Visitor\HomePage;

use App\Models\Sector;
use Illuminate\Support\Facades\Cache;

class GetSectorsAction
{
    public function execute(string $lang)
    {
        //Cache::forget("home:sectors:{$lang}");
        return /*Cache::tags(['sectors'])->*/Cache::remember("home:sectors:{$lang}", now()->addDays(1), function () {
            return Sector::select('id', 'name')->get();
        });
    }

}
