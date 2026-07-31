<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Models\Hall;
use App\Models\Booth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DetachSectorFromHallAction extends BaseAction
{
    public function execute(int $hallId, int $sectorId): Hall
    {
        return $this->executeAction(
            function () use ($hallId, $sectorId) {
                $isAttached = DB::table('hall_sector')
                    ->where('hall_id', $hallId)
                    ->where('sector_id', $sectorId)
                    ->exists();

                if (!$isAttached) {
                    throw ValidationException::withMessages([
                        'sector_id' => ['هذا القطاع غير مرتبط بهذه القاعة.']
                    ]);
                }

                $hasBoothsInThisHall = Booth::where('hall_id', $hallId)
                    ->where('sector_id', $sectorId)
                    ->exists();

                if ($hasBoothsInThisHall) {
                    throw ValidationException::withMessages([
                        'sector_id' => ['لا يمكن إزالة القطاع لأنه يحتوي على أجنحة (Booths) مرتبطة به داخل هذه القاعة.']
                    ]);
                }

                $hall = Hall::findOrFail($hallId);
                $hall->sectors()->detach($sectorId);

                Cache::forget("admin:hall:{$hallId}");

                return $hall;
            },
            [
                'ar' => "تم فصل القطاع رقم (#{$sectorId}) عن القاعة رقم (#{$hallId}) بنجاح",
                'en' => "Sector (#{$sectorId}) detached from hall (#{$hallId}) successfully"
            ],
            [
                'hall_id'   => $hallId,
                'sector_id' => $sectorId
            ],
            true
        );
    }
}
