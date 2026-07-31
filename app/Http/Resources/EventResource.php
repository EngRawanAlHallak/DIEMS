<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    public function toArray(Request $request)
    {
        //$lang = app()->getLocale();

        return [
            'id'          => $this->id,
            'event_title' => $this->event_title,

            'event_description' => $this->whenHas('event_description', function () {
                return $this->event_description;
            }),
            'organizer_name'    => $this->whenHas('organizer_name'),


            'image_url'   => $this->image ? Storage::disk('s3')->url($this->image) : null,
            'slot_id'     => $this->slot ?->id,
            'start_time'  => $this->slot ? Carbon::parse($this->slot->start_time)->format('g A') : null,
            'end_time'    =>$this->slot ? Carbon::parse($this->slot->end_time)->format('g A') : null,
            'sector_id'   => $this->sector_id,
            'sector_name' => $this->sector->name,
            'hall_id'     => $this->hall_id,
            'hall_name'   => $this->hall->name,
        ];
    }
}
