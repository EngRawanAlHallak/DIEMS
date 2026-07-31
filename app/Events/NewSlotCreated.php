<?php

namespace App\Events;

use App\Models\EventSlot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSlotCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EventSlot $slot;

    public function __construct(EventSlot $slot)
    {
        $this->slot = $slot;
    }

    public function broadcastOn(): Channel
    {
        // البث على نفس القناة الخاصة بالـ Timeline
        return new Channel('slots-timeline');
    }

    public function broadcastAs(): string
    {
        return 'NewSlotCreated';
    }

    public function broadcastWith(): array
    {
        // إرسال تفاصيل السلوت الجديد ليقوم الفرونت إند برسمه فوراً
        return [
            'id'           => $this->slot->id,
            'slot_date'    => $this->slot->slot_date,
            'start_time'   => $this->slot->start_time,
            'end_time'     => $this->slot->end_time,
            'is_available' => $this->slot->available,
        ];
    }
}
