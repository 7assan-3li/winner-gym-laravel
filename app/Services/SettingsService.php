<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::remember(
            'winner-gym:setting:'.$key,
            now()->addMinutes(60),
            static fn () => [
                'val' => Setting::query()->where('key', $key)->value('value'),
            ]
        );

        return $cached['val'] ?? $default;
    }

    public function put(string $key, mixed $value, ?User $actor = null, string $group = 'general'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value, 'updated_by' => $actor?->id],
        );

        Cache::forget('winner-gym:setting:'.$key);

        return $setting;
    }
}
