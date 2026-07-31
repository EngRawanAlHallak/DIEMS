<?php

namespace App\Actions\ContentManagement;

use App\Actions\General\BaseAction;
use App\Models\ExhibitionProfile;
use App\Jobs\Company\ProcessMediaUploadJob;
use Illuminate\Http\UploadedFile;

class UpdateVisitorAppContentAction extends BaseAction
{
    public function execute(array $data): ExhibitionProfile
    {
        return $this->executeAction(
            function () use ($data) {
                $profile = ExhibitionProfile::firstOrCreate([]);

                $syriaLogo = $data['syria_logo'] ?? null;
                $welcomeVideo = $data['welcome_video'] ?? null;
                unset($data['syria_logo'], $data['welcome_video']);

                $profile->update($data);

                if ($syriaLogo instanceof UploadedFile && $syriaLogo->isValid()) {
                    $tempPath = $syriaLogo->store('temp', 'local');
                    dispatch(new ProcessMediaUploadJob(
                        $profile,
                        'syria_logo',
                        $tempPath,
                        'visitor_app/logos',
                        $profile->syria_logo
                    ));
                }

                if ($welcomeVideo instanceof UploadedFile && $welcomeVideo->isValid()) {
                    $tempPath = $welcomeVideo->store('temp', 'local');
                    dispatch(new ProcessMediaUploadJob(
                        $profile,
                        'welcome_video',
                        $tempPath,
                        'visitor_app/videos',
                        $profile->welcome_video
                    ));
                }

                return $profile;
            },
            [
                'ar' => 'تم تحديث محتوى تطبيق الزائر بنجاح',
                'en' => 'Visitor app content updated successfully'
            ],
            [
                'updated_fields' => array_keys($data)
            ],
            true
        );
    }
}
