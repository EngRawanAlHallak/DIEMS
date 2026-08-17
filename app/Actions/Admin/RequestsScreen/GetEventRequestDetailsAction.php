<?php

namespace App\Actions\Admin\RequestsScreen;

use App\Http\Resources\AdminCompanyRequestDetailResource;
use App\Http\Resources\EventRequestDetailsResource;
use App\Models\CompanyRequest;
use App\Models\EventRequest;
use Illuminate\Support\Facades\Cache;

class GetEventRequestDetailsAction
{
    public function execute(int $id)
    {
        $cacheKey = "admin:event_request_detail:{$id}";
        //Cache::forget($cacheKey);
        //return Cache::tags(['company_requests'])->remember($cacheKey, now()->addHours(4), function () use ($id) {
        return Cache::remember($cacheKey, now()->addHours(4),function () use ($id) {
            $eventRequest = EventRequest::with([
                'slot:id,slot_date,start_time,end_time,available',
                'sector:id,name',
                'hall:id,name'
            ])->findOrFail($id);

            return EventRequestDetailsResource::make($eventRequest)->resolve();
        });
    }

}
