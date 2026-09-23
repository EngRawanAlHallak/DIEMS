<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class Company extends Model
{
    protected $guarded = [];
    use LogsActivity,HasTranslations;

    protected $casts = [
        'is_active' => 'boolean',
        'final_area' => 'float',
    ];

    public $translatable = [
        'name',
        'bio',
        'nationality',
        'address'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CompanyRequest::class, 'company_id');
    }

    // داخل موديل Company
    public function sector_relation(): BelongsTo // غيرنا الاسم هنا
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function products() {
        return $this->hasMany(Product::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class, 'company_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }


    // ─── Scopes ─────────────────────────────────────
    public function scopeActive($query)
    {
        //return $query->where('is_active', true);
        return $query->where('is_active', true)
            ->whereHas('requests', function ($q) {
                $q->where('request_status', 'approved')
                    ->whereIn('payment_status', ['paid', 'partial_paid']);
            });
    }

    public function scopeBySector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // لتسجيل كل الحقول التي تغيرت
            ->logOnlyDirty() // لتسجيل الحقول التي تغيرت قيمتها فعلياً فقط
            ->dontSubmitEmptyLogs(); // عدم تسجيل لوغ إذا لم يتغير شيء
    }

    public function documents()
    {
        return $this->morphMany(CompanyDocument::class, 'documentable');
    }
}
