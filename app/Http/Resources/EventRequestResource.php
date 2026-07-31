<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EventRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            // استخراج الاسم باللغة الإنجليزية عبر مكتبة spatie
            'event_title'   => $this->getTranslation('event_title', 'en', false) ?? $this->getTranslation('event_title', 'ar'),
            'sector'         =>$this->sector ? ($this->sector->getTranslation('name', 'en', false) ?? $this->sector->getTranslation('name', 'ar')) : null,
            'request_status' => $this->request_status,
            'payment_status' => $this->payment_status,
            'request_date'   => Carbon::parse($this->created_at)->format('Y-m-d'),
        ];
    }
}
