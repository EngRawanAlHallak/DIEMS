<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
//    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens,
        HasFactory,
        HasUuids,
        HasRoles,
        Notifiable,
        SoftDeletes,
        LogsActivity;

    public const MAX_ATTEMPTS= 3;
    public const LOCKOUT_MINUTES= 10;
    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'phonenumber',
        'email',
        'password',
        'failed_attempts',
        'locked_until',
        'last_failed_at',
        'fcm_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until'      => 'datetime',
            'last_failed_at'    => 'datetime',
            'password'          => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'failed_attempts', 'locked_until'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $event) => "User {$event}");
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null
            && $this->locked_until->isFuture();
    }

    public function lockRemainingMinutes(): int
    {
        if (! $this->isLocked()) {
            return 0;
        }
        return (int) now()->diffInMinutes($this->locked_until);
    }

    public function recordFailedAttempt(): void
    {
        $attempts = $this->failed_attempts + 1;

        $this->update([
            'failed_attempts' => $attempts,
            'last_failed_at'  => now(),
        ]);

        // Lock when threshold reached
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->lockAccount();
        }
    }

    public function lockAccount(): void
    {
        $lockedUntil = Carbon::now()->addMinutes(self::LOCKOUT_MINUTES);

        $this->update([
            'locked_until'    => $lockedUntil,
            'failed_attempts' => 0,
        ]);

        activity('auth')
            ->causedBy($this)
            ->performedOn($this)
            ->withProperties([
                'ip'           => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'locked_until' => $lockedUntil->toDateTimeString(),
            ])
            ->log('Account locked after repeated failed attempts');
    }

    public function clearLockout(): void
    {
        $this->update([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_failed_at' => null,
        ]);
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }
}
