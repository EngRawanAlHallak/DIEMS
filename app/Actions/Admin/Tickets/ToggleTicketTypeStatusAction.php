<?php

namespace App\Actions\Admin\Tickets;

use App\Actions\General\BaseAction;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;

class ToggleTicketTypeStatusAction extends BaseAction
{
    public function execute(int $id): TicketType
    {
        return $this->executeAction(
            function () use ($id) {
                $ticketType = TicketType::findOrFail($id);

                $ticketType->update([
                    'is_active' => !$ticketType->is_active
                ]);

                Cache::forget("admin:ticket-types");

                return $ticketType;
            },
            [
                'ar' => "تم تغيير حالة تفعيل نوع التذكرة رقم (#{$id}) بنجاح",
                'en' => "Ticket type (#{$id}) status toggled successfully"
            ],
            [
                'ticket_type_id' => $id,
                'is_active'      => $ticketType->is_active ?? null
            ],
            true
        );
    }
}
