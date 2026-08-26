<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property int $duration_value
 * @property string $duration_unit
 * @property numeric-string|null $price_yer
 * @property numeric-string|null $price_sar
 * @property bool $is_active
 */
class Package extends Model
{
    protected $fillable = [
        'name', 'duration_value', 'duration_unit', 'price_yer', 'price_sar',
        'is_active', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_yer' => 'decimal:2',
            'price_sar' => 'decimal:2',
        ];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
