<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property Carbon|null $birth_date
 * @property Carbon $registration_date
 * @property Carbon|null $archived_at
 * @property string $assigned_period
 * @property string $status
 */
class Member extends Model
{
    protected $fillable = [
        'full_name', 'phone', 'gender', 'birth_date', 'age', 'assigned_period',
        'registration_date', 'status', 'address', 'identity_number',
        'identity_image_path', 'profile_image_path', 'notes', 'created_by',
        'archived_at', 'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'registration_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            $member->membership_code ??= static::generateUniqueMembershipCode();
            $member->barcode_value ??= $member->membership_code;
            $member->qr_value ??= 'winner-gym:'.$member->membership_code;
            $member->registration_date ??= now('Asia/Aden')->startOfDay();
        });
    }

    protected static function generateUniqueMembershipCode(): string
    {
        do {
            $code = 'WG-'.Str::upper(Str::random(6));
        } while (static::query()->where('membership_code', $code)->exists());

        return $code;
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasOne<Subscription, $this> */
    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('end_date');
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<Measurement, $this> */
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
