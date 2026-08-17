<?php

namespace App\Actions\Admin\Tickets;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetVisitorTicketsAction
{
    public function execute(string $guestId): mixed
    {
        $cacheKey = "visitor:tickets:{$guestId}";
        //Cache::forget($cacheKey);
        return Cache::remember($cacheKey, now()->addDays(1), function () use ($guestId) {

            $allTickets = Ticket::with(['ticketType', 'ticketOrder'])
                ->whereHas('ticketOrder', function ($q) use ($guestId) {
                    $q->where('guest_id', $guestId)
                        ->where('payment_status', 'paid');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $valid = $allTickets->where('status', 'valid')->values();
            $used = $allTickets->where('status', 'used')->values();

            return [
                'valid_tickets' => TicketResource::collection($valid)->resolve(),
                'used_tickets' => TicketResource::collection($used)->resolve(),
                ];
        });
    }
}
