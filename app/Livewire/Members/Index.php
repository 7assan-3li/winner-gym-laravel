<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use App\Services\MembershipService;
use App\Services\PermissionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الأعضاء - WINNER GYM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status_filter = 'all';

    public string $subscription_status_filter = 'all';

    public string $package_filter = 'all';

    public string $full_name = '';

    public string $phone = '';

    public string $gender = 'male';

    public string $assigned_period = 'men';

    public string $address = '';

    public string $identity_number = '';

    public string $notes = '';

    public ?string $birth_date = null;

    public ?int $age = null;

    public ?int $editing_id = null;

    public string $edit_full_name = '';

    public string $edit_phone = '';

    public string $edit_gender = 'male';

    public string $edit_assigned_period = 'men';

    public string $edit_address = '';

    public string $edit_identity_number = '';

    public string $edit_notes = '';

    public ?string $edit_birth_date = null;

    public ?int $edit_age = null;

    public ?int $view_member_id = null;

    public ?int $action_member_id = null;

    public string $suspension_reason = '';

    public string $suspension_notes = '';

    public function mount(PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.view') || $p->allows(auth()->user(), 'members.manage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSubscriptionStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPackageFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status_filter', 'subscription_status_filter', 'package_filter']);
        $this->status_filter = 'all';
        $this->subscription_status_filter = 'all';
        $this->package_filter = 'all';
        $this->resetPage();
    }

    public function create(MembershipService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.create') || $p->allows(auth()->user(), 'members.manage'), 403);

        $d = $this->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:members,phone'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'age' => ['nullable', 'integer', 'min:5', 'max:100'],
            'assigned_period' => ['required', Rule::in(['men', 'women'])],
            'address' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (($d['gender'] === 'male' && $d['assigned_period'] !== 'men') || ($d['gender'] === 'female' && $d['assigned_period'] !== 'women')) {
            $this->addError('assigned_period', 'الفترة لا تطابق جنس العضو.');

            return;
        }

        foreach (['birth_date', 'age', 'address', 'identity_number', 'notes'] as $k) {
            $d[$k] = $d[$k] ?: null;
        }

        $member = $service->create($d, auth()->user());
        $this->forgetMembersCache();
        $this->reset(['full_name', 'phone', 'birth_date', 'age', 'address', 'identity_number', 'notes']);
        $this->gender = 'male';
        $this->assigned_period = 'men';
        $this->dispatch('member-created');
        session()->flash('success', 'تم إنشاء العضو بنجاح: '.$member->membership_code);
    }

    public function startView(int $id): void
    {
        $this->view_member_id = $id;
        $this->dispatch('member-view-open');
    }

    public function startEdit(int $id): void
    {
        $m = Member::findOrFail($id);
        $this->editing_id = $m->id;
        $this->edit_full_name = $m->full_name;
        $this->edit_phone = $m->phone;
        $this->edit_gender = $m->gender;
        $this->edit_assigned_period = $m->assigned_period;
        $this->edit_address = (string) $m->address;
        $this->edit_identity_number = (string) $m->identity_number;
        $this->edit_notes = (string) $m->notes;
        $this->edit_birth_date = $m->birth_date?->format('Y-m-d');
        $this->edit_age = $m->age;
        $this->dispatch('member-edit-open');
    }

    public function updateMember(MembershipService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.update') || $p->allows(auth()->user(), 'members.manage'), 403);
        $member = Member::findOrFail($this->editing_id);

        $d = $this->validate([
            'edit_full_name' => ['required', 'string', 'max:255'],
            'edit_phone' => ['required', 'string', 'max:30', Rule::unique('members', 'phone')->ignore($member->id)],
            'edit_gender' => ['required', Rule::in(['male', 'female'])],
            'edit_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'edit_age' => ['nullable', 'integer', 'min:5', 'max:100'],
            'edit_assigned_period' => ['required', Rule::in(['men', 'women'])],
            'edit_address' => ['nullable', 'string', 'max:255'],
            'edit_identity_number' => ['nullable', 'string', 'max:100'],
            'edit_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (($d['edit_gender'] === 'male' && $d['edit_assigned_period'] !== 'men') || ($d['edit_gender'] === 'female' && $d['edit_assigned_period'] !== 'women')) {
            $this->addError('edit_assigned_period', 'الفترة لا تطابق جنس العضو.');

            return;
        }

        $service->update($member, [
            'full_name' => $d['edit_full_name'],
            'phone' => $d['edit_phone'],
            'gender' => $d['edit_gender'],
            'birth_date' => $d['edit_birth_date'] ?: null,
            'age' => $d['edit_age'] ?: null,
            'assigned_period' => $d['edit_assigned_period'],
            'address' => $d['edit_address'] ?: null,
            'identity_number' => $d['edit_identity_number'] ?: null,
            'notes' => $d['edit_notes'] ?: null,
        ], auth()->user());

        $this->forgetMembersCache();
        $this->dispatch('member-edit-close');
        session()->flash('success', 'تم حفظ تعديلات العضو.');
    }

    public function prepareAction(int $id): void
    {
        $this->action_member_id = $id;
        $this->suspension_reason = '';
        $this->suspension_notes = '';
    }

    public function suspend(MembershipService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.update') || $p->allows(auth()->user(), 'members.manage'), 403);
        $d = $this->validate([
            'suspension_reason' => ['required', Rule::in(['طلب العضو', 'قرار إداري', 'مخالفة أنظمة النادي', 'أخرى'])],
            'suspension_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $d['suspension_reason'];
        if (! empty(trim((string) ($d['suspension_notes'] ?? '')))) {
            $reason .= ' — '.trim($d['suspension_notes']);
        }

        $service->suspend(Member::findOrFail($this->action_member_id), $reason, auth()->user());
        $this->forgetMembersCache();
        $this->dispatch('member-suspend-close');
        session()->flash('success', 'تم تعليق العضو ومنع الدخول حتى إعادة التفعيل.');
    }

    public function archiveSelected(MembershipService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.update') || $p->allows(auth()->user(), 'members.manage'), 403);
        $service->archive(Member::findOrFail($this->action_member_id), auth()->user());
        $this->forgetMembersCache();
        $this->dispatch('member-archive-close');
        session()->flash('success', 'تمت أرشفة العضو بدون حذف أي سجل مرتبط به.');
    }

    public function reactivateSelected(MembershipService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'members.update') || $p->allows(auth()->user(), 'members.manage'), 403);
        $service->reactivate(Member::findOrFail($this->action_member_id), auth()->user());
        $this->forgetMembersCache();
        $this->dispatch('member-reactivate-close');
        session()->flash('success', 'تمت إعادة تفعيل العضو. لم يتم إنشاء أو تمديد أي اشتراك.');
    }

    public function render(): View
    {
        $counts = Cache::store('file')->remember(
            'winner-gym:members:counts:v3',
            now()->addHours(6),
            function (): array {
                $row = (array) DB::selectOne(
                    "select count(*) as total,
                        count(*) filter (where status='active') as active,
                        count(*) filter (where status='suspended') as suspended,
                        count(*) filter (where status='archived') as archived
                     from members"
                );

                return [
                    'total' => (int) $row['total'],
                    'active' => (int) $row['active'],
                    'suspended' => (int) $row['suspended'],
                    'archived' => (int) $row['archived'],
                ];
            }
        );

        $packageOptions = Cache::store('file')->remember(
            'winner-gym:members:packages:v2',
            now()->addHours(6),
            fn (): array => Package::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Package $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                ])
                ->all()
        );

        $search = trim($this->search);
        $hasSubscriptionFilter = $this->subscription_status_filter !== 'all'
            || $this->package_filter !== 'all';

        $today = now('Asia/Aden')->toDateString();
        $subscriptionColumns = [
            'subscriptions.id', 'subscriptions.member_id', 'subscriptions.package_id',
            'subscriptions.package_name_snapshot', 'subscriptions.duration_value_snapshot',
            'subscriptions.duration_unit_snapshot', 'subscriptions.start_date',
            'subscriptions.end_date', 'subscriptions.status',
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

        if (DB::connection()->getDriverName() !== 'pgsql') {
            return view('livewire.members.index', [
                'members' => $this->paginateMembersWithoutLateralJoins($search, $hasSubscriptionFilter, $today),
                'packages' => collect($packageOptions)->map(fn (array $package): object => (object) $package),
                'counts' => $counts,
            ]);
        }

        $page = $this->getPage();
        $perPage = 10;
        $memberItems = Member::query()
            ->leftJoinLateral($primarySubscription, 'primary_subscription')
            ->leftJoinLateral($upcomingSubscription, 'upcoming_subscription')
            ->select([
                'members.id', 'members.membership_code', 'members.full_name', 'members.phone',
                'members.gender', 'members.birth_date', 'members.age', 'members.assigned_period',
                'members.registration_date', 'members.status', 'members.address',
                'members.identity_number', 'members.notes',
            ])
            ->selectRaw('count(*) over() as filtered_total')
            ->addSelect([
                'primary_subscription.id as primary_subscription_id',
                'primary_subscription.member_id as primary_subscription_member_id',
                'primary_subscription.package_id as primary_subscription_package_id',
                'primary_subscription.package_name_snapshot as primary_subscription_package_name',
                'primary_subscription.duration_value_snapshot as primary_subscription_duration_value',
                'primary_subscription.duration_unit_snapshot as primary_subscription_duration_unit',
                'primary_subscription.start_date as primary_subscription_start_date',
                'primary_subscription.end_date as primary_subscription_end_date',
                'primary_subscription.status as primary_subscription_status',
                'upcoming_subscription.id as upcoming_subscription_id',
                'upcoming_subscription.member_id as upcoming_subscription_member_id',
                'upcoming_subscription.package_id as upcoming_subscription_package_id',
                'upcoming_subscription.package_name_snapshot as upcoming_subscription_package_name',
                'upcoming_subscription.duration_value_snapshot as upcoming_subscription_duration_value',
                'upcoming_subscription.duration_unit_snapshot as upcoming_subscription_duration_unit',
                'upcoming_subscription.start_date as upcoming_subscription_start_date',
                'upcoming_subscription.end_date as upcoming_subscription_end_date',
                'upcoming_subscription.status as upcoming_subscription_status',
            ])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('members.full_name', 'ilike', '%'.$search.'%')
                ->orWhere('members.phone', 'ilike', '%'.$search.'%')
                ->orWhere('members.membership_code', 'ilike', '%'.$search.'%')
            ))
            ->when($this->status_filter !== 'all', fn ($q) => $q->where('members.status', $this->status_filter))
            ->when($hasSubscriptionFilter, function ($query): void {
                $query->whereExists(function ($subscriptionQuery): void {
                    $subscriptionQuery
                        ->selectRaw('1')
                        ->from('subscriptions as filtered_subscriptions')
                        ->whereColumn('filtered_subscriptions.member_id', 'members.id')
                        ->when(
                            $this->subscription_status_filter !== 'all',
                            fn ($q) => $q->where('filtered_subscriptions.status', $this->subscription_status_filter)
                        )
                        ->when(
                            $this->package_filter !== 'all',
                            fn ($q) => $q->where('filtered_subscriptions.package_id', (int) $this->package_filter)
                        );
                });
            })
            ->latest('members.id')
            ->forPage($page, $perPage)
            ->get();

        $total = (int) data_get($memberItems->first(), 'filtered_total', 0);
        $memberItems->each(function (Member $member): void {
            $primarySubscription = $this->subscriptionFromJoinedColumns($member, 'primary_subscription');
            $upcomingSubscription = $this->subscriptionFromJoinedColumns($member, 'upcoming_subscription');

            if ($upcomingSubscription?->id === $primarySubscription?->id) {
                $upcomingSubscription = null;
            }

            $member->setRelation('latestSubscription', $primarySubscription);
            $member->setRelation('upcomingSubscription', $upcomingSubscription);
        });
        $members = new LengthAwarePaginator(
            $memberItems,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'page']
        );

        return view('livewire.members.index', [
            'members' => $members,
            'packages' => collect($packageOptions)->map(fn (array $package): object => (object) $package),
            'counts' => $counts,
        ]);
    }

    /** @return LengthAwarePaginator<int, Member> */
    private function paginateMembersWithoutLateralJoins(
        string $search,
        bool $hasSubscriptionFilter,
        string $today,
    ): LengthAwarePaginator {
        $page = $this->getPage();
        $perPage = 10;
        $query = Member::query()
            ->with('subscriptions')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('full_name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')
                ->orWhere('membership_code', 'like', '%'.$search.'%')
            ))
            ->when($this->status_filter !== 'all', fn ($q) => $q->where('status', $this->status_filter))
            ->when($hasSubscriptionFilter, function ($query): void {
                $query->whereHas('subscriptions', function ($subscriptionQuery): void {
                    $subscriptionQuery
                        ->when(
                            $this->subscription_status_filter !== 'all',
                            fn ($q) => $q->where('status', $this->subscription_status_filter)
                        )
                        ->when(
                            $this->package_filter !== 'all',
                            fn ($q) => $q->where('package_id', (int) $this->package_filter)
                        );
                });
            });

        $total = (clone $query)->count();
        $memberItems = $query->latest('id')->forPage($page, $perPage)->get();

        $memberItems->each(function (Member $member) use ($today): void {
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

            $ordered = $member->subscriptions->sort(function (Subscription $left, Subscription $right) use ($priority): int {
                return ($priority($left) <=> $priority($right))
                    ?: (($left->status === 'upcoming' ? $left->start_date->toDateString() : '9999-12-31') <=> ($right->status === 'upcoming' ? $right->start_date->toDateString() : '9999-12-31'))
                    ?: ($right->end_date->toDateString() <=> $left->end_date->toDateString())
                    ?: ($right->id <=> $left->id);
            })->values();

            $primary = $ordered->first();
            $upcoming = $member->subscriptions
                ->filter(fn (Subscription $subscription) => $subscription->status === 'upcoming' && $subscription->start_date->toDateString() >= $today)
                ->sortBy([['start_date', 'asc'], ['id', 'asc']])
                ->first();

            $member->setRelation('latestSubscription', $primary);
            $member->setRelation('upcomingSubscription', $upcoming?->id === $primary?->id ? null : $upcoming);
        });

        return new LengthAwarePaginator(
            $memberItems,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'page']
        );
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
        ]);
    }

    private function forgetMembersCache(): void
    {
        Cache::store('file')->forget('winner-gym:members:counts');
        Cache::store('file')->forget('winner-gym:members:counts:v2');
        Cache::store('file')->forget('winner-gym:members:counts:v3');
        Cache::store('file')->forget('winner-gym:members:packages');
        Cache::store('file')->forget('winner-gym:members:packages:v2');
    }
}
