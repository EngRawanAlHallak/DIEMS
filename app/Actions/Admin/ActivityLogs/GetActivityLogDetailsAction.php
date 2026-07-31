<?php

namespace App\Actions\Admin\ActivityLogs;

use App\Actions\General\BaseAction;
use Spatie\Activitylog\Models\Activity;

class GetActivityLogDetailsAction extends BaseAction
{
    /**
     * جلب تفاصيل سجل نشاط معين بواسطة الـ ID
     */
    public function execute(int|string $id): ?Activity
    {
        return Activity::with(['causer', 'subject'])->find($id);
    }
}
