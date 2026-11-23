<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Provides filtered member directory queries with primary and upcoming subscription snapshots for printing and reporting.
 */
class MemberDirectoryService
{
    public const MAX_PRINT_RECORDS = 2000;

    /**
     * Fetch filtered members with their primary/latest and upcoming subscriptions plus summary counts.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     members: Collection<int, Member>,
     *     summary: array{
     *         total_matched: int,
     *         printed_count: int,
     *         truncated: bool,
     *         active_members: int,
     *         suspended_members: int,
     *         archived_members: int,
     *         active_subscriptions: int,
     *         expiring_soon_subscriptions: int,
     *         overdue_subscriptions: int,
     *         expired_subscriptions: int,
     *         no_subscription_members: int
     *     },
     *     package_name: string|null
     * }
     */
    public function forPrint(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $period = (string) ($filters['period'] ?? 'all');
        $packageId = (string) ($filters['package_id'] ?? 'all');
        $subscriptionStatus = (string) ($filters['subscription_status'] ?? 'all');
        $sort = (string) ($filters['sort'] ?? 'latest');

        $today = now('Asia/Aden')->toDateString();

        $members = DB::connection()->getDriverName() === 'pgsql'
            ? $this->fetchForPostgres($search, $status, $period, $packageId, $subscriptionStatus, $sort, $today)
            : $this->fetchWithoutLateralJoins($search, $status, $period, $packageId, $subscriptionStatus, $sort, $today);

        $totalMatched = $members->count();
        $truncated = $totalMatched > self::MAX_PRINT_RECORDS;
        $printedMembers = $truncated ? $members->take(self::MAX_PRINT_RECORDS)->values() : $members;

        $summary = [
            'total_matched' => $totalMatched,
            'printed_count' => $printedMembers->count(),
            'truncated' => $truncated,
            'active_members' => $members->where('status', 'active')->count(),
            'suspended_members' => $members->where('status', 'suspended')->count(),
            'archived_members' => $members->where('status', 'archived')->count(),
            'active_subscriptions' => $members->filter(fn (Member $m): bool => $m->latestSubscription?->status === 'active')->count(),
            'expiring_soon_subscriptions' => $members->filter(fn (Member $m): bool => $m->latestSubscription?->status === 'expiring_soon')->count(),
            'overdue_subscriptions' => $members->filter(fn (Member $m): bool => $m->latestSubscription?->status === 'financial_overdue')->count(),
            'expired_subscriptions' => $members->filter(fn (Member $m): bool => $m->latestSubscription?->status === 'expired')->count(),
            'no_subscription_members' => $members->filter(fn (Member $m): bool => $m->latestSubscription === null)->count(),
        ];

        $packageName = $packageId !== 'all'
            ? Package::query()->whereKey((int) $packageId)->value('name')
            : null;

        return [
            'members' => $printedMembers,
            'summary' => $summary,
            'package_name' => $packageName,
        ];
    }

