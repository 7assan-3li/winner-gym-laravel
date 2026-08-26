<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $status
 * @property string $package_name_snapshot
 * @property string $currency
 * @property numeric-string $final_price
 * @property-read Member $member
 */
class Subscription extends Model
{
    protected $fillable = [
        'member_id', 'package_id', 'package_name_snapshot',
        'duration_value_snapshot', 'duration_unit_snapshot', 'period',
        'start_date', 'end_date', 'currency', 'price_snapshot',
        'discount_amount', 'final_price', 'payment_plan', 'installment_count',
        'status', 'notes', 'created_by', 'cancelled_at', 'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price_snapshot' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_price' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return HasMany<SubscriptionInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(SubscriptionInstallment::class);
    }

    /** @return HasMany<SubscriptionPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /** @return HasOne<SubscriptionRefund, $this> */
    public function refund(): HasOne
    {
        return $this->hasOne(SubscriptionRefund::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return numeric-string */
    public function paidAmount(): string
    {
        $amount = (string) $this->payments()->where('status', 'completed')->sum('amount');

        return $amount;
    }

    /** @return numeric-string */
    public function remainingAmount(): string
    {
        return bcsub((string) $this->final_price, $this->paidAmount(), 2);
    }
}
