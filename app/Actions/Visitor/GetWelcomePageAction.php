<?php

namespace App\Actions\Visitor;

use App\Http\Resources\EventResource;
use App\Models\ExhibitionProfile;
use App\Models\GlobalSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class GetWelcomePageAction
{
    public function execute()
    {
        return Cache::rememberForever("welcome_page", function () {
            $profile = ExhibitionProfile::first();

            if (!$profile) {
                return null;
            }
            return [
                'title' => $profile->title,
                'logo'  => $profile->syria_logo ? Storage::disk('s3')->url($profile->syria_logo) : null,
                'video' => $profile->welcome_video ? Storage::disk('s3')->url($profile->welcome_video) : null,
            ];
        });
    }
}
