<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GateCode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_for_date' => 'date',
        'is_active' => 'boolean',
    ];
}