    /** @return Collection<int, Member> */
    private function fetchForPostgres(
        string $search,
        string $status,
        string $period,
        string $packageId,
        string $subscriptionStatus,
        string $sort,
        string $today,
    ): Collection {
        $subscriptionColumns = [
            'subscriptions.id', 'subscriptions.member_id', 'subscriptions.package_id',
            'subscriptions.package_name_snapshot', 'subscriptions.duration_value_snapshot',
            'subscriptions.duration_unit_snapshot', 'subscriptions.start_date',
            'subscriptions.end_date', 'subscriptions.status', 'subscriptions.currency',
            'subscriptions.final_price', 'subscriptions.payment_plan',
        ];

        $primarySubscription = DB::table('subscriptions')
            ->select($subscriptionColumns)
            ->whereColumn('subscriptions.member_id', 'members.id')
            ->orderByRaw(
                "case
                    when subscriptions.status in ('active', 'financial_overdue', 'expiring_soon')
                        and subscriptions.start_date <= ? and subscriptions.end_date >= ? then 0
                    when subscriptions.status = 'upcoming' and subscriptions.start_date >= ? then 1
                    when subscriptions.status in ('active', 'financial_overdue', 'expiring_soon') then 2
                    when subscriptions.status = 'upcoming' then 3
                    else 4
                end",
                [$today, $today, $today]
            )
            ->orderByRaw("case when subscriptions.status = 'upcoming' then subscriptions.start_date end asc nulls last")
            ->latest('subscriptions.end_date')
            ->latest('subscriptions.id')
            ->limit(1);

        $upcomingSubscription = DB::table('subscriptions')
            ->select($subscriptionColumns)
            ->whereColumn('subscriptions.member_id', 'members.id')
            ->where('subscriptions.status', 'upcoming')
            ->whereDate('subscriptions.start_date', '>=', $today)
            ->oldest('subscriptions.start_date')
            ->oldest('subscriptions.id')
            ->limit(1);

        $query = Member::query()
            ->leftJoinLateral($primarySubscription, 'primary_subscription')
            ->leftJoinLateral($upcomingSubscription, 'upcoming_subscription')
            ->select([
                'members.id', 'members.membership_code', 'members.full_name', 'members.phone',
                'members.gender', 'members.assigned_period', 'members.registration_date',
                'members.status',
            ])
            ->addSelect($this->lateralSelectColumns('primary_subscription'))
            ->addSelect($this->lateralSelectColumns('upcoming_subscription'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('members.full_name', 'ilike', '%'.$search.'%')
                ->orWhere('members.phone', 'ilike', '%'.$search.'%')
                ->orWhere('members.membership_code', 'ilike', '%'.$search.'%')
            ))
            ->when($status !== 'all', fn ($q) => $q->where('members.status', $status))
            ->when($period !== 'all', fn ($q) => $q->where('members.assigned_period', $period))
            ->when($subscriptionStatus === 'none', fn ($q) => $q->whereNull('primary_subscription.id'))
            ->when(
                $subscriptionStatus !== 'all' && $subscriptionStatus !== 'none',
                fn ($q) => $q->where('primary_subscription.status', $subscriptionStatus)
            )
            ->when(
                $packageId !== 'all',
                fn ($q) => $q->where('primary_subscription.package_id', (int) $packageId)
            );

        $this->applyPostgresSort($query, $sort);

        $members = $query->limit(self::MAX_PRINT_RECORDS + 1)->get();

        $members->each(function (Member $member): void {
            $primary = $this->subscriptionFromJoinedColumns($member, 'primary_subscription');
            $upcoming = $this->subscriptionFromJoinedColumns($member, 'upcoming_subscription');

            if ($upcoming?->id === $primary?->id) {
                $upcoming = null;
            }

            $member->setRelation('latestSubscription', $primary);
            $member->setRelation('upcomingSubscription', $upcoming);
        });

        return $members;
    }

    /** @return array<int, string> */
    private function lateralSelectColumns(string $prefix): array
    {
        return [
            "{$prefix}.id as {$prefix}_id",
            "{$prefix}.member_id as {$prefix}_member_id",
            "{$prefix}.package_id as {$prefix}_package_id",
            "{$prefix}.package_name_snapshot as {$prefix}_package_name",
            "{$prefix}.duration_value_snapshot as {$prefix}_duration_value",
            "{$prefix}.duration_unit_snapshot as {$prefix}_duration_unit",
            "{$prefix}.start_date as {$prefix}_start_date",
            "{$prefix}.end_date as {$prefix}_end_date",
            "{$prefix}.status as {$prefix}_status",
            "{$prefix}.currency as {$prefix}_currency",
            "{$prefix}.final_price as {$prefix}_final_price",
            "{$prefix}.payment_plan as {$prefix}_payment_plan",
        ];
    }

