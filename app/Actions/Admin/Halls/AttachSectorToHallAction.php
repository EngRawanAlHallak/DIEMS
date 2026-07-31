<?php

namespace App\Actions\Admin\Halls;

use App\Models\Hall;
use Illuminate\Support\Facades\Cache;

class AttachSectorToHallAction
{
    public function execute(int $hallId, int $sectorId)
    {
        // syncWithoutDetaching لمنع تكرار الربط
        $hall = Hall::findOrFail($hallId);
        $hall->sectors()->syncWithoutDetaching([$sectorId]);

        Cache::forget("admin:hall:{$hallId}");
        return true;
    }
}
