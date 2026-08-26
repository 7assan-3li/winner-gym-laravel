<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function __construct(private AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Member
    {
        if (($data['birth_date'] ?? null) === null && ($data['age'] ?? null) === null) {
            throw ValidationException::withMessages([
                'birth_date' => 'يجب إدخال تاريخ الميلاد أو العمر.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor) {
            $member = Member::create($data + [
                'created_by' => $actor->id,
                'status' => $data['status'] ?? 'active',
            ]);

            $this->audit->log($actor, 'member', 'member.created', $member, null, $member->toArray());
            $this->forgetMembersCounts();

            return $member;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Member $member, array $data, User $actor): Member
    {
        if (($data['birth_date'] ?? null) === null && ($data['age'] ?? null) === null) {
            throw ValidationException::withMessages([
                'edit_birth_date' => 'يجب إدخال تاريخ الميلاد أو العمر.',
            ]);
        }

        return DB::transaction(function () use ($member, $data, $actor) {
            $old = $member->toArray();
            $member->update($data);
            $fresh = $member->fresh();
            $this->audit->log($actor, 'member', 'member.updated', $fresh, $old, $fresh->toArray());

            return $fresh;
        });
    }

    public function suspend(Member $member, string $reason, User $actor): Member
    {
        return DB::transaction(function () use ($member, $reason, $actor) {
            $old = $member->toArray();
            $member->update(['status' => 'suspended']);
            $fresh = $member->fresh();
            $this->audit->log($actor, 'member', 'member.suspended', $fresh, $old, [
                ...$fresh->toArray(),
                'suspension_reason' => $reason,
            ]);
            $this->forgetMembersCounts();

            return $fresh;
        });
    }

    public function reactivate(Member $member, User $actor): Member
    {
        return DB::transaction(function () use ($member, $actor) {
            $old = $member->toArray();
            $member->update([
                'status' => 'active',
                'archived_at' => null,
                'archived_by' => null,
            ]);
            $fresh = $member->fresh();
            $this->audit->log($actor, 'member', 'member.reactivated', $fresh, $old, $fresh->toArray());
            $this->forgetMembersCounts();

            return $fresh;
        });
    }

    public function archive(Member $member, User $actor): Member
    {
        return DB::transaction(function () use ($member, $actor) {
            $old = $member->toArray();

            $member->update([
                'status' => 'archived',
                'archived_at' => now(),
                'archived_by' => $actor->id,
            ]);

            $this->audit->log($actor, 'member', 'member.archived', $member, $old, $member->fresh()->toArray());
            $this->forgetMembersCounts();

            return $member->fresh();
        });
    }

    private function forgetMembersCounts(): void
    {
        Cache::store('file')->forget('winner-gym:members:counts');
        Cache::store('file')->forget('winner-gym:members:counts:v2');
        Cache::store('file')->forget('winner-gym:members:counts:v3');
    }
}
