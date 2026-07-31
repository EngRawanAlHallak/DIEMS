<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booth extends Model
{
    protected $fillable = [
        'hall_id',
        'booth_number',
        'booth_type',
        'equipment_type',
        'size_sqm',
        'available',
        'company_id',
        'company_request_id',
        'sector_id',
        'hall_id'
    ];

    protected $casts = ['available' => 'boolean'];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
