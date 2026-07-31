<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SectorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale(), false)
                ?? $this->getTranslation('name', 'en'),
            'booths' => $this->whenLoaded('booths', function () {
                $filteredBooths = ($this->pivot && $this->pivot->hall_id)
                    ? $this->booths->where('hall_id', $this->pivot->hall_id)
                    : $this->booths;

                return BoothDetailsResource::collection($filteredBooths);
            }),
        ];
    }
}
