<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventSlotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'date'       => Carbon::parse($this->slot_date)->format('d-m-Y'),
            // تحويل الوقت لصيغة مقروءة وسهلة للفرونت إند (مثال: 02:30 PM)
            'start_time' => Carbon::parse($this->start_time)->format('h:i A'),
            'end_time'   => Carbon::parse($this->end_time)->format('h:i A'),
            'available'  => $this->available,
        ];
    }
}
