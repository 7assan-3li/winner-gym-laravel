<?php

namespace App\Livewire\Reports;

use App\Models\User;
use App\Services\ReportService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('التقارير وتسوية الورديات - WINNER GYM')]
class Index extends Component
{
    public string $tab = 'shifts';

    public string $from = '';

    public string $to = '';

    public string $gender = 'all';

    public string $currency = 'YER';

    public string $shiftDate = '';

    public ?int $shiftCashierId = null;

    public string $shiftCurrency = 'YER';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->role === 'owner'
            || $user?->hasGymPermission('reports.operational')
            || $user?->hasGymPermission('reports.finance'),
            403
        );

        $this->from = now('Asia/Aden')->startOfMonth()->toDateString();
        $this->to = now('Asia/Aden')->toDateString();
        $this->shiftDate = now('Asia/Aden')->toDateString();

        if (! in_array($user->role, ['owner', 'manager'], true)) {
            $this->shiftCashierId = $user->id;
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['shifts', 'summary'], true) ? $tab : 'shifts';
    }

    public function render(ReportService $reports): View
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'gender' => ['required', 'in:all,male,female'],
            'currency' => ['required', 'in:YER,SAR'],
            'shiftDate' => ['required', 'date'],
            'shiftCashierId' => ['nullable', 'integer', 'exists:users,id'],
            'shiftCurrency' => ['required', 'in:YER,SAR'],
        ]);

        $staffList = User::query()->where('is_active', true)->orderBy('name')->get();

        return view('livewire.reports.index', [
            'data' => $reports->summary($this->from, $this->to, $this->gender, $this->currency),
            'shiftData' => $reports->cashierShiftSummary($this->shiftCashierId, $this->shiftDate, $this->shiftCurrency),
            'staffList' => $staffList,
        ]);
    }
}
