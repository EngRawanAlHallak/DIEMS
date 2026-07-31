<?php

namespace App\Actions\Admin\Tickets;

use App\Actions\General\BaseAction;
use App\Actions\General\TranslateTextAction;
use App\Models\TicketType;
use Illuminate\Support\Facades\Cache;

class CreateTicketTypeAction extends BaseAction
{
    public function __construct(
        protected TranslateTextAction $translator
    ) {}

    public function execute(array $data): TicketType
    {
        return $this->executeAction(
            function () use ($data) {
                $data['name']        = $this->translator->execute($data['name']);
                $data['description'] = $this->translator->execute($data['description']);
                $data['is_active']   = $data['is_active'] ?? true;

                $ticketType = TicketType::create($data);

                Cache::forget("admin:ticket-types");
                Cache::forget('admin_ticket_metrics');

                return $ticketType;
            },
            [
                'ar' => "تم إضافة نوع تذكرة جديد بنجاح",
                'en' => "New ticket type created successfully"
            ],
            [
                'price'         => $data['price'] ?? null,
                'persons_count' => $data['persons_count'] ?? null
            ],
            true
        );
    }
}
