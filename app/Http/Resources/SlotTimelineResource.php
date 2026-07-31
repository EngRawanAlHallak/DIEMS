<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SlotTimelineResource extends JsonResource
{
    public function toArray($request): array
    {
        $start = Carbon::parse($this->start_time);
        $end   = Carbon::parse($this->end_time);

        return [
            'slot_id'    => $this->id,
            'date'       => $this->slot_date,
            'start_time' => $start->format('h:i A'),
            'end_time'   => $end->format('h:i A'),

            // سيتم دمج هذه البيانات في الـ Response فقط إذا تم استدعاء with('event_requests')
            $this->mergeWhen($this->relationLoaded('event_requests'), function () {
                $approvedEvent = $this->event_requests->first();

                return [
                    'is_booked'  => $approvedEvent ? true : false,
                    'event_details' => $approvedEvent ? [
                        'event_id'    => $approvedEvent->id,
                        'event_title' => $approvedEvent->getTranslation('event_title', 'en', false) ?? $approvedEvent->getTranslation('event_title', 'ar'),

                        'hall' => $approvedEvent->relationLoaded('hall') && $approvedEvent->hall
                            ? ($approvedEvent->hall->getTranslation('name', 'en', false) ?? $approvedEvent->hall->getTranslation('name', 'ar'))
                            : 'non assign yet',
                    ] : null,
                ];
            }),
        ];
    }
}
