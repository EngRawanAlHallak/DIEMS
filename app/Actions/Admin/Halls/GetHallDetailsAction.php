<?php

namespace App\Actions\Admin\Halls;

use App\Http\Resources\HallResource;
use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class GetHallDetailsAction
{
    public function execute(int $id)
    {
        Cache::forget("admin:hall:{$id}");
        return Cache::remember("admin:hall:{$id}", now()->addDays(1), function () use ($id){
            $hall = Hall::with([
                'sectors',
                'sectors.booths' => function ($query) {
                    $query->orderBy('booth_number', 'asc');
                }
            ])->findOrFail($id);

            return HallResource::make($hall)->resolve();
        });
    }
}
