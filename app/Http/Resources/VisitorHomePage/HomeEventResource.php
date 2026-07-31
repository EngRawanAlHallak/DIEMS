<?php

namespace App\Http\Resources\VisitorHomePage;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HomeEventResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = app()->getLocale();

        return [
            'id'          => $this->id,
            'event_title' => $this->event_title, // ترجمة العنوان
            'image_url'   => $this->image ? Storage::disk('s3')->url($this->image) : null,
            'sector_name' => $this->sector ?->name,
            'hall_name' => $this->hall ?->name,
            'start_time'  => $this->slot ? Carbon::parse($this->slot->start_time)->format('g A') : null,
        ];
    }

}
