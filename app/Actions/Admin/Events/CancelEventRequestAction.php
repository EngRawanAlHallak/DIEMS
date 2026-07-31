<?php

namespace App\Actions\Admin\Events;

use App\Actions\General\BaseAction;
use App\Models\EventRequest;
use App\Models\EventSlot;
use App\Events\SlotAvailabilityChanged;
use App\Jobs\Event\SendEventStatusEmailJob;
use Illuminate\Validation\ValidationException;

class CancelEventRequestAction extends BaseAction
{
    public function execute(int $requestId)
    {
        return $this->executeAction(
            function () use ($requestId) {
                $eventRequest = EventRequest::lockForUpdate()->findOrFail($requestId);

                if ($eventRequest->request_status === 'approved') {
                    $eventRequest->update(['request_status' => 'cancelled']); // أو cancelled إذا كان لديك هذه الحالة

                    // تحرير الـ Slot ليعود متاحاً
                    $slot = EventSlot::findOrFail($eventRequest->slot_id);
                    $slot->update(['available' => true]);

                    // إرسال إيميل الإلغاء للمنظم
                    dispatch(new SendEventStatusEmailJob($eventRequest, 'cancelled'));

                    // بث التحديث فواً عبر WebSocket ليظهر الوقت متاحاً للآخرين من جديد
                    broadcast(new SlotAvailabilityChanged($slot->id, true));

                    // تحديث الكاش
                    //Cache::tags(['events_timeline'])->flush();
                }
                else{
                    throw ValidationException::withMessages([
                        'this request not approved at first !!',
                    ]);
                }
            },
            [
                'ar' => "تم إلغاء حجز الفعالية رقم (#{$requestId}) وتحرير الوقت بنجاح",
                'en' => "Event request (#{$requestId}) cancelled and slot freed successfully"
            ],
            [
                'request_id' => $requestId
            ],
            true
        );
    }
}
