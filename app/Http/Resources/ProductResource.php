<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'description' => $this->description,
        'price' => $this->price,
        'images' => $this->images->map(function($image) {
            return [
                'id' => $image->id,
                'url' => Storage::disk('s3')->url($image->image_path),
            ];
        }),
        'created_at' => $this->created_at?->format('Y-m-d'),
    ];
}
}
