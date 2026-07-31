<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class CompanyRequestDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'request_id'     => $this->id,
            'request_status' => $this->request_status,

            'space_details' => [
                'requested_area'   => $this->requested_area,
                'setup_preference' => $this->setup_preference,
            ],

            'financial_details' => [
                'payment_status'   => $this->payment_status,
                'total_price'      => (float) ($this->total_price ?? 0),
                'required_deposit' => (float) ($this->required_deposit ?? 0),
                'paid_amount'      => (float) $this->paid_amount,
                'payment_due_date' => $this->payment_due_date,
            ],

            'booth' => $this->relationLoaded('booth')
                ? ($this->booth ? [
                    'booth_id'     => $this->booth->id,
                    'booth_number' => $this->booth->booth_number,
                    'booth_type'   => $this->booth->booth_type,
                    'size_sqm'     => $this->booth->size_sqm,
                    // جلب القاعة بأمان بعد التأكد من ربط الاستعلام بشكل صحيح
                    'hall'         => $this->booth->hall
                        ? ($this->booth->hall->getTranslation('name', 'en', false) ?? $this->booth->hall->getTranslation('name', 'ar')) : null,
                        ] : 'No booth booked yet')
                        : new MissingValue,
                ];
    }
}
