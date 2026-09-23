<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'discount_percentage' => $this->discount_percentage,
            'bundle_price' => $this->total_package_price,
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date ? Carbon::parse($this->start_date)->format('d-m-Y') : null,
            'end_date' => $this->end_date ? Carbon::parse($this->end_date)->format('d-m-Y') : null,
            'is_expired' => $this->end_date ? $this->end_date->isPast() : false,
            'products' => $this->products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'original_price' => $product->price,
                    'price_after_discount' => $this->type === 'discount'
                        ? $product->price * (1 - $this->discount_percentage / 100)
                        : null
                ];
            }),
        ];
    }
}
