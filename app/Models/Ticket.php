<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $guarded = [];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * التذكرة تنتمي إلى طلب شراء واحد (BelongsTo)
     */
    public function ticketOrder(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    /**
     * التذكرة تتبع لنوع تذكرة محدد (BelongsTo)
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }
}
