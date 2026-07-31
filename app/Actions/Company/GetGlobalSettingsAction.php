<?php

namespace App\Actions\Company;

use App\Models\Globalsetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetGlobalSettingsAction
{
    public function execute()
    {
        $lang = request()->header('Accept-Language', 'ar');
        app()->setLocale($lang);
        return Cache::rememberForever("global_settings_{$lang}", function () {
            return Globalsetting::all()->groupBy('group')->map(function ($items) {
                return $items->keyBy('key')->map(function ($setting) {
                    return $this->formatValue($setting);
                });
            });
        });
    }

    private function formatValue($setting)
    {
        $translatedValue = $setting->value;

        if (in_array($setting->type, ['image', 'video'])) {
            return $translatedValue ? Storage::disk('s3')->url($translatedValue) : null;
        }

        if ($setting->type === 'json') {
            return is_array($translatedValue) ? $translatedValue : json_decode($translatedValue, true);
        }

        return $translatedValue;
    }
}
