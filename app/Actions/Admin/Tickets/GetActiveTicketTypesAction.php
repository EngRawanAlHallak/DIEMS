<?php

namespace App\Actions\Admin\Tickets;

use App\Http\Resources\TicketTypeResource;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;

class GetActiveTicketTypesAction
{
    public function execute()
    {
        return Cache::rememberForever("admin:active_ticket_types", function () {
            $types = TicketType::where('is_active', true)->get();
            return TicketTypeResource::collection($types);
        });
    }
}
