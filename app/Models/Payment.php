<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'uuid',
        'payable_type',
        'payable_id',
        'paymera_payment_id',
        'amount',
        'currency',
        'status',
        'payment_url',
        'expires_at',
        'gateway_response',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'expires_at'       => 'datetime',
        'amount'           => 'decimal:2',
    ];

    /**
     * الحصول على الموديل الأب المالك لعملية الدفع (TicketOrder أو CompanyRequest)
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
