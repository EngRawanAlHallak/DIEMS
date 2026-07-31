<?php

namespace App\Actions\Visitor;

use App\Http\Resources\EventResource;
use App\Http\Resources\ExhibitionProfileResource;
use App\Models\ExhibitionProfile;
use App\Models\GlobalSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class GetProfileAction
{
    public function execute()
    {
        $lang = app()->getLocale();

        Cache::forget("exhibition_profile_{$lang}");
        return Cache::rememberForever("exhibition_profile_{$lang}", function () {
            $profile = ExhibitionProfile::first();

            if (!$profile) {
                return null;
            }
            return (new ExhibitionProfileResource($profile))->resolve();
        });
    }
}
