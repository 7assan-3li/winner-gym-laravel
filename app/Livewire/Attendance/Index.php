<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الحضور - WINNER GYM')]
class Index extends Component
{
    use WithPagination;

    public string $identifier = '';

    public string $date = '';

    public string $periodFilter = 'all';

    public string $methodFilter = 'all';

    public ?int $selectedAttendanceId = null;

    public function mount(PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'attendance.view'), 403);

        $this->date = now('Asia/Aden')->toDateString();
    }

    public function updatedDate(): void
    {
        $this->resetPage();
        $this->selectedAttendanceId = null;
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
    }

    public function record(AttendanceService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'attendance.record'), 403);

        $validated = $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'أدخل كود العضوية أو رقم الهاتف أو امسح الكود أولاً.',
        ]);

        $identifier = trim($validated['identifier']);
        $method = $this->detectMethod($identifier);
        $attendance = $service->record($method, $identifier, auth()->user());

        $this->selectedAttendanceId = $attendance->id;
        $this->identifier = '';
        $this->date = now('Asia/Aden')->toDateString();
        $this->resetPage();

        session()->flash('success', 'تم تسجيل حضور '.$attendance->member->full_name.' بنجاح.');
        $this->dispatch('attendance-recorded');
    }

    public function selectAttendance(int $attendanceId): void
    {
        $this->selectedAttendanceId = $attendanceId;
    }

    public function clearSelected(): void
    {
        $this->selectedAttendanceId = null;
        $this->identifier = '';
        $this->dispatch('attendance-focus');
    }

    private function detectMethod(string $identifier): string
    {
        if (str_starts_with(strtolower($identifier), 'winner-gym:')) {
            return 'qr';
        }

        if (preg_match('/^WG-[A-Z0-9-]+$/i', $identifier)) {
            return 'membership_code';
        }

        if (preg_match('/^[+0-9\s-]{7,}$/', $identifier)) {
            return 'phone';
        }

        if (str_contains($identifier, ' ')) {
            return 'name';
        }

        return 'barcode';
    }

    public function render(): View
    {
        $date = CarbonImmutable::parse($this->date ?: now('Asia/Aden')->toDateString(), 'Asia/Aden')->toDateString();
        $dayStartUtc = CarbonImmutable::parse($date, 'Asia/Aden')->startOfDay()->utc();
        $dayEndUtc = CarbonImmutable::parse($date, 'Asia/Aden')->addDay()->startOfDay()->utc();

        $stats = DB::selectOne(
            <<<'SQL'
            select
                count(*)::int as total,
                count(*) filter (where m.assigned_period = 'men')::int as men_count,
                count(*) filter (where m.assigned_period = 'women')::int as women_count
            from attendances a
            join members m on m.id = a.member_id
            where a.attendance_date = ?
            SQL,
            [$date]
        );

        $rejectedCount = (int) DB::table('attendance_attempts')
            ->where('allowed', false)
            ->where('attempted_at', '>=', $dayStartUtc)
            ->where('attempted_at', '<', $dayEndUtc)
            ->count();

        $query = Attendance::query()
            ->with(['member', 'subscription'])
            ->whereDate('attendance_date', $date);

        if (in_array($this->periodFilter, ['men', 'women'], true)) {
            $query->whereHas('member', fn ($q) => $q->where('assigned_period', $this->periodFilter));
        }

        if (in_array($this->methodFilter, ['name', 'phone', 'membership_code', 'barcode', 'qr'], true)) {
            $query->where('method', $this->methodFilter);
        }

        $rows = $query
            ->orderByDesc('entered_at')
            ->paginate(8);

        $selected = null;
        if ($this->selectedAttendanceId) {
            $selected = $rows->getCollection()->firstWhere('id', $this->selectedAttendanceId);
            if (! $selected) {
                $selected = Attendance::with(['member', 'subscription'])->find($this->selectedAttendanceId);
            }
        }

        $selected ??= $rows->getCollection()->first();

        return view('livewire.attendance.index', [
            'rows' => $rows,
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'men' => (int) ($stats->men_count ?? 0),
                'women' => (int) ($stats->women_count ?? 0),
                'rejected' => $rejectedCount,
            ],
            'selected' => $selected,
            'selectedMember' => $selected?->member,
            'selectedSubscription' => $selected?->subscription,
            'selectedRemainingDays' => $selected?->subscription
                ? max(0, (int) now('Asia/Aden')->startOfDay()->diffInDays($selected->subscription->end_date->copy()->startOfDay(), false))
                : null,
        ]);
    }
}
