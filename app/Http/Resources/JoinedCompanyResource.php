<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JoinedCompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->getTranslation('name', 'en', false) ?? $this->getTranslation('name', 'ar'),
            'logo'        => $this->logo ? Storage::disk('s3')->url($this->logo) : null,
            'sector_name' => $this->whenLoaded('sector_relation', fn() => $this->sector_relation->getTranslation('name', 'en')),
            'is_active'   => (bool) $this->is_active,
        ];
    }
}
