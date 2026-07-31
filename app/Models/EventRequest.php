<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class EventRequest extends Model
{
    use HasTranslations;

    public $translatable = ['event_title','event_description'];
    protected $fillable = [
        'slot_id',
        'sector_id',
        'hall_id',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'event_title',
        'event_description',
        'Expected_attendance',
        'equipment_needed',
        'image',
        'request_status',
        'payment_status',
        'is_special',
        'payment_due_date',
        'total_price',
        'required_deposit'
    ];


    protected $casts = [
        'is_special' => 'boolean',
        'payment_due_date' => 'datetime',
    ];

    // ─── Relations ─────────────────────────────────
    public function slot(): BelongsTo
    {
        return $this->belongsTo(EventSlot::class, 'slot_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    // ─── Scopes ─────────────────────────────────────
    public function scopePaid($query)
    {
        return $query->where('request_status', 'approved')
            ->where('payment_status', 'paid');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereHas('slot', fn($q) => $q->where('slot_date', $date));
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
