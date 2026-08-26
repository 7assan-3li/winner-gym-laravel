<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;

class PermissionService
{
    public function allows(User $user, string $ability): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'owner') {
            return true;
        }

        $userOverride = UserPermission::query()
            ->where('user_id', $user->id)
            ->where('ability', $ability)
            ->value('allowed');

        if ($userOverride !== null) {
            return (bool) $userOverride;
        }

        return (bool) RolePermission::query()
            ->where('role', $user->role)
            ->where('ability', $ability)
            ->value('allowed');
    }
}
