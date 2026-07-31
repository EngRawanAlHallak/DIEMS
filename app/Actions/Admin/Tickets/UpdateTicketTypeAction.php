<?php

namespace App\Actions\Admin\Tickets;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;

class UpdateTicketTypeAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(int $id, array $data): TicketType
    {
        return $this->executeAction(
            function () use ($id, $data) {
                $ticketType = TicketType::findOrFail($id);

                if (isset($data['name']) && !is_array($data['name'])) {
                    $data['name'] = $this->translator->execute($data['name']);
                }

                if (isset($data['description']) && !is_array($data['description'])) {
                    $data['description'] = $this->translator->execute($data['description']);
                }

                $ticketType->update($data);

                Cache::forget("admin:ticket-types");
                Cache::forget('admin_ticket_metrics');

                return $ticketType;
            },
            [
                'ar' => "تم تحديث بيانات نوع التذكرة رقم (#{$id}) بنجاح",
                'en' => "Ticket type (#{$id}) updated successfully"
            ],
            [
                'ticket_type_id' => $id
            ],
            true
        );
    }
}
