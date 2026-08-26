<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Setting::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public function put(string $key, mixed $value, ?User $actor = null, string $group = 'general'): Setting
    {
        return Setting::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value, 'updated_by' => $actor?->id],
        );
    }
}
