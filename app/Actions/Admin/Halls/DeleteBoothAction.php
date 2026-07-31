<?php

namespace App\Actions\Admin\Halls;

use App\Actions\General\BaseAction;
use App\Models\Booth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteBoothAction extends BaseAction
{
    public function execute(int $id): Booth
    {
        return $this->executeAction(
            function () use ($id) {
                $booth = Booth::findOrFail($id);

                if ($booth->company_id !== null) {
                    throw ValidationException::withMessages([
                        'booth' => ["لا يمكن حذف الجناح لأنه معين لشركة حالياً. يرجى إلغاء تعيين الشركة أولاً."]
                    ]);
                }

                $deletedBooth = $booth;

                Cache::forget("admin:hall:{$booth->hall_id}");
                $booth->delete();

                return $deletedBooth;
            },
            [
                'ar' => "تم حذف الجناح رقم (#{$id}) بنجاح",
                'en' => "Booth (#{$id}) deleted successfully"
            ],
            [
                'booth_id' => $id
            ],
            true
        );
    }
}
