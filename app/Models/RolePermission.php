<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'ability', 'allowed'];

    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }
}
