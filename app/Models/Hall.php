<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Hall extends Model
{
    use HasTranslations;

    protected $fillable = ['name','total_area_sqm','description', 'floor'];
    public $translatable = ['name'];

    protected $casts = [];

    public function sectors() {
        return $this->belongsToMany(Sector::class, 'hall_sector');
    }

    public function booths() {
        return $this->hasMany(Booth::class);
    }
}
