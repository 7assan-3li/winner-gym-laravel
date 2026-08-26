<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $expense_date
 * @property Carbon|null $approved_at
 * @property Carbon|null $cancelled_at
 */
class Expense extends Model
{
    protected $fillable = [
        'category_id', 'title', 'amount', 'currency', 'expense_date', 'payment_method',
        'transfer_service', 'transfer_reference', 'receipt_path', 'notes', 'status', 'created_by',
        'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'expense_date' => 'date',
            'approved_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ExpenseCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
