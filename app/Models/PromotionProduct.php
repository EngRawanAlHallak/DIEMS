<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionProduct extends Model
{
    protected $fillable =[
        'product_id',
        'promotion_id',
    ];
}
