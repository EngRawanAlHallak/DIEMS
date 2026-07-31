<?php

namespace App\Actions\Admin\Halls;

use App\Http\Resources\HallResource;
use App\Models\Hall;
use App\Models\Sector;
use Illuminate\Support\Facades\Cache;

class GetHallsAction
{
    public function execute()
    {
        Cache::forget("admin:halls");
        return Cache::remember("admin:halls", now()->addDays(1), function () {

            $halls = Hall::all();
            $sectors = Sector::all();
            return HallResource::collection($halls)->resolve();
        });
    }
}
