<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Measurement extends Model
{
    protected $fillable = [
        'appointment_id', 'member_id', 'nutrition_client_id',
        'nutritionist_id', 'bmi', 'notes', 'measured_at',
    ];

    protected function casts(): array
    {
        return ['bmi' => 'decimal:2', 'measured_at' => 'datetime'];
    }

    /** @return HasMany<MeasurementValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(MeasurementValue::class);
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<NutritionClient, $this> */
    public function nutritionClient(): BelongsTo
    {
        return $this->belongsTo(NutritionClient::class);
    }
}
