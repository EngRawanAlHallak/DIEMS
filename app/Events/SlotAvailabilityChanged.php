<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlotAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $slotId;
    public bool $isAvailable;

    public function __construct(int $slotId, bool $isAvailable)
    {
        $this->slotId = $slotId;
        $this->isAvailable = $isAvailable;
    }

    // تحديد القناة التي سيتم البث عليها للفرونت إند
    public function broadcastOn(): Channel
    {
        return new Channel('slots-timeline');
    }

    // اسم الحدث الذي سيستمع له الفرونت إند
    public function broadcastAs(): string
    {
        return 'SlotStatusUpdated';
    }
}
