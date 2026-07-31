<?php
namespace App\Actions\General;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class TranslateTextAction
{
    public function execute(?string $text): array
    {
        if (empty($text)) {
            return ['ar' => '', 'en' => ''];
        }


        $currentLocale = App::getLocale();
        $targetLocale = ($currentLocale === 'ar') ? 'en' : 'ar';

        try {
            $tr = new GoogleTranslate($targetLocale);
            $translation = $tr->translate($text);

            return [
                $currentLocale => $text,
                $targetLocale  => $translation,
            ];
        } catch (\Exception $e) {
            //هلق هون اذا ما زبط تبع قوقل لح خزن باللغتين مشان ما يوقف البرنامج ولح حط تحذير مشان اذا بدنا نحطو للادمن
            Log::warning("Translation failed (Network/Service). Falling back to original text for both languages.");
            return [
                'ar' => $text,
                'en' => $text,
            ];
        }
    }
}
