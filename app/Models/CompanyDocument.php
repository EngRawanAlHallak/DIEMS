<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CompanyDocument extends Model
{
    protected $fillable = ['file_path', 'file_type', 'original_name', 'documentable_id', 'documentable_type'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
