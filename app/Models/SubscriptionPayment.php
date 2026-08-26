<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $paid_at
 * @property Carbon|null $reversed_at
 * @property numeric-string $amount
 */
class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id', 'installment_id', 'amount', 'currency', 'payment_method',
        'transfer_service', 'transfer_reference', 'proof_path', 'receipt_number',
        'status', 'paid_at', 'created_by', 'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'reversed_at' => 'datetime'];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<SubscriptionInstallment, $this> */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInstallment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
