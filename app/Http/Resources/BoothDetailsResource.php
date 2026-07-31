<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BoothDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // حقول مشتركة وجديدة (إضافتها آمنة تماماً ولا تضرب الشغل القديم)
            'id' => $this->id,
            'booth_number' => $this->booth_number,
            'booth_type' => $this->booth_type,
            'equipment_type' => $this->equipment_type,
            'size_sqm' => $this->size_sqm,
            'available' => $this->available,

            'hall_name' => $this->whenLoaded('hall', function () {
                return $this->hall->getTranslation('name', 'en', false) ?? $this->hall->getTranslation('name', 'ar');
            }),

            'sector_name' => $this->whenLoaded('sector', function () {
                return $this->sector->getTranslation('name', 'en', false) ?? $this->sector->getTranslation('name', 'ar');
            }),
        ];
    }
}
