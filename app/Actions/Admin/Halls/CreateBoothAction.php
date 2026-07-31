<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Models\Booth;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CreateBoothAction extends BaseAction
{
    public function execute(array $data): Booth
    {
        return $this->executeAction(
            function () use ($data) {
                $hall = Hall::findOrFail($data['hall_id']);

                $existingBoothsArea = Booth::where('hall_id', $hall->id)->sum('size_sqm');
                $remainingSpace = $hall->total_area_sqm - $existingBoothsArea;

                if ($data['size_sqm'] > $remainingSpace) {
                    throw ValidationException::withMessages([
                        'size_sqm' => [
                            "لا توجد مساحة كافية في هذه القاعة. المساحة المتبقية المتاحة هي {$remainingSpace} متر مربع فقط."
                        ]
                    ]);
                }

                $booth = Booth::create($data);

                Cache::forget("admin:hall:{$data['hall_id']}");

                return $booth;
            },
            [
                'ar' => "تم إضافة جناح (Booth) جديد بالقاعة رقم (#{$data['hall_id']}) بنجاح",
                'en' => "New booth created in hall (#{$data['hall_id']}) successfully"
            ],
            [
                'hall_id'  => $data['hall_id'],
                'size_sqm' => $data['size_sqm'],
            ],
            true
        );
    }
}
