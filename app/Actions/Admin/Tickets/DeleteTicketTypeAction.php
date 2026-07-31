<?php

namespace App\Actions\Admin\Tickets;

use App\Actions\General\BaseAction;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DeleteTicketTypeAction extends BaseAction
{
    public function execute(int $id): TicketType
    {
        return $this->executeAction(
            function () use ($id) {
                $ticketType = TicketType::findOrFail($id);

                if ($ticketType->tickets()->exists()) {
                    throw ValidationException::withMessages([
                        'ticket_type' => ['لا يمكن حذف نوع تذكرة تم البيع منه سابقاً، يمكن إيقاف تفعيله بدلاً من ذلك.']
                    ]);
                }

                $deletedTicketType = $ticketType;
                $ticketType->delete();

                Cache::forget("admin:ticket-types");

                return $deletedTicketType;
            },
            [
                'ar' => "تم حذف نوع التذكرة رقم (#{$id}) بنجاح",
                'en' => "Ticket type (#{$id}) deleted successfully"
            ],
            [
                'ticket_type_id' => $id
            ],
            true
        );
    }
}
