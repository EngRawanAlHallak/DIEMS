<?php

namespace App\Actions\Admin\Halls;

use App\Models\Booth;
use Illuminate\Support\Facades\Cache;

class NullBoothAction
{
    public function execute(int $id): bool
    {
        $booth = Booth::findOrFail($id);

        $booth->update([
            'company_id' => null,
        ]);
        Cache::forget("admin:hall:{$booth->hall_id}");
        return true;
    }
}