    /** @param Builder<Member> $query */
    private function applyPostgresSort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name' => $query->orderBy('members.full_name')->orderBy('members.id'),
            'end_date' => $query->orderByRaw('primary_subscription.end_date asc nulls last')->latest('members.id'),
            default => $query->latest('members.id'),
        };
    }

    /** @return Collection<int, Member> */
    private function fetchWithoutLateralJoins(
        string $search,
        string $status,
        string $period,
        string $packageId,
        string $subscriptionStatus,
        string $sort,
        string $today,
    ): Collection {
        $members = Member::query()
            ->with('subscriptions')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('full_name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('membership_code', 'like', '%'.$search.'%')
            ))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($period !== 'all', fn ($q) => $q->where('assigned_period', $period))
            ->latest('id')
            ->get();

        $priority = static function (Subscription $subscription) use ($today): int {
            if (
                in_array($subscription->status, ['active', 'financial_overdue', 'expiring_soon'], true)
                && $subscription->start_date->toDateString() <= $today
                && $subscription->end_date->toDateString() >= $today
            ) {
                return 0;
            }

            if ($subscription->status === 'upcoming' && $subscription->start_date->toDateString() >= $today) {
                return 1;
            }

            if (in_array($subscription->status, ['active', 'financial_overdue', 'expiring_soon'], true)) {
                return 2;
            }

            return $subscription->status === 'upcoming' ? 3 : 4;
        };

        $members->each(function (Member $member) use ($priority, $today): void {
            $ordered = $member->subscriptions->sort(function (Subscription $left, Subscription $right) use ($priority): int {
                return ($priority($left) <=> $priority($right))
                    ?: (($left->status === 'upcoming' ? $left->start_date->toDateString() : '9999-12-31') <=> ($right->status === 'upcoming' ? $right->start_date->toDateString() : '9999-12-31'))
                    ?: ($right->end_date->toDateString() <=> $left->end_date->toDateString())
                    ?: ($right->id <=> $left->id);
            })->values();

            $primary = $ordered->first();
            $upcoming = $member->subscriptions
                ->filter(fn (Subscription $s): bool => $s->status === 'upcoming' && $s->start_date->toDateString() >= $today)
                ->sortBy([['start_date', 'asc'], ['id', 'asc']])
                ->first();

            $member->setRelation('latestSubscription', $primary);
            $member->setRelation('upcomingSubscription', $upcoming?->id === $primary?->id ? null : $upcoming);
        });

        $filtered = $members->filter(function (Member $member) use ($packageId, $subscriptionStatus): bool {
            $primary = $member->latestSubscription;

            if ($subscriptionStatus === 'none') {
                return $primary === null;
            }

            if ($subscriptionStatus !== 'all' && $primary?->status !== $subscriptionStatus) {
                return false;
            }

            if ($packageId !== 'all' && (int) ($primary?->package_id ?? 0) !== (int) $packageId) {
                return false;
            }

            return true;
        });

        $sorted = match ($sort) {
            'name' => $filtered->sortBy('full_name')->values(),
            'end_date' => $filtered->sortBy(fn (Member $m): string => $m->latestSubscription?->end_date->toDateString() ?? '9999-12-31')->values(),
            default => $filtered->sortByDesc('id')->values(),
        };

        return $sorted->take(self::MAX_PRINT_RECORDS + 1);
    }

    private function subscriptionFromJoinedColumns(Member $member, string $prefix): ?Subscription
    {
        $id = $member->getAttribute($prefix.'_id');

        if (! $id) {
            return null;
        }

        return (new Subscription)->newFromBuilder([
            'id' => $id,
            'member_id' => $member->getAttribute($prefix.'_member_id'),
            'package_id' => $member->getAttribute($prefix.'_package_id'),
            'package_name_snapshot' => $member->getAttribute($prefix.'_package_name'),
            'duration_value_snapshot' => $member->getAttribute($prefix.'_duration_value'),
            'duration_unit_snapshot' => $member->getAttribute($prefix.'_duration_unit'),
            'start_date' => $member->getAttribute($prefix.'_start_date'),
            'end_date' => $member->getAttribute($prefix.'_end_date'),
            'status' => $member->getAttribute($prefix.'_status'),
            'currency' => $member->getAttribute($prefix.'_currency'),
            'final_price' => $member->getAttribute($prefix.'_final_price'),
            'payment_plan' => $member->getAttribute($prefix.'_payment_plan'),
        ]);
    }
}
