<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Globalsetting extends Model
{
    use HasTranslations;
    protected $fillable = ['key', 'value', 'type', 'group'];
    public $translatable = ['value'];

    protected $casts = [
        'value' => 'array',
    ];
}
