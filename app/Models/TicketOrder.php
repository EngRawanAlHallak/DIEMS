<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TicketOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_amount' => 'decimal:3',
    ];

    /**
     * علاقة الطلب مع التذاكر الفردية التابعة له (One-to-Many)
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_order_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
