<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CompanyRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            // استخراج الاسم باللغة الإنجليزية عبر مكتبة spatie
            'company_name'   => $this->getTranslation('company_name', 'en', false) ?? $this->getTranslation('company_name', 'ar'),
            'sector'         => $this->sector,
            'request_status' => $this->request_status,
            'payment_status' => $this->payment_status,
            'request_date'   => Carbon::parse($this->created_at)->format('Y-m-d'),
        ];
    }
}
