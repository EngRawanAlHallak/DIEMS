<?php

namespace App\Actions\Admin\Events;

use App\Actions\General\BaseAction;
use App\Models\EventRequest;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AssignHallToEventAction extends BaseAction
{
    public function execute(int $eventRequestId, int $hallId): EventRequest
    {
        return $this->executeAction(
            function () use ($eventRequestId, $hallId) {
                // 1. تحميل الـ Slot الخاص بالطلب الحالي لكي نعرف أوقاته (Eager Load missing)
                $eventRequest = EventRequest::findOrFail($eventRequestId);
                $eventRequest->loadMissing('slot');
                $targetSlot = $eventRequest->slot;

                if ($eventRequest->request_status !== 'pending') {
                    $hallName = Hall::findOrFail($hallId)->name;
                    throw ValidationException::withMessages([
                        'status' => ["This event request not pending and have a hall assigned >> {$hallName}"]]);
                }
                // 2. خوارزمية فحص التداخل (Time Overlap Logic)
                $hasOverlap = EventRequest::query()
                    ->where('hall_id', $hallId) // نفس القاعة المطلوبة
                    ->where('request_status', 'approved') // مع الفعاليات الموافق عليها فقط
                    ->where('id', '!=', $eventRequest->id) // استثناء الفعالية الحالية من الفحص
                    ->whereHas('slot', function ($query) use ($targetSlot) {
                        $query->where('slot_date', $targetSlot->slot_date) // نفس اليوم
                        // شرط التداخل: بداية الجديدة أصغر من نهاية القديمة، ونهاية الجديدة أكبر من بداية القديمة
                        ->where('start_time', '<', $targetSlot->end_time)
                            ->where('end_time', '>', $targetSlot->start_time);
                    })
                    ->exists();

                // 3. إذا وجد تداخل، نرفض العملية فوراً
                if ($hasOverlap) {
                    throw ValidationException::withMessages([
                        'hall_id' => ['This hall is booked for another event on the same day and overlaps in time with this event.']
                    ]);
                }

                // 4. إذا السجل نظيف، نسند القاعة للطلب
                $eventRequest->update([
                    'hall_id' => $hallId
                ]);

                // 5. مسح الكاش لتحديث المخطط الزمني
                Cache::forget("admin:event_request_detail:{$eventRequestId}");
                Cache::forget("admin:events_timeline:all");
                Cache::forget("events:show:{$eventRequestId}");

                return $eventRequest;
            },
            [
                'ar' => "تم إسناد القاعة رقم (#{$hallId}) لطلب الفعالية رقم (#{$eventRequestId}) بنجاح",
                'en' => "Hall (#{$hallId}) assigned to event request (#{$eventRequestId}) successfully"
            ],
            [
                'event_request_id' => $eventRequestId,
                'hall_id'          => $hallId,
            ],
            true
        );
    }
}
