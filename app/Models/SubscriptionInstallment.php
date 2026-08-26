<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $due_date
 * @property Carbon|null $paid_at
 * @property numeric-string $amount
 * @property string $status
 * @property-read Subscription $subscription
 */
class SubscriptionInstallment extends Model
{
    protected $fillable = [
        'subscription_id', 'installment_number', 'due_date', 'amount', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return HasMany<SubscriptionPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'installment_id');
    }
}
