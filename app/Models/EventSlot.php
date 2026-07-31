<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSlot extends Model
{
    protected $fillable = [
        'slot_date',
        'start_time',
        'end_time',
        'available'
    ];

    public function event_requests()
    {
        return $this->hasMany(EventRequest::class, 'slot_id');
    }
}
