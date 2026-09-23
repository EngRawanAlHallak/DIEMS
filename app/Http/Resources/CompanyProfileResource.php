<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class CompanyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // هنا $this تعود تلقائياً لموديل الـ Company الممرر إليها
        return [
            'id'                 => $this->id,
            'company_email'      => $this->user ? $this->user->email : null,
            'company_phone'      => $this->user ? $this->user->phonenumber : null,
            'company_name'       => $this->name,
            'logo'               => $this->logo ? Storage::disk('s3')->url($this->logo) : null,
            'responsible_person' => $this->responsible_person,
            'sector'             => $this->sector,
            'sector_details'     => [
                'id'   => $this->sector_id,
                'name' => $this->sector_relation ? $this->sector_relation->name : null,
            ],
            'bio'                => $this->bio,
            'nationality'        => $this->nationality,
            'address'            => $this->address,
            'final_area'         => $this->final_area,
            'booth_type'         => $this->booth_type,
            'is_active'          => $this->is_active,
        ];
    }
}

