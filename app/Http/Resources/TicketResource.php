<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'ticket_uuid'    => $this->uuid, // الـ UUID الذي سيتحول لـ QR Code بالفرونت
            'visitor_name'   => $this->visitor_name,
            'visitor_email'  => $this->visitor_email,
            'visitor_phone'  => $this->visitor_phone,
            'interest_field' => $this->interest_field,
            'status'         => $this->status, // valid, used, cancelled
            'used_at'        => $this->used_at ? $this->used_at->format('Y-m-d H:i:s') : null,
            'ticket_type'    => $this->whenLoaded('ticketType', function() {
                return [
                    'name' => $this->ticketType->name,
                ];
            }),
        ];
    }
}
