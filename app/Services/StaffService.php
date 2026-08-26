<?php

namespace App\Services;

use App\Models\GymPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): User
    {
        abort_unless($actor->role === 'owner', 403);

        $username = Str::lower(trim($data['username']));
        if (User::whereRaw('LOWER(username) = ?', [$username])->exists()) {
            throw ValidationException::withMessages(['username' => 'اسم المستخدم مستخدم مسبقًا.']);
        }

        $workPeriod = $this->resolveWorkPeriod($data);

        $user = User::create([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'] ?: null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'work_period' => $workPeriod,
            'branch_id' => $data['branch_id'] ?: null,
            'gym_period_id' => $data['gym_period_id'] ?: null,
            'is_active' => true,
            'created_by' => $actor->id,
            'must_change_password' => true,
        ]);

        $this->audit->log($actor, 'security', 'staff.created', $user);

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function update(User $staff, array $data, User $actor): User
    {
        abort_unless($actor->role === 'owner', 403);
        abort_if($staff->role === 'owner' && $staff->id !== $actor->id, 403);

        $username = Str::lower(trim($data['username']));
        if (User::whereRaw('LOWER(username) = ?', [$username])->whereKeyNot($staff->id)->exists()) {
            throw ValidationException::withMessages(['username' => 'اسم المستخدم مستخدم مسبقًا.']);
        }

        $old = $staff->only(['name', 'username', 'email', 'role', 'work_period', 'branch_id', 'gym_period_id', 'is_active']);
        $staff->update([
            'name' => $data['name'],
            'username' => $username,
            'email' => $data['email'] ?: null,
            'role' => $staff->role === 'owner' ? 'owner' : $data['role'],
            'work_period' => $staff->role === 'owner' ? 'both' : $this->resolveWorkPeriod($data),
            'branch_id' => $data['branch_id'] ?: null,
            'gym_period_id' => $data['gym_period_id'] ?: null,
        ]);

        $this->audit->log($actor, 'security', 'staff.updated', $staff, $old, $staff->only(array_keys($old)));

        return $staff;
    }

    public function toggle(User $staff, User $actor): void
    {
        abort_unless($actor->role === 'owner', 403);
        if ($staff->id === $actor->id && $staff->is_active) {
            throw ValidationException::withMessages(['staff' => 'لا يمكنك تعطيل حسابك الحالي.']);
        }
        if ($staff->role === 'owner' && $staff->id !== $actor->id) {
            throw ValidationException::withMessages(['staff' => 'حساب المالك لا يمكن تعطيله من هنا.']);
        }

        $staff->update(['is_active' => ! $staff->is_active]);
        $this->audit->log($actor, 'security', $staff->is_active ? 'staff.activated' : 'staff.deactivated', $staff);
    }

    public function resetPassword(User $staff, string $password, User $actor): void
    {
        abort_unless($actor->role === 'owner', 403);

        $staff->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);
        $this->audit->log($actor, 'security', 'staff.password_reset', $staff);
    }

    /** @param array<string, mixed> $data */
    private function resolveWorkPeriod(array $data): string
    {
        if (! empty($data['gym_period_id'])) {
            $gender = GymPeriod::query()->whereKey($data['gym_period_id'])->value('gender');
            if (in_array($gender, ['men', 'women'], true)) {
                return $gender;
            }
        }

        return in_array(($data['work_period'] ?? 'both'), ['men', 'women', 'both'], true)
            ? $data['work_period']
            : 'both';
    }
}
