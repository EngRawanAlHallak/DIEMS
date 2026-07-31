<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JoinedCompanyDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            // 1. المعلومات العامة للشركة
            'company_info' => [
                'id'                 => $this->id,
                'name'               => $this->getTranslation('name', 'en', false) ?? $this->getTranslation('name', 'ar'),
                'logo'               => $this->logo ? Storage::disk('public')->url($this->logo) : null,
                'is_active'          => (bool) $this->is_active,
                'sector_name'        => $this->whenLoaded('sector_relation', fn() => $this->sector_relation->getTranslation('name', 'en', false) ?? $this->sector->getTranslation('name', 'ar')),
                'bio'                => $this->getTranslation('bio', 'en', false) ?? $this->getTranslation('bio', 'ar'),
                'nationality'        => $this->getTranslation('nationality', 'en', false) ?? $this->getTranslation('nationality', 'ar'),
                'address'            => $this->getTranslation('address', 'en', false) ?? $this->getTranslation('address', 'ar'),
                'documents'          => $this->whenLoaded('documents', function () {
                    return $this->documents->map(function ($document) {
                        return [
                            'id'        => $document->id,
                            'file_type' => $document->file_type,
                            'file_url'  => $document->file_path ? Storage::disk('s3')->url($document->file_path) : null,
                        ];
                    });
                }),
                'another_info'       => $this->whenLoaded('requests', function() {
                    $firstRequest = $this->requests->first();
                    if (! $firstRequest) return null;

                    return [
                        'foreign_local'       => $firstRequest->foreign_local,
                        'responsible_person'  => $firstRequest->responsible_name,
                        'job_title'           => $firstRequest->job_title,
                        'email'               => $firstRequest->email,
                        'phone'               => $firstRequest->phone,
                        'commercial_register' => $firstRequest->commercial_register,
                    ];
                }),
            ],
            'requests_details' => CompanyRequestDetailsResource::collection($this->whenLoaded('requests')),
        ];
    }
    /*public function toArray($request): array
    {
        $lang = app()->getLocale();

        return [
            'company_info' => [
                'id'                 => $this->id,
                'logo'               => $this->logo ? Storage::disk('public')->url($this->logo) : null,
                'is_active'          => (bool) $this->is_active,
            ],

            'request_details' => $this->whenLoaded('request', function () {
                return AdminCompanyRequestDetailResource::make($this->request);
            }),

            'exhipition_details' => [
                'sector_name' => $this->whenLoaded('sector_relation', fn() => $this->sector_relation->getTranslation('name', 'en')),
                'final_area'  => $this->final_area,
            ],

            'financial_details' => $this->whenLoaded('request', fn() => [
                'total_price'      => $this->request->total_price ? (float) $this->request->total_price : 0.00,
                'required_deposit' => $this->request->required_deposit ? (float) $this->request->required_deposit : 0.00,
                'paid_amount'      => (float) $this->request->paid_amount,
                'payment_due_date' => $this->request->payment_due_date,
            ]),

            'booths' => $this->whenLoaded('booths', function () {
                return $this->booths->isNotEmpty()
                    ? BoothDetailsResource::collection($this->booths)
                    : 'Booth not booked';
            }),
        ];
    }*/
}
