<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'original_unit_price', 'actual_unit_price',
        'unit_cost', 'line_total', 'price_overridden', 'price_overridden_by',
        'price_overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'original_unit_price' => 'decimal:2', 'actual_unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2', 'line_total' => 'decimal:2',
            'price_overridden' => 'boolean', 'price_overridden_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
