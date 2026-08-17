<?php

namespace App\Actions\Admin\Events;

use App\Actions\General\BaseAction;
use App\Models\EventRequest;
use App\Jobs\Event\SendEventStatusEmailJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class RejectEventRequestAction extends BaseAction
{
    public function execute(int $requestId): EventRequest
    {
        return $this->executeAction(
            function () use ($requestId) {
                $eventRequest = EventRequest::findOrFail($requestId);

                if ($eventRequest->request_status !== 'pending') {
                    throw ValidationException::withMessages([
                        'request_status' => ['يمكن رفض الطلبات التي بحالة معلقة فقط.']
                    ]);
                }

                $eventRequest->update(['request_status' => 'rejected']);

                // إرسال إيميل الرفض
                dispatch(new SendEventStatusEmailJob($eventRequest, 'rejected'));

                Cache::forget("admin:event_request_detail:{$requestId}");
                Cache::forget("admin:dashboard:super_stats");
                return $eventRequest;
            },
            [
                'ar' => "تم رفض طلب الفعالية رقم (#{$requestId}) بنجاح",
                'en' => "Event request (#{$requestId}) rejected successfully"
            ],
            [
                'request_id' => $requestId
            ],
            true
        );
    }
}
