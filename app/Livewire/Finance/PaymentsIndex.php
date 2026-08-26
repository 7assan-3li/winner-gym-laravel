<?php

namespace App\Livewire\Finance;

use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\SubscriptionPayment;
use App\Services\PaymentPolicy;
use App\Services\PaymentService;
use App\Services\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المدفوعات - WINNER GYM')]
class PaymentsIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $filterCurrency = 'YER';

    public string $filterStatus = 'all';

    public string $filterMethod = 'all';

    public string $fromDate = '';

    public string $toDate = '';

    public bool $showInstallmentSelector = false;

    public string $installmentSearch = '';

    public bool $showPayModal = false;

    public ?int $installmentId = null;

    public string $amount = '';

    public string $currency = 'YER';

    public string $selectedMemberName = '';

    public string $payment_method = 'cash';

    public string $transfer_service = '';

    public string $transfer_reference = '';

    public ?TemporaryUploadedFile $payment_proof = null;

    public bool $requireTransferReference = true;

    public bool $requirePaymentProof = false;

    public bool $showReverseModal = false;

    public ?int $reversePaymentId = null;

    public string $reversal_reason = '';

    public bool $showRefundModal = false;

    public ?int $refundSubscriptionId = null;

    public string $refund_method = 'cash';

    public string $refund_transfer_service = '';

    public string $refund_transfer_reference = '';

    public string $refund_reason = '';

    public ?TemporaryUploadedFile $refund_proof = null;

    public function mount(PermissionService $permissions, PaymentPolicy $paymentPolicy): void
    {
        $user = auth()->user();
        $this->requireTransferReference = $paymentPolicy->requiresTransferReference();
        $this->requirePaymentProof = $paymentPolicy->requiresProof();
        abort_unless(
            $permissions->allows($user, 'payments.create')
            || $permissions->allows($user, 'payments.view')
            || $permissions->allows($user, 'payments.reverse')
            || $permissions->allows($user, 'refunds.process'),
            403
        );

        if (request()->boolean('receive') && $permissions->allows($user, 'payments.create')) {
            $requestedSubscriptionId = request()->integer('subscription');
            if ($requestedSubscriptionId > 0) {
                $installment = SubscriptionInstallment::query()
                    ->where('subscription_id', $requestedSubscriptionId)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->orderBy('due_date')
                    ->first();

                if ($installment) {
                    $this->selectInstallment($installment->id);

                    return;
                }
            }
            $this->showInstallmentSelector = true;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCurrency(): void
    {
        $this->currency = $this->filterCurrency;
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMethod(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterMethod', 'fromDate', 'toDate']);
        $this->filterCurrency = 'YER';
        $this->currency = 'YER';
        $this->resetPage();
    }

    public function openPaySelector(): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'payments.create'), 403);
        $this->installmentSearch = '';
        $this->resetValidation();
        $this->showInstallmentSelector = true;
        $this->dispatch('installment-selector-ready');
    }

    public function selectInstallment(int $id): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'payments.create'), 403);

        $installment = SubscriptionInstallment::with('subscription.member')->findOrFail($id);
        abort_unless(in_array($installment->status, ['pending', 'overdue'], true), 422);

        $this->resetValidation();
        $this->installmentId = $installment->id;
        $this->amount = (string) $installment->amount;
        $this->selectedMemberName = $installment->subscription->member->full_name;
        $this->currency = $installment->subscription->currency;
        $this->payment_method = 'cash';
        $this->transfer_service = '';
        $this->transfer_reference = '';
        $this->showInstallmentSelector = false;
        $this->showPayModal = true;
        $this->dispatch('payment-selected');
    }

    public function pay(PaymentService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'payments.create'), 403);

        $validated = $this->validate([
            'installmentId' => ['required', 'integer', 'exists:subscription_installments,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'transfer_service' => ['nullable', 'required_if:payment_method,transfer', 'string', 'max:255'],
            'transfer_reference' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'payment_proof' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $validated['proof_path'] = $this->payment_proof?->store('subscription-payment-proofs', 'local');
        $installment = SubscriptionInstallment::findOrFail((int) $validated['installmentId']);
        $service->payInstallment($installment, $validated, auth()->user());

        $this->reset(['installmentId', 'amount', 'selectedMemberName', 'transfer_service', 'transfer_reference', 'payment_proof']);
        $this->payment_method = 'cash';
        $this->showPayModal = false;
        $this->dispatch('payment-received');
        session()->flash('success', 'تم استلام الدفعة بنجاح.');
    }

    public function confirmReverse(int $paymentId): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'payments.reverse'), 403);
        $this->resetValidation();
        $this->reversePaymentId = $paymentId;
        $this->reversal_reason = '';
        $this->showReverseModal = true;
    }

    public function reverse(PaymentService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'payments.reverse'), 403);
        $this->validate([
            'reversePaymentId' => ['required', 'integer', 'exists:subscription_payments,id'],
            'reversal_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $payment = SubscriptionPayment::findOrFail($this->reversePaymentId);
        $service->reverse($payment, $this->reversal_reason, auth()->user());

        $this->reset(['reversePaymentId', 'reversal_reason']);
        $this->showReverseModal = false;
        $this->dispatch('payment-reversed');
        session()->flash('success', 'تم عكس الدفعة وتسجيل السبب.');
    }

    public function openRefund(int $subscriptionId): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'refunds.process'), 403);
        $this->resetValidation();
        $this->refundSubscriptionId = $subscriptionId;
        $this->refund_reason = '';
        $this->refund_method = 'cash';
        $this->showRefundModal = true;
    }

    public function refund(PaymentService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'refunds.process'), 403);

        $validated = $this->validate([
            'refundSubscriptionId' => ['required', 'integer', 'exists:subscriptions,id'],
            'refund_method' => ['required', Rule::in(['cash', 'transfer'])],
            'refund_transfer_service' => ['nullable', 'required_if:refund_method,transfer', 'string', 'max:255'],
            'refund_transfer_reference' => ['nullable', Rule::requiredIf($this->refund_method === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'refund_proof' => ['nullable', Rule::requiredIf($this->refund_method === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'refund_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $proofPath = $this->refund_proof?->store('subscription-payment-proofs', 'local');
        $subscription = Subscription::findOrFail((int) $validated['refundSubscriptionId']);
        $service->refund($subscription, [
            'payment_method' => $validated['refund_method'],
            'transfer_service' => $validated['refund_transfer_service'] ?: null,
            'transfer_reference' => $validated['refund_transfer_reference'] ?: null,
            'proof_path' => $proofPath,
            'reason' => $validated['refund_reason'] ?: null,
        ], auth()->user());

        $this->reset(['refundSubscriptionId', 'refund_transfer_service', 'refund_transfer_reference', 'refund_reason', 'refund_proof']);
        $this->refund_method = 'cash';
        $this->showRefundModal = false;
        $this->dispatch('payment-refunded');
        session()->flash('success', 'تم تسجيل الاسترداد حسب قاعدة النظام.');
    }

    public function render(): View
    {
        $currency = $this->filterCurrency;
        $now = CarbonImmutable::now('Asia/Aden');
        $monthStart = $now->copy()->startOfMonth();
        $todayStart = $now->copy()->startOfDay();
        $sixMonthsAgo = $now->copy()->startOfMonth()->subMonths(5);

        $paymentStats = SubscriptionPayment::query()
            ->where('currency', $currency)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS total_received,
                 COALESCE(SUM(CASE WHEN status = 'completed' AND paid_at >= ? THEN amount ELSE 0 END), 0) AS month_received,
                 COALESCE(SUM(CASE WHEN status = 'completed' AND paid_at >= ? THEN amount ELSE 0 END), 0) AS today_received,
                 COALESCE(SUM(CASE WHEN status = 'completed' AND payment_method = 'cash' THEN amount ELSE 0 END), 0) AS cash_total,
                 COALESCE(SUM(CASE WHEN status = 'completed' AND payment_method = 'transfer' THEN amount ELSE 0 END), 0) AS transfer_total,
                 COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed_count,
                 COUNT(CASE WHEN status = 'reversed' THEN 1 END) AS reversed_count",
                [$monthStart, $todayStart],
            )->first();

        $installmentStats = SubscriptionInstallment::query()
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_installments.subscription_id')
            ->where('subscriptions.currency', $currency)
            ->whereIn('subscription_installments.status', ['pending', 'overdue'])
            ->selectRaw(
                "COALESCE(SUM(subscription_installments.amount), 0) AS pending_amount,
                 COALESCE(SUM(CASE WHEN subscription_installments.status = 'overdue' OR subscription_installments.due_date < ? THEN subscription_installments.amount ELSE 0 END), 0) AS overdue_amount,
                 COUNT(*) AS pending_count,
                 COUNT(CASE WHEN subscription_installments.status = 'overdue' OR subscription_installments.due_date < ? THEN 1 END) AS overdue_count",
                [$now->toDateString(), $now->toDateString()],
            )->first();

        $monthKeyExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "TO_CHAR(paid_at, 'YYYY-MM')"
            : "strftime('%Y-%m', paid_at)";

        $incomeRows = SubscriptionPayment::query()
            ->where('currency', $currency)
            ->where('status', 'completed')
            ->where('paid_at', '>=', $sixMonthsAgo)
            ->selectRaw("{$monthKeyExpression} AS month_key, SUM(amount) AS total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $months = collect(range(5, 0))->map(function (int $back) use ($now, $incomeRows) {
            $date = $now->copy()->startOfMonth()->subMonths($back);
            $key = $date->format('Y-m');
            $date->locale('ar');

            return [
                'label' => $date->translatedFormat('M'),
                'income' => (float) ($incomeRows[$key] ?? 0),
            ];
        });

        $paymentsQuery = SubscriptionPayment::query()
            ->with(['subscription.member', 'installment', 'creator'])
            ->where('currency', $currency)
            ->when($this->filterStatus !== 'all', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->filterMethod !== 'all', fn (Builder $q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->fromDate !== '', fn (Builder $q) => $q->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn (Builder $q) => $q->whereDate('paid_at', '<=', $this->toDate))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $s) use ($term) {
                    $s->where('receipt_number', 'ilike', $term)
                        ->orWhere('transfer_reference', 'ilike', $term)
                        ->orWhereHas('subscription.member', fn (Builder $m) => $m
                            ->where('full_name', 'ilike', $term)
                            ->orWhere('phone', 'ilike', $term)
                            ->orWhere('membership_code', 'ilike', $term));
                });
            });

        $installments = SubscriptionInstallment::query()
            ->with('subscription.member')
            ->whereIn('status', ['pending', 'overdue'])
            ->whereHas('subscription', fn (Builder $q) => $q->where('currency', $currency))
            ->orderByRaw("CASE WHEN status = 'overdue' OR due_date < ? THEN 0 ELSE 1 END", [$now->toDateString()])
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $selectorInstallments = collect();
        if ($this->showInstallmentSelector) {
            $selectorInstallments = SubscriptionInstallment::query()
                ->with('subscription.member')
                ->whereIn('status', ['pending', 'overdue'])
                ->whereHas('subscription', function (Builder $q) use ($currency) {
                    $q->where('currency', $currency)
                        ->when(trim($this->installmentSearch) !== '', function (Builder $subscriptionQuery) {
                            $term = '%'.trim($this->installmentSearch).'%';
                            $subscriptionQuery->whereHas('member', fn (Builder $memberQuery) => $memberQuery
                                ->where('full_name', 'ilike', $term)
                                ->orWhere('phone', 'ilike', $term)
                                ->orWhere('membership_code', 'ilike', $term));
                        });
                })
                ->orderByRaw("CASE WHEN status = 'overdue' OR due_date < ? THEN 0 ELSE 1 END", [$now->toDateString()])
                ->orderBy('due_date')
                ->limit(20)
                ->get();
        }

        $permissions = app(PermissionService::class);
        $user = auth()->user();
        $canPay = $permissions->allows($user, 'payments.create');
        $canReverse = $permissions->allows($user, 'payments.reverse');
        $canRefund = $permissions->allows($user, 'refunds.process');

        $refundableSubscriptions = collect();
        if ($canRefund) {
            $refundableSubscriptions = Subscription::query()
                ->with('member')
                ->withSum(['payments as completed_payments_sum' => fn (Builder $q) => $q->where('status', 'completed')], 'amount')
                ->where('currency', $currency)
                ->whereNotIn('status', ['refunded', 'cancelled'])
                ->whereHas('payments', fn (Builder $q) => $q->where('status', 'completed'))
                ->whereDoesntHave('refund')
                ->latest('id')
                ->limit(20)
                ->get();
        }

        return view('livewire.finance.payments-index', [
            'payments' => $paymentsQuery->latest('paid_at')->paginate(10),
            'installments' => $installments,
            'selectorInstallments' => $selectorInstallments,
            'refundableSubscriptions' => $refundableSubscriptions,
            'metrics' => [
                'totalReceived' => (float) data_get($paymentStats, 'total_received', 0),
                'receivedThisMonth' => (float) data_get($paymentStats, 'month_received', 0),
                'receivedToday' => (float) data_get($paymentStats, 'today_received', 0),
                'cashTotal' => (float) data_get($paymentStats, 'cash_total', 0),
                'transferTotal' => (float) data_get($paymentStats, 'transfer_total', 0),
                'completedPayments' => (int) data_get($paymentStats, 'completed_count', 0),
                'reversedPayments' => (int) data_get($paymentStats, 'reversed_count', 0),
                'pendingAmount' => (float) data_get($installmentStats, 'pending_amount', 0),
                'pendingCount' => (int) data_get($installmentStats, 'pending_count', 0),
                'overdueAmount' => (float) data_get($installmentStats, 'overdue_amount', 0),
                'overdueCount' => (int) data_get($installmentStats, 'overdue_count', 0),
            ],
            'months' => $months,
            'canPay' => $canPay,
            'canReverse' => $canReverse,
            'canRefund' => $canRefund,
        ]);
    }
}
