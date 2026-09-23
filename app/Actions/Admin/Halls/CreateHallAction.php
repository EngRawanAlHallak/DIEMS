<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class CreateHallAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data): Hall
    {
        return $this->executeAction(
            function () use ($data) {
                $translatedName = $this->translator->execute($data['name']);

                $data['name'] = $translatedName;

                $hall = Hall::create($data);

                Cache::forget("admin:halls");

                return $hall;
            },
            [
                'ar' => "تم إنشاء قاعة جديدة بنجاح",
                'en' => "New hall created successfully"
            ],
            [
                'total_area' => $data['total_area_sqm'] ?? null
            ],
            true
        );
    }
}
