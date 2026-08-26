<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $purchase_date
 * @property Carbon|null $approved_at
 * @property Carbon|null $cancelled_at
 */
class Purchase extends Model
{
    protected $fillable = [
        'purchase_number', 'purchase_date', 'supplier_name', 'supplier_invoice', 'currency',
        'payment_method', 'transfer_service', 'transfer_reference', 'proof_path', 'status', 'notes',
        'created_by', 'approved_by', 'approved_at', 'cancelled_by',
        'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return HasMany<PurchaseItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
