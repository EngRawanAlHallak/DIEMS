<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'company_type',
        'pricing_type',
        'unit_price',
        'currency',
        'period_days',
        'min_area',
        'location_zone',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'min_area'   => 'decimal:2',
        'is_active'  => 'boolean',
    ];
}
