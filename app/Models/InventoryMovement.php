<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'movement_type', 'quantity_delta', 'quantity_before',
        'quantity_after', 'unit_cost', 'reference_type', 'reference_id',
        'created_by', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'created_at' => 'datetime'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
