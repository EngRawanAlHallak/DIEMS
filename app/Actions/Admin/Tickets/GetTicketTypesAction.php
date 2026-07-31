<?php

namespace App\Actions\Admin\Tickets;

use App\Http\Resources\TicketTypeResource;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;

class GetTicketTypesAction
{

    public function execute()
    {
        Cache::forget("admin:ticket-types");
        return Cache::rememberForever("admin:ticket-types", function () {

            $tickets = TicketType::orderBy('created_at', 'desc')->get();
            return TicketTypeResource::collection($tickets)->resolve();
        });
    }
}
