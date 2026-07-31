<?php

namespace App\Actions\Admin\Sectors;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\Sector;
use Illuminate\Support\Facades\Cache;

class CreateSectorAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data): Sector
    {
        return $this->executeAction(
            function () use ($data) {
                $translatedName = $this->translator->execute($data['name']);

                $sector = Sector::create([
                    'name' => $translatedName,
                ]);

                Cache::forget("home:sectors:en");
                Cache::forget("home:sectors:ar");

                return $sector;
            },
            [
                'ar' => "تم إضافة قطاع جديد بنجاح",
                'en' => "New sector created successfully"
            ],
            [
                'name' => $data['name']
            ],
            true
        );
    }
}
