<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventRequestDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 1. المعلومات الأساسية
            'id'                  => $this->id,
            'event_title'         => $this->getTranslation('event_title', 'en', false) ?? $this->getTranslation('event_title', 'ar'),
            'event_description'   => $this->getTranslation('event_description', 'en', false) ?? $this->getTranslation('event_description', 'ar'),
            'expected_attendance' => $this->Expected_attendance,
            'equipment_needed'    => $this->equipment_needed,
            'is_special'          => (bool) $this->is_special,

            'image'               => $this->image ? Storage::disk('s3')->url($this->image) : null,

            // 2. معلومات المنظم
            'organizer' => [
                'name'  => $this->organizer_name,
                'email' => $this->organizer_email,
                'phone' => $this->organizer_phone,
            ],

            // 3. حالات الطلب والمالية
            'status' => [
                'request_status' => $this->request_status,
                'payment_status' => $this->payment_status,
            ],
            'financials' => [
                'total_price'      => (float) $this->total_price,
                'required_deposit' => (float) $this->required_deposit,
                'paid_amount'      => (float) $this->paid_amount,
                'payment_due_date' => $this->payment_due_date ? Carbon::parse($this->payment_due_date)->format('d-m-Y') : null,
            ],

            // 4. العلاقات (الوقت، المكان)
            'timing'      => $this->whenLoaded('slot', fn () => EventSlotResource::make($this->slot)),

            'location'    => [
                'hall_name'   => $this->whenLoaded('hall', fn () => $this->hall->getTranslation('name', 'en', false) ?? $this->hall->getTranslation('name', 'ar')),
                'sector_name' => $this->whenLoaded('sector', fn () => $this->sector->getTranslation('name', 'en', false) ?? $this->sector->getTranslation('name', 'ar')),
            ],

            'requested_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
