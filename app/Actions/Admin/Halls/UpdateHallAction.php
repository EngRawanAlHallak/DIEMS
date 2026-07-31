<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class UpdateHallAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data): Hall
    {
        return $this->executeAction(
            function () use ($data) {
                $hall = Hall::findOrFail($data['id']);

                if (isset($data['name'])) {
                    $data['name'] = $this->translator->execute($data['name']);
                }

                $hall->update($data);

                Cache::forget("admin:halls");
                Cache::forget("admin:hall:{$data['id']}");

                return $hall;
            },
            [
                'ar' => "تم تحديث بيانات القاعة رقم (#{$data['id']}) بنجاح",
                'en' => "Hall (#{$data['id']}) updated successfully"
            ],
            [
                'hall_id' => $data['id']
            ],
            true
        );
    }
}
