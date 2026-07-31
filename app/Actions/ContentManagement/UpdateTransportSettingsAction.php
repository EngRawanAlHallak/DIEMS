<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Models\ExhibitionProfile;

class UpdateTransportSettingsAction extends BaseAction
{
    public function execute(array $data): ExhibitionProfile
    {
        return $this->executeAction(
            function () use ($data) {
                $profile = ExhibitionProfile::firstOrCreate([]);

                $profile->update([
                    'transport_interval_minutes' => $data['transport_interval_minutes'] ?? $profile->transport_interval_minutes,
                    'transport_start_time'       => $data['transport_start_time'] ?? $profile->transport_start_time,
                    'transport_end_time'         => $data['transport_end_time'] ?? $profile->transport_end_time,
                ]);

                return $profile;
            },
            [
                'ar' => 'تم تحديث إعدادات أوقات المواصلات بنجاح',
                'en' => 'Transport time settings updated successfully'
            ],
            [
                'interval'   => $data['transport_interval_minutes'] ?? null,
                'start_time' => $data['transport_start_time'] ?? null,
                'end_time'   => $data['transport_end_time'] ?? null,
            ],
            true
        );
    }
}
