<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Actions\Company\UploadMediaToS3Action;
use App\Models\Globalsetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;

class UpdateCompanyPagesAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator,
        protected UploadMediaToS3Action $uploader
    ) {}

    public function execute(array $settings): array
    {
        $firstUpdatedSetting = null;
        $updatedKeys = [];

        $result = $this->executeAction(
            function () use ($settings, &$firstUpdatedSetting, &$updatedKeys) {
                foreach ($settings as $key => $val) {
                    if ($val === null) {
                        continue;
                    }

                    $setting = Globalsetting::where('key', $key)->first();
                    if (!$setting) {
                        continue;
                    }

                    if (!$firstUpdatedSetting) {
                        $firstUpdatedSetting = $setting;
                    }
                    if ($val instanceof UploadedFile && $val->isValid()) {
                        $oldValues = $setting->getTranslations('value');
                        $oldPath = $oldValues['ar'] ?? null;

                        $s3Path = $this->uploader->execute($val, 'main_pages', $oldPath);

                        $setting->setTranslations('value', [
                            'ar' => $s3Path,
                            'en' => $s3Path
                        ]);
                    }
                    elseif (is_string($val)) {
                        if ($key === 'experience_video_url' || filter_var($val, FILTER_VALIDATE_URL)) {
                            $setting->setTranslations('value', [
                                'ar' => $val,
                                'en' => $val
                            ]);
                        } else {
                            $translated = $this->translator->execute($val);
                            $setting->setTranslations('value', $translated);
                        }
                    }
                    elseif (is_array($val)) {
                        if (isset($val['ar']) || isset($val['en'])) {
                            $setting->setTranslations('value', $val);
                        } else {
                            $translatedAr = $val;
                            $translatedEn = $this->translateArrayRecursively($val);

                            $setting->setTranslations('value', [
                                'ar' => $translatedAr,
                                'en' => $translatedEn
                            ]);
                        }
                    }

                    $setting->save();
                    $updatedKeys[] = $key;
                }

                Cache::forget('global_settings_ar');
                Cache::forget('global_settings_en');

                return $firstUpdatedSetting;
            },
            [
                'ar' => 'تم تحديث محتوى الواجهة الرئيسية وإعدادات الشركة بنجاح',
                'en' => 'Global landing and company content updated successfully'
            ],
            [
                'updated_keys' => array_keys($settings)
            ],
            true
        );
        return [
            'updated_keys' => $updatedKeys
        ];
    }

    /**
     * دالة مساعدة لترجمة جميع النصوص داخل المصفوفات المتداخلة تلقائياً
     */
    private function translateArrayRecursively(array $array): array
    {
        $translated = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $translated[$key] = $this->translateArrayRecursively($value);
            } elseif (is_string($value) && !empty($value)) {
                if (in_array($key, ['icon', 'id', 'url', 'link', 'type']) || filter_var($value, FILTER_VALIDATE_URL) || is_numeric($value)) {
                    $translated[$key] = $value;
                } else {
                    $translated[$key] = $this->translator->execute($value)['en'] ?? $value;
                }
            } else {
                $translated[$key] = $value;
            }
        }

        return $translated;
    }
}
