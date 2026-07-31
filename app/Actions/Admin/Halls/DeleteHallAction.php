<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class DeleteHallAction extends BaseAction
{
    public function execute(int $id): Hall
    {
        return $this->executeAction(
            function () use ($id) {
                $hall = Hall::findOrFail($id);

                $deletedHall = $hall;
                $hall->delete();

                Cache::forget("admin:halls");

                return $deletedHall;
            },
            [
                'ar' => "تم حذف القاعة رقم (#{$id}) بنجاح",
                'en' => "Hall (#{$id}) deleted successfully"
            ],
            [
                'hall_id' => $id
            ],
            true
        );
    }
}
