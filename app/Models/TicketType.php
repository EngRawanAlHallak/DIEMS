<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class TicketType extends Model
{
    use HasTranslations;

    protected $guarded = [];
    public $translatable = ['name','description'];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:3',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_type_id');
    }

}
