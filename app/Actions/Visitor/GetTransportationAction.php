<?php

namespace App\Actions\Visitor;

use App\Http\Resources\TransportationResource;
use App\Models\ExhibitionProfile;
use App\Models\Transportation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetTransportationAction
{

    public function execute()
    {
        $lang = app()->getLocale();

        //Cache::forget("transportation_page_{$lang}");
        return Cache::rememberForever("transportation_page_{$lang}", function () use ($lang) {
            $profile = ExhibitionProfile::first();

            if (!$profile) return null;

            $lines = Transportation::all();

            return [
                'interval_minutes' => $profile->transport_interval_minutes,
                'open_time'        => Carbon::parse($profile->transport_start_time)->format('g A'),
                'close_time'       => Carbon::parse($profile->transport_end_time)->format('g A'),
                'lines'            => TransportationResource::collection($lines)->resolve(),
            ];
        });
    }
}
