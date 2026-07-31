<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Transportation extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'image',
        'google_maps_url',
    ];
    public $translatable = ['name'];

    protected $casts = [];

}
