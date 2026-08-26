<?php

namespace App\Models\Concerns;

use App\Services\PermissionService;

trait HasGymPermissions
{
    public function hasGymPermission(string $ability): bool
    {
        return app(PermissionService::class)->allows($this, $ability);
    }
}
