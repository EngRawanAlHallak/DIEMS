<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExhibitionProfileResource extends JsonResource
{
    public function toArray($request)
    {
        $lang = app()->getLocale();

        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'session' => $this->session,
            'address' => $this->address,
            'bio'     => $this->bio,
            'dates' => [
                'start' => $this->start_date ? Carbon::parse($this->start_date)->format('d-m-Y') : null,
                'end'   => $this->end_date ? Carbon::parse($this->end_date)->format('d-m-Y') : null,
            ],
            'working_hours' => [
                'open'  => $this->open_time? Carbon::parse($this->open_time)->format('g A') : null,
                'close' => $this->close_time? Carbon::parse($this->close_time)->format('g A') : null,
            ],
            'contact' => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
                'emergency_phone'=>$this->emergency_phone

            ],
            'social' => [
                'facebook'  => $this->facebook_url,
                'instagram' => $this->instagram_url,
                'x'         => $this->x_url,
            ],
        ];
    }
}
