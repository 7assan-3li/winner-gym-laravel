<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['code', 'name', 'phone', 'address', 'manager_name', 'is_main', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_main' => 'boolean', 'is_active' => 'boolean'];
    }

    /** @return HasMany<GymPeriod, $this> */
    public function periods(): HasMany
    {
        return $this->hasMany(GymPeriod::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
