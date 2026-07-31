<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;


class Product extends Model
{

   // use LogsActivity;

    use HasTranslations;

    protected $fillable =[
        'company_id',
        'name',
        'description',
        'price',
    ];

    protected $casts =[
        'price' => 'decimal:2',
    ];

    public $translatable = [
        'name',
        'description',
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function images() {
        return $this->hasMany(ProductImage::class);
    }

//    public function getActivitylogOptions(): LogOptions {
//        return LogOptions::defaults()
//            ->logAll()         // يلقط كل الحقول
//            ->logOnlyDirty()   // يسجل التغييرات الحقيقية فقط
//            ->useLogName('data_changes'); // تسمية السجل لتمييزه عن سجل الأكشنز
//    }


    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class ,'promotion_products')
            ->withTimestamps();
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }
}
