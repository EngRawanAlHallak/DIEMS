<?php

namespace App\Actions\Admin\Sectors;

use App\Actions\General\BaseAction;
use App\Models\Sector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteSectorAction extends BaseAction
{
    public function execute(int $id): Sector
    {
        return $this->executeAction(
            function () use ($id) {
                $sector = Sector::findOrFail($id);

                // التحقق من وجود أي داتا مرتبطة بالقطاع (الشركات، الفعاليات، الـ Booths)
                if ($sector->companies()->exists() || $sector->eventRequests()->exists() || $sector->booths()->exists()) {
                    throw ValidationException::withMessages([
                        'sector' => ['لا يمكن حذف هذا القطاع لارتباطه ببيانات نشطة (شركات أو فعاليات أو بوثات).']
                    ]);
                }

                $deletedSector = $sector;
                $sector->delete();

                Cache::forget("home:sectors:en");
                Cache::forget("home:sectors:ar");

                return $deletedSector;
            },
            [
                'ar' => "تم حذف القطاع رقم (#{$id}) بنجاح",
                'en' => "Sector (#{$id}) deleted successfully"
            ],
            [
                'sector_id' => $id
            ],
            true
        );
    }
}
