<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutritionClient extends Model
{
    protected $fillable = ['full_name', 'phone', 'gender', 'notes', 'created_by'];

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<Measurement, $this> */
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }
}
