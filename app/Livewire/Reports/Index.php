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

    public ?int $summaryCashierId = null;

    public string $summaryPaymentChannel = 'all';

    public string $shiftDate = '';

    public ?int $shiftCashierId = null;

    public string $shiftCurrency = 'YER';

    public string $shiftPaymentChannel = 'all';

    public string $shiftTransactionType = 'all';

    public string $shiftSearch = '';

    public bool $showShiftModal = false;

    public ?int $modalCashierId = null;

    public function openShiftDetails(int $cashierId): void
    {
        $this->modalCashierId = $cashierId;
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

    public function resetShiftFilters(): void
    {
        $this->shiftPaymentChannel = 'all';
        $this->shiftTransactionType = 'all';
        $this->shiftSearch = '';
        if (in_array(auth()->user()?->role, ['owner', 'manager'], true)) {
            $this->shiftCashierId = null;
        }
    }

    public function applyDatePreset(string $preset): void
    {
        $now = now('Asia/Aden');

        match ($preset) {
            'today' => [$this->from, $this->to] = [$now->toDateString(), $now->toDateString()],
            'yesterday' => [$this->from, $this->to] = [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
            'this_week' => [$this->from, $this->to] = [$now->copy()->startOfWeek()->toDateString(), $now->toDateString()],
            'this_month' => [$this->from, $this->to] = [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()],
            'last_month' => [$this->from, $this->to] = [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_year' => [$this->from, $this->to] = [$now->copy()->startOfYear()->toDateString(), $now->toDateString()],
            default => null,
        };
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
            'currency' => ['required', 'in:YER,SAR,ALL'],
            'summaryCashierId' => ['nullable', 'integer', 'exists:users,id'],
            'summaryPaymentChannel' => ['required', 'string', 'max:80'],
            'shiftDate' => ['required', 'date'],
            'shiftCashierId' => ['nullable', 'integer', 'exists:users,id'],
            'shiftCurrency' => ['required', 'in:YER,SAR,ALL'],
            'shiftPaymentChannel' => ['required', 'string', 'max:80'],
            'shiftTransactionType' => ['required', 'in:all,subscription,sale,nutrition,expense'],
            'shiftSearch' => ['nullable', 'string', 'max:100'],
        ]);

        $staffList = User::query()->where('is_active', true)->orderBy('name')->get();
        $transferServices = $reports->availableTransferServices();

        $data = $this->tab === 'summary'
            ? $reports->summary(
                $this->from,
                $this->to,
                $this->gender,
                $this->currency,
                $this->summaryCashierId,
                $this->summaryPaymentChannel
            )
            : $this->emptySummaryData();

        $shiftData = $this->tab === 'shifts'
            ? $reports->cashierShiftSummary(
                $this->shiftCashierId,
                $this->shiftDate,
                $this->shiftCurrency,
                $this->shiftPaymentChannel,
                $this->shiftTransactionType,
                $this->shiftSearch
            )
            : $this->emptyShiftData();

        $modalShiftData = ($this->showShiftModal && $this->modalCashierId)
            ? $reports->cashierShiftSummary(
                $this->modalCashierId,
                $this->shiftDate,
                $this->shiftCurrency,
                $this->shiftPaymentChannel,
                $this->shiftTransactionType,
                $this->shiftSearch
            )
            : null;

        return view('livewire.reports.index', [
            'data' => $data,
            'shiftData' => $shiftData,
            'modalShiftData' => $modalShiftData,
            'staffList' => $staffList,
            'transferServices' => $transferServices,
        ]);
    }

    /** @return array<string, mixed> */
    private function emptySummaryData(): array
    {
        return [
            'is_all_currencies' => $this->currency === 'ALL',
            'currency' => $this->currency,
            'payment_channel' => $this->summaryPaymentChannel,
            'payment_channel_label' => 'الكل (نقدي + جميع الصرافات)',
            'exchange_breakdown' => [],
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
            'yer' => [],
            'sar' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function emptyShiftData(): array
    {
        return [
            'is_all_currencies' => $this->shiftCurrency === 'ALL',
            'currency' => $this->shiftCurrency,
            'date' => $this->shiftDate,
            'cashier_id' => $this->shiftCashierId,
            'cashier_name' => '',
            'payment_channel' => $this->shiftPaymentChannel,
            'payment_channel_label' => 'الكل (نقدي + جميع الصرافات)',
            'transaction_type' => $this->shiftTransactionType,
            'search' => $this->shiftSearch,
            'subscription_cash' => 0.0,
            'subscription_transfer' => 0.0,
            'nutrition_cash' => 0.0,
            'nutrition_transfer' => 0.0,
            'sales_cash' => 0.0,
            'sales_transfer' => 0.0,
            'cash_expenses' => 0.0,
            'transfer_expenses' => 0.0,
            'total_cash_in' => 0.0,
            'total_transfer_in' => 0.0,
            'total_collected' => 0.0,
            'net_cash_handover' => 0.0,
            'exchange_breakdown' => [],
            'yer' => [],
            'sar' => [],
            'transactions_count' => 0,
            'transactions' => [],
            'comparison' => [],
        ];
    }
}
