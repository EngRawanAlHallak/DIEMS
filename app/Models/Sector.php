<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Sector extends Model
{
    use HasTranslations;

    protected $fillable = ['name','hall_id'];
    public $translatable = ['name'];

    protected $casts = [];

    public function eventRequests(): HasMany
    {
        return $this->hasMany(EventRequest::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function halls() {
        return $this->belongsToMany(Hall::class, 'hall_sector');
    }
}
