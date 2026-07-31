<?php
// app/Http/Resources/Visitor/CompanyDetailResource.php

namespace App\Http\Resources;

use App\Http\Resources\BoothResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PromotionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $hasSalesBooth = (bool) $this->has_sales_booth;

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'bio'         => $this->bio,
            'nationality' => $this->nationality,
            'address'     => $this->address,

            'logo'        => $this->logo
                ? Storage::disk('s3')->url($this->logo)
                : null,

            // Sector from the relationship (not the raw string column)
            'sector'      => $this->whenLoaded('sectorCategory', fn () => [
                'id'   => $this->sectorCategory->id,
                'name' => $this->sectorCategory->name, // translatable
            ]),

            // All booths assigned to this company → location cards
            'locations'   => BoothResource::collection(
                $this->whenLoaded('booths')
            ),

            'has_sales_booth' => $hasSalesBooth,

            // Products are always shown regardless of booth type
            'products'    => ProductResource::collection(
                $this->whenLoaded('products')
            ),

            // Promotions block is omitted entirely for display-only companies
            'promotions'  => $this->when(
                $hasSalesBooth,
                fn () => PromotionResource::collection(
                    $this->whenLoaded('promotions')
                )
            ),
        ];
    }
}
