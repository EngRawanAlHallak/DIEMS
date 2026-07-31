<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\ExhibitionProfile;
use App\Models\Globalsetting;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UpdateExhibitionProfileAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data): ExhibitionProfile
    {
        return $this->executeAction(
            function () use ($data) {
                $profile = ExhibitionProfile::firstOrCreate([]);

                if (isset($data['name'])) {
                    $data['name'] = is_array($data['name']) ? $data['name'] : $this->translator->execute($data['name']);
                }

                if (isset($data['address'])) {
                    $data['address'] = is_array($data['address']) ? $data['address'] : $this->translator->execute($data['address']);
                }

                if (isset($data['bio'])) {
                    $data['bio'] = is_array($data['bio']) ? $data['bio'] : $this->translator->execute($data['bio']);
                }

                $profile->fill($data)->save();

                $this->syncWithCompanySettings($profile);

                return $profile;
            },
            [
                'ar' => 'تم تحديث بيانات بروفايل المعرض وتأكيد المزامنة مع بيانات الشركة بنجاح',
                'en' => 'Exhibition profile updated and synced with company settings successfully'
            ],
            [
                'profile_id' => 1
            ],
            true
        );
    }

    /**
     * مزامنة العنوان وتاريخ المعرض مع جدول الشركة وتحديث الكاش
     */
    protected function syncWithCompanySettings(ExhibitionProfile $profile): void
    {
        if ($profile->address) {
            $locationSetting = Globalsetting::where('key', 'event_location')->first();
            if ($locationSetting) {
                $locationSetting->setTranslations('value', $profile->getTranslations('address'));
                $locationSetting->save();
            }
        }

        if ($profile->start_date && $profile->end_date) {
            $startDate = Carbon::parse($profile->start_date);
            $endDate   = Carbon::parse($profile->end_date);

            $eventDateAr = sprintf(
                '%s %s - %s %s %s',
                $startDate->translatedFormat('j'),
                $startDate->translatedFormat('F'),
                $endDate->translatedFormat('j'),
                $endDate->translatedFormat('F'),
                $endDate->format('Y')
            );

            $eventDateEn = trim(str_replace('  ', ' ', sprintf(
                '%s - %s, %s',
                $startDate->locale('en')->isoFormat('MMMM D'),
                $endDate->locale('en')->isoFormat('MMMM D'),
                $endDate->format('Y')
            )));

            $dateSetting = Globalsetting::where('key', 'event_date')->first();
            if ($dateSetting) {
                $dateSetting->setTranslations('value', [
                    'ar' => $eventDateAr,
                    'en' => $eventDateEn,
                ]);
                $dateSetting->save();
            }
        }

        Cache::forget('global_settings_ar');
        Cache::forget('global_settings_en');
    }
}
