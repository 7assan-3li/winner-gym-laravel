<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['measurement_id', 'measurement_type_id', 'value'];

    protected function casts(): array
    {
        return ['value' => 'decimal:3'];
    }

    /** @return BelongsTo<Measurement, $this> */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /** @return BelongsTo<MeasurementType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MeasurementType::class, 'measurement_type_id');
    }
}
