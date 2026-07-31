<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //use HasFactory;

    //protected $keyType = 'string';
    //public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'body', 'type', 'status', 'sender', 'data'
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
