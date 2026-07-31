<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class CompanyRequest extends Model
{
    use HasFactory,HasTranslations;


    protected $table = 'company_requests';

    protected $fillable = [
        'foreign_local',
        'company_name',
        'company_id',
        'responsible_name',
        'job_title',
        'email',
        'phone',
        'nationality',
        'commercial_register',
        'address',
        'sector',
        'company_description',
        'requested_area',
        'setup_preference',
        'terms_accepted_at',
        'request_status',
        'payment_status',
        'total_price',
        'required_deposit',
        'paid_amount',
        'payment_due_date',
        'admin_notes'
    ];

    protected $casts = [
        'terms_accepted_at' => 'datetime',
        'payment_due_date' => 'date',
        'total_price' => 'decimal:2',
        'required_deposit' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public $translatable = [
        'company_name',
        'company_description',
        'nationality',
        'address',
    ];


    // نوع الشركة
    const TYPE_FOREIGN = 'foreign';
    const TYPE_LOCAL = 'local';

    // نوع البوث
    const BOOTH_EQUIPPED = 'Equipped Booth';
    const BOOTH_NOT_EQUIPPED = 'Not Equipped Booth';
    const BOOTH_ROW = 'Row Space Only';
    const Lecture_Hall='Lecture Hall';
    const Kiosk = 'Kiosk';
    const Games_Area = 'Games Area';

    // حالة الطلب
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // حالة الدفع
    const PAYMENT_PAID = 'paid';
    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PARTIAL = 'partial_paid';

    // App\Models\CompanyRequest.php

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function booth()
    {
        return $this->hasOne(Booth::class, 'company_request_id');
    }

    public function documents()
    {
        return $this->morphMany(CompanyDocument::class, 'documentable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
