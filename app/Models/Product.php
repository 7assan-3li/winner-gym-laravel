<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'image_path', 'barcode', 'purchase_cost', 'selling_price',
        'currency', 'current_quantity', 'minimum_quantity', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['purchase_cost' => 'decimal:2', 'selling_price' => 'decimal:2'];
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
