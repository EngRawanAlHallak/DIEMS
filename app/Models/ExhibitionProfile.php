<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ExhibitionProfile extends Model
{
    use HasTranslations;
    protected $guarded = [];

    public $translatable = ['name','address','bio',];

    protected $fillable = [
        'name',
        'session',
        'address',
        'bio',
        'start_date',
        'end_date',
        'open_time',
        'close_time',
        'contact_email',
        'contact_phone',
        'emergency_phone',
        'instagram_url',
        'facebook_url',
        'x_url',
        'title',
        'syria_logo',
        'welcome_video',
        'transport_interval_minutes',
        'transport_start_time',
        'transport_end_time',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];



}
