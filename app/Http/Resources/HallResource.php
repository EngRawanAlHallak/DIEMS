<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HallResource extends JsonResource
{
    public function toArray($request): array
    {
        $lang = app()->getLocale();
        return [
            'id'             => $this->id,
            'name'           => $this->getTranslation('name','en', false) ?? $this->getTranslation('name', 'ar'),
            'description'    => $this->description,
            'floor'          => $this->floor,
            'total_area_sqm' => $this->total_area_sqm,
            'sectors'        => SectorResource::collection($this->whenLoaded('sectors')),
        ];
    }
}
