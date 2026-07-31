<?php
// app/Actions/Visitor/GetEventDetailAction.php
namespace App\Actions\Visitor;

use App\Http\Resources\EventResource;
use App\Models\EventRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GetEventDetailAction
{
    /**
     * تفاصيل الفعالية — TTL أطول لأنها تتغير نادراً بعد الـ approval.
     */
    public function execute(int $id)
    {
        $lang = app()->getLocale();

        //Cache::forget("events:show:{$id}");
        $event = Cache::remember("events:show:{$id}:{$lang}", now()->addMinutes(30), function () use ($id) {
            return EventRequest::paid()
                ->with([
                    'slot:id,slot_date,start_time,end_time',
                    'sector:id,name',
                    'hall:id,name',
                ])
                ->select([
                    'id', 'event_title', 'event_description',
                    'image', 'organizer_name',
                    'slot_id', 'sector_id', 'hall_id',
                ])
                ->findOrFail($id);
        });
        return new EventResource($event);
    }
}
