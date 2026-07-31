<?php

namespace App\Http\Resources\VisitorHomePage;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HomeCompaniesResource extends JsonResource
{
    public function toArray($request)
    {
        //$lang = app()->getLocale();

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'logo'        => Storage::disk('s3')->url($this->logo),
            'nationality' => $this->nationality,
            'sector_name' => $this->sector_relation?->name,
            'hall_name'   => $this->whenLoaded('booths',
                fn() => $this->booths->first()?->hall?->name
            ),
        ];
    }
}
