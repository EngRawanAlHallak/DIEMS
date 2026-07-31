<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TransportationResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = app()->getLocale();
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'image'           => $this->image ? Storage::disk('s3')->url($this->image) : null,
            'google_maps_url' => $this->google_maps_url,
        ];
    }


}
