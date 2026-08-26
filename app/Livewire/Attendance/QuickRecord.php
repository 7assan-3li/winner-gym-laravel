<?php

namespace App\Livewire\Attendance;

use App\Services\AttendanceService;
use App\Services\PermissionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class QuickRecord extends Component
{
    public string $identifier = '';

    /** @var array{member_name: string, membership_code: string, package: string, entered_at: string}|null */
    public ?array $result = null;

    public function record(AttendanceService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'attendance.record'), 403);

        $this->resetErrorBag();
        $this->result = null;

        $validated = $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'أدخل كود العضوية أو رقم الهاتف أو امسح الكود أولًا.',
        ]);

        $identifier = trim($validated['identifier']);
        $attendance = $service->record($this->detectMethod($identifier), $identifier, auth()->user());
        $attendance->load(['member:id,full_name,membership_code', 'subscription:id,package_name_snapshot,end_date']);

        $this->result = [
            'member_name' => $attendance->member->full_name,
            'membership_code' => $attendance->member->membership_code,
            'package' => $attendance->subscription->package_name_snapshot,
            'entered_at' => $attendance->entered_at->timezone('Asia/Aden')->format('h:i A'),
        ];
        $this->identifier = '';

        $this->dispatch('quick-attendance-recorded');
    }

    public function clear(): void
    {
        $this->identifier = '';
        $this->result = null;
        $this->resetErrorBag();
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
        return view('livewire.attendance.quick-record');
    }
}
