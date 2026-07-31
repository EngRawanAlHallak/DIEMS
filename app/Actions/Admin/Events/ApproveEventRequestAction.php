<?php

namespace App\Actions\Admin\Events;

use App\Actions\General\BaseAction;
use App\Events\SlotAvailabilityChanged;
use App\Jobs\Event\SendEventStatusEmailJob;
use App\Models\EventRequest;
use App\Models\EventSlot;
use App\Models\PricingTier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveEventRequestAction extends BaseAction
{
    public function execute(EventRequest $eventRequest): EventRequest
    {
        // 1. تطبيق قفل رديس الذري (Atomic Lock) على مستوى الـ Slot لمدة 10 ثوانٍ
        $lockKey = "lock_slot_booking_{$eventRequest->slot_id}";
        $lock = Cache::lock($lockKey, 10);

        return $lock->block(5, function () use ($eventRequest) {

            // استخدام executeAction لتغليف العملية وتسجيل اللوج عند النجاح أو رمي الخلل
            return $this->executeAction(
                function () use ($eventRequest) {

                    $eventRequest = EventRequest::lockForUpdate()->find($eventRequest->id);
                    $slot = EventSlot::lockForUpdate()->findOrFail($eventRequest->slot_id);

                    if (!$slot->available || $eventRequest->request_status === 'approved') {
                        throw ValidationException::withMessages([
                            'this time in this request is pre-booked',
                        ]);
                    }

                    $tier = PricingTier::where('slug', 'Lecture Hall')->firstOrFail();
                    $totalPrice = $tier->unit_price;

                    // 2. تحديث الطلب الفائز
                    $eventRequest->update([
                        'request_status'   => 'approved',
                        'total_price'      => $totalPrice,
                        'payment_due_date' => Carbon::now()->addHours(48),
                    ]);

                    // 3. إغلاق الـ Slot
                    $slot->update(['available' => false]);

                    // 4. الرفض التلقائي لبقية الطلبات المنافسة على نفس الوقت (Auto-Reject)
                    $competingRequests = EventRequest::where('slot_id', $slot->id)
                        ->where('id', '!=', $eventRequest->id)
                        ->where('request_status', 'pending')
                        ->get();

                    if ($competingRequests->isNotEmpty()) {
                        EventRequest::whereIn('id', $competingRequests->pluck('id'))
                            ->update(['request_status' => 'rejected']);

                        // إرسال إيميل الرفض التلقائي للمنافسين عبر الطوابير (Jobs) لأعلى أداء
                        foreach ($competingRequests as $competing) {
                            dispatch(new SendEventStatusEmailJob($competing, 'auto_rejected'));
                        }
                    }

                    // 5. إرسال إيميل الموافقة للفائز
                    dispatch(new SendEventStatusEmailJob($eventRequest, 'approved'));

                    // 6. بث التحديث فواً للواجهات عبر الـ WebSocket لإغلاق الوقت عند الجميع
                    broadcast(new SlotAvailabilityChanged($slot->id, false));

                    // 7. مسح الكاش
                    //Cache::tags(['events_timeline'])->flush();
                    return $eventRequest;
                },
                [
                    'ar' => "تمت الموافقة على طلب الفعالية رقم (#{$eventRequest->id}) وتحديد القاعة بنجاح",
                    'en' => "Event request (#{$eventRequest->id}) approved and hall assigned successfully"
                ],
                [
                    'slot_id'  => $eventRequest->slot_id,
                ],
                true // تفعيل تسجيل اللوج
            );
        });
    }
}
