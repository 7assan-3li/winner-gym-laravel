<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'updated_by'];

    protected static function booted(): void
    {
        static::saved(function (Setting $setting): void {
            Cache::forget('winner-gym:setting:'.$setting->key);
        });

        static::deleted(function (Setting $setting): void {
            Cache::forget('winner-gym:setting:'.$setting->key);
        });
    }

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
