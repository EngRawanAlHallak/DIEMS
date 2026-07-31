<?php

namespace App\Actions\Admin\Events;

use App\Actions\General\BaseAction;
use App\Events\NewSlotCreated;
use App\Models\EventSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateEventSlotAction extends BaseAction
{
    public function execute(array $data): EventSlot
    {
        return $this->executeAction(
            function () use ($data) {
                $data['start_time'] = Carbon::createFromFormat('g:i A', $data['start_time'])->format('H:i');
                $data['end_time']   = Carbon::createFromFormat('g:i A', $data['end_time'])->format('H:i');

                // 1. فحص التداخل
                $hasOverlap = EventSlot::query()
                    ->where('slot_date', $data['slot_date'])
                    ->where(function ($query) use ($data) {
                        $query->where('start_time', '<', $data['end_time'])
                            ->where('end_time', '>', $data['start_time']);
                    })
                    ->exists();

                if ($hasOverlap) {
                    throw ValidationException::withMessages([
                        'start_time' => ['هذا الوقت يتداخل مع فترة زمنية مسجلة سابقاً في نفس اليوم.']
                    ]);
                }

                // 2. إنشاء الـ Slot
                $newSlot = EventSlot::create($data);

                /*// 3. بث الحدث الفوري للواجهات (WebSocket)
                broadcast(new NewSlotCreated($newSlot))->toOthers();
                // 3. مسح كاش المخطط الزمني
                Cache::forget("admin:events_timeline:all");*/
                Log::info('BEFORE BROADCAST', ['slot_id' => $newSlot->id]);
                try {
                    broadcast(new NewSlotCreated($newSlot))->toOthers();
                    Log::info('BROADCAST SUCCESS');
                } catch (\Throwable $e) {
                    Log::error('BROADCAST FAILED', ['error' => $e->getMessage()]);
                }

                Cache::forget("admin:events_timeline:all");

                return $newSlot;
            },
            [
                'ar' => "تم إنشاء فترة زمنية (Slot) جديدة بتاريخ {$data['slot_date']} بنجاح",
                'en' => "New event slot created on {$data['slot_date']} successfully"
            ],
            [
                'slot_date'  => $data['slot_date'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time']
            ],
            true
        );
    }
}
