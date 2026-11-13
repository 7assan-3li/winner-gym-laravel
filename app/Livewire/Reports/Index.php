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

    public bool $showShiftModal = false;

    public ?int $modalCashierId = null;

    public function openShiftDetails(int $cashierId): void
    {
        $this->modalCashierId = $cashierId;
        $this->shiftCashierId = $cashierId;
        $this->showShiftModal = true;
    }

    public function closeShiftDetails(): void
    {
        $this->showShiftModal = false;
        $this->modalCashierId = null;
    }

    public function printCashierShift(int $cashierId): void
    {
        $this->shiftCashierId = $cashierId;
        $this->showShiftModal = false;
        $this->dispatch('trigger-print');
    }

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

        $data = $this->tab === 'summary'
            ? $reports->summary($this->from, $this->to, $this->gender, $this->currency)
            : $this->emptySummaryData();

        $shiftData = $this->tab === 'shifts'
            ? $reports->cashierShiftSummary($this->shiftCashierId, $this->shiftDate, $this->shiftCurrency)
            : $this->emptyShiftData();

        $modalShiftData = ($this->showShiftModal && $this->modalCashierId)
            ? $reports->cashierShiftSummary($this->modalCashierId, $this->shiftDate, $this->shiftCurrency)
            : null;

        return view('livewire.reports.index', [
            'data' => $data,
            'shiftData' => $shiftData,
            'modalShiftData' => $modalShiftData,
            'staffList' => $staffList,
        ]);
    }

    /** @return array<string, mixed> */
    private function emptySummaryData(): array
    {
        return [
            'currency' => $this->currency,
            'subscription_gross_revenue' => 0.0,
            'subscription_refunds' => 0.0,
            'subscription_revenue' => 0.0,
            'nutrition_revenue' => 0.0,
            'product_revenue' => 0.0,
            'product_cogs' => 0.0,
            'product_profit' => 0.0,
            'expenses' => 0.0,
            'net' => 0.0,
            'attendance_count' => 0,
            'appointments_count' => 0,
            'new_members_count' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyShiftData(): array
    {
        return [
            'currency' => $this->shiftCurrency,
            'date' => $this->shiftDate,
            'cashier_id' => $this->shiftCashierId,
            'cashier_name' => '',
            'subscription_cash' => 0.0,
            'subscription_transfer' => 0.0,
            'nutrition_cash' => 0.0,
            'nutrition_transfer' => 0.0,
            'sales_cash' => 0.0,
            'sales_transfer' => 0.0,
            'cash_expenses' => 0.0,
            'total_cash_in' => 0.0,
            'total_transfer_in' => 0.0,
            'total_collected' => 0.0,
            'net_cash_handover' => 0.0,
            'transactions_count' => 0,
            'transactions' => [],
            'comparison' => [
                'cash_diff' => 0.0,
                'transfer_diff' => 0.0,
                'net_diff' => 0.0,
            ],
        ];
    }
}
