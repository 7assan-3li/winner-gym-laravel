<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementType extends Model
{
    protected $fillable = ['code', 'name_ar', 'unit', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
