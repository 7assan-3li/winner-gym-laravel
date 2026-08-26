<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $sold_at
 * @property Carbon|null $cancelled_at
 */
class Sale extends Model
{
    protected $fillable = [
        'sale_number', 'member_id', 'customer_name', 'currency', 'subtotal',
        'discount_type', 'discount_value', 'discount_amount', 'total_amount',
        'payment_method', 'transfer_service', 'transfer_reference', 'proof_path', 'status', 'sold_at',
        'created_by', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
            'sold_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
