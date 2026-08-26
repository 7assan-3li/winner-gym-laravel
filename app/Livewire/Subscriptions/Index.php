<?php

namespace App\Livewire\Subscriptions;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Services\PaymentPolicy;
use App\Services\PaymentService;
use App\Services\PermissionService;
use App\Services\SubscriptionService;
use App\Support\NumberFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
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
#[Title('الاشتراكات - WINNER GYM')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $status_filter = 'all';

    public string $package_filter = 'all';

    public string $payment_method_filter = 'all';

    public string $from_date = '';

    public string $to_date = '';

    public string $summary_currency = 'YER';

    public string $period = 'men';

    public string $start_date = '';

    public string $currency = 'YER';

    public string $discount_amount = '0';

    public string $payment_plan = 'full';

    public string $first_payment_amount = '';

    public string $payment_method = 'cash';

    public string $transfer_service = '';

    public string $transfer_reference = '';

    public string $notes = '';

    public ?TemporaryUploadedFile $payment_proof = null;

    public ?int $member_id = null;

    public ?int $package_id = null;

    public int $installment_count = 1;

    /** @var list<string> */
    public array $installment_due_dates = [];

    /** @var array<int, array<string, mixed>> */
    public array $member_options = [];

    /** @var array<int, array<string, mixed>> */
    public array $package_options = [];

    /** @var array<int, array<string, mixed>> */
    public array $all_package_options = [];

    /** @var array<string, int> */
    public array $dashboard_counts = [];

    public float $current_revenue = 0.0;

    public float $previous_revenue = 0.0;

    public float $revenue_change = 0.0;

    /** @var array<int, array<string, int|float|string>> */
    public array $revenue_series_data = [];

    /** @var array<int, array<string, int|float|string>> */
    public array $package_distribution_data = [];

    public string $month_report_from = '';

    public string $month_report_to = '';

    public bool $collectionOpen = false;

    public ?int $collectionInstallmentId = null;

    public string $collectionAmount = '';

    public string $collectionCurrency = 'YER';

    public string $collectionMethod = 'cash';

    public string $collectionTransferService = '';

    public string $collectionTransferReference = '';

    public ?TemporaryUploadedFile $collectionPaymentProof = null;

    public bool $requireTransferReference = true;

    public bool $requirePaymentProof = false;

    /** @var array<string, int|string> */
    public array $collectionContext = [];

    public function mount(PermissionService $p, PaymentPolicy $paymentPolicy): void
    {
        abort_unless($p->allows(auth()->user(), 'subscriptions.view'), 403);
        $this->requireTransferReference = $paymentPolicy->requiresTransferReference();
        $this->requirePaymentProof = $paymentPolicy->requiresProof();
        $this->start_date = now('Asia/Aden')->toDateString();
        $this->from_date = '';
        $this->to_date = '';

        $requestedMemberId = request()->integer('member');
        if ($requestedMemberId > 0) {
            $member = Member::query()->where('status', 'active')->find($requestedMemberId);
            if ($member) {
                $this->member_id = $member->id;
                $this->period = $member->assigned_period;
            }
        }

        $this->loadReferenceData();
        $this->loadDashboardData();
    }

    public function updatedInstallmentCount(): void
    {
        $n = max(0, $this->installment_count - 1);
        $this->installment_due_dates = array_slice($this->installment_due_dates, 0, $n);
        while (count($this->installment_due_dates) < $n) {
            $this->installment_due_dates[] = '';
        }
    }

    public function updatedPaymentPlan(): void
    {
        if ($this->payment_plan === 'full') {
            $this->installment_count = 1;
            $this->installment_due_dates = [];
            $this->syncFullPaymentAmount();
        } elseif ($this->installment_count < 2) {
            $this->installment_count = 2;
            $this->updatedInstallmentCount();
        }
    }

    public function updatedMemberId(int|string|null $memberId): void
    {
        $member = $memberId
            ? Member::query()->where('status', 'active')->find((int) $memberId)
            : null;

        if ($member) {
            $this->period = $member->assigned_period;
        }
    }

    public function updatedPackageId(): void
    {
        $this->syncFullPaymentAmount();
    }

    public function updatedCurrency(): void
    {
        $this->syncFullPaymentAmount();
    }

    public function updatedDiscountAmount(): void
    {
        $this->syncFullPaymentAmount();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPackageFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSummaryCurrency(): void
    {
        $this->loadDashboardData();
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
        $this->search = '';
        $this->status_filter = 'all';
        $this->package_filter = 'all';
        $this->payment_method_filter = 'all';
        $this->from_date = '';
        $this->to_date = '';
        $this->resetPage();
    }

    public function create(SubscriptionService $service, PermissionService $p): void
    {
        abort_unless($p->allows(auth()->user(), 'subscriptions.create') || $p->allows(auth()->user(), 'subscriptions.manage'), 403);

        if ($this->payment_plan === 'full') {
            $this->syncFullPaymentAmount();
        }

        $d = $this->validate([
            'member_id' => ['required', 'exists:members,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'period' => ['required', Rule::in(['men', 'women'])],
            'start_date' => ['required', 'date'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'payment_plan' => ['required', Rule::in(['full', 'installments'])],
            'installment_count' => ['required', 'integer', 'min:1', 'max:24'],
            'first_payment_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'transfer_service' => ['nullable', 'required_if:payment_method,transfer', 'string', 'max:255'],
            'transfer_reference' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'installment_due_dates' => ['array'],
            'installment_due_dates.*' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_proof' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'transfer_service.required_if' => 'اسم خدمة التحويل أو الصرافة مطلوب.',
            'transfer_reference.required_if' => 'رقم مرجع السند مطلوب.',
            'payment_proof.required_if' => 'يجب إرفاق صورة أو ملف سند التحويل قبل تأكيد الاشتراك.',
        ]);

        if ($d['payment_plan'] === 'full') {
            $d['installment_count'] = 1;
            $d['installment_due_dates'] = [];
        }

        if (Member::findOrFail((int) $d['member_id'])->assigned_period !== $d['period']) {
            $this->addError('period', 'الفترة لا تطابق فترة العضو.');

            return;
        }

        $package = Package::findOrFail((int) $d['package_id']);
        $packagePrice = $d['currency'] === 'SAR' ? $package->price_sar : $package->price_yer;
        if ($packagePrice === null) {
            $this->addError('currency', 'لا يوجد سعر للباقة بهذه العملة.');

            return;
        }

        $finalPrice = round((float) $packagePrice - (float) $d['discount_amount'], 2);
        if ($finalPrice < 0) {
            $this->addError('discount_amount', 'الخصم لا يمكن أن يتجاوز سعر الباقة.');

            return;
        }

        if ($d['payment_plan'] === 'installments' && (float) $d['first_payment_amount'] > $finalPrice) {
            $this->addError('first_payment_amount', 'الدفعة الأولى لا يمكن أن تتجاوز المبلغ الكامل للاشتراك.');

            return;
        }

        $d['proof_path'] = $this->payment_proof?->store('subscription-payment-proofs', 'local');

        $subscription = $service->create($d, auth()->user());
        $this->loadDashboardData();

        $this->reset([
            'member_id', 'package_id', 'discount_amount', 'first_payment_amount',
            'transfer_service', 'transfer_reference', 'notes', 'installment_due_dates', 'payment_proof',
        ]);
        $this->start_date = now('Asia/Aden')->toDateString();
        $this->currency = 'YER';
        $this->period = 'men';
        $this->payment_plan = 'full';
        $this->payment_method = 'cash';
        $this->installment_count = 1;

        $this->dispatch('subscription-created');
        session()->flash('success', 'تم إنشاء الاشتراك وتسجيل الدفعة الأولى: #'.$subscription->id);
    }

    public function openCollection(int $subscriptionId, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'payments.create'), 403);

        $this->resetValidation();

        $subscription = Subscription::query()
            ->with([
                'member:id,full_name,membership_code',
                'installments' => fn ($query) => $query
                    ->whereIn('status', ['pending', 'overdue'])
                    ->where('amount', '>', 0)
                    ->orderBy('due_date')
                    ->orderBy('installment_number'),
            ])
            ->withSum(['payments as paid_amount' => fn ($query) => $query->where('status', 'completed')], 'amount')
            ->findOrFail($subscriptionId);

        $remaining = max(0, round((float) $subscription->final_price - (float) ($subscription->paid_amount ?? 0), 2));
        $installment = $subscription->installments->first();

        if ($subscription->payment_plan !== 'installments' || $remaining <= 0.009 || ! $installment) {
            $this->addError('collectionInstallmentId', 'لا يوجد قسط متبقٍ قابل للتحصيل لهذا الاشتراك.');
            $this->collectionOpen = false;

            return;
        }

        $this->collectionInstallmentId = $installment->id;
        $this->collectionAmount = rtrim(rtrim(number_format((float) $installment->amount, 2, '.', ''), '0'), '.');
        $this->collectionCurrency = $subscription->currency;
        $this->collectionMethod = 'cash';
        $this->collectionTransferService = '';
        $this->collectionTransferReference = '';
        $this->collectionContext = [
            'subscription_id' => $subscription->id,
            'member_name' => $subscription->member->full_name,
            'member_code' => $subscription->member->membership_code,
            'package' => $subscription->package_name_snapshot,
            'installment_number' => $installment->installment_number,
            'due_date' => $installment->due_date->format('Y-m-d'),
            'remaining' => NumberFormatter::money($remaining).' '.$subscription->currency,
        ];
        $this->collectionOpen = true;
    }

    public function receiveCollection(PaymentService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'payments.create'), 403);

        $validated = $this->validate([
            'collectionInstallmentId' => ['required', 'integer', 'exists:subscription_installments,id'],
            'collectionAmount' => ['required', 'numeric', 'gt:0'],
            'collectionCurrency' => ['required', Rule::in(['YER', 'SAR'])],
            'collectionMethod' => ['required', Rule::in(['cash', 'transfer'])],
            'collectionTransferService' => ['nullable', 'required_if:collectionMethod,transfer', 'string', 'max:255'],
            'collectionTransferReference' => ['nullable', Rule::requiredIf($this->collectionMethod === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'collectionPaymentProof' => ['nullable', Rule::requiredIf($this->collectionMethod === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'collectionTransferService.required_if' => 'اسم خدمة التحويل أو الصرافة مطلوب.',
            'collectionTransferReference.required_if' => 'رقم مرجع التحويل مطلوب.',
            'collectionPaymentProof.required_if' => 'يجب إرفاق سند التحويل حسب سياسة الدفع.',
        ]);

        $installment = SubscriptionInstallment::with('subscription')->findOrFail((int) $validated['collectionInstallmentId']);
        $subscription = $installment->subscription;
        $paid = (float) $subscription->payments()->where('status', 'completed')->sum('amount');
        $remaining = max(0, round((float) $subscription->final_price - $paid, 2));

        if (
            $subscription->payment_plan !== 'installments'
            || $remaining <= 0.009
            || ! in_array($installment->status, ['pending', 'overdue'], true)
        ) {
            $this->addError('collectionInstallmentId', 'هذا الاشتراك لا يحتوي على قسط متبقٍ قابل للتحصيل.');

            return;
        }

        if (strtoupper($validated['collectionCurrency']) !== $subscription->currency) {
            $this->addError('collectionCurrency', 'عملة القسط لا تطابق عملة الاشتراك.');

            return;
        }

        if (abs((float) $validated['collectionAmount'] - (float) $installment->amount) > 0.009) {
            $this->addError('collectionAmount', 'المبلغ يجب أن يساوي قيمة القسط المستحق.');

            return;
        }

        $proofPath = $this->collectionPaymentProof?->store('subscription-payment-proofs', 'local');

        $service->payInstallment($installment, [
            'amount' => $validated['collectionAmount'],
            'currency' => $validated['collectionCurrency'],
            'payment_method' => $validated['collectionMethod'],
            'transfer_service' => $validated['collectionTransferService'] ?: null,
            'transfer_reference' => $validated['collectionTransferReference'] ?: null,
            'proof_path' => $proofPath,
        ], auth()->user());

        $this->closeCollection();
        $this->loadDashboardData();

        session()->flash('success', 'تم استلام القسط بنجاح.');
    }

    public function closeCollection(): void
    {
        $this->reset([
            'collectionOpen',
            'collectionInstallmentId',
            'collectionAmount',
            'collectionCurrency',
            'collectionMethod',
            'collectionTransferService',
            'collectionTransferReference',
            'collectionPaymentProof',
            'collectionContext',
        ]);
        $this->resetValidation();
    }

    public function render(): View
    {
        $perPage = 10;
        $page = $this->getPage();
        $subscriptionItems = Subscription::query()
            ->select('subscriptions.*')
            ->selectRaw('count(*) over() as filtered_total')
            ->with([
                'member:id,full_name,membership_code,phone',
                'installments' => fn ($q) => $q->orderBy('installment_number'),
                'payments' => fn ($q) => $q->where('status', 'completed')->latest('paid_at'),
            ])
            ->withSum(['payments as paid_amount' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $term = '%'.$this->search.'%';
                $q->whereHas('member', fn ($m) => $m
                    ->where('full_name', 'ilike', $term)
                    ->orWhere('membership_code', 'ilike', $term)
                    ->orWhere('phone', 'ilike', $term))
                    ->orWhere('package_name_snapshot', 'ilike', $term)
                    ->orWhereRaw('CAST(id AS TEXT) ILIKE ?', [$term]);
            }))
            ->when($this->status_filter !== 'all', fn ($q) => $q->where('status', $this->status_filter))
            ->when($this->package_filter !== 'all', fn ($q) => $q->where('package_id', (int) $this->package_filter))
            ->when($this->payment_method_filter !== 'all', fn ($q) => $q->whereHas('payments', fn ($p) => $p
                ->where('status', 'completed')
                ->where('payment_method', $this->payment_method_filter)))
            ->when($this->from_date !== '', fn ($q) => $q->whereDate('start_date', '>=', $this->from_date))
            ->when($this->to_date !== '', fn ($q) => $q->whereDate('start_date', '<=', $this->to_date))
            ->latest('id')
            ->forPage($page, $perPage)
            ->get();

        $subscriptions = new LengthAwarePaginator(
            $subscriptionItems,
            (int) data_get($subscriptionItems->first(), 'filtered_total', 0),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'page'],
        );

        return view('livewire.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'members' => collect($this->member_options)->map(fn (array $item) => (object) $item),
            'packages' => collect($this->package_options)->map(fn (array $item) => (object) $item),
            'allPackages' => collect($this->all_package_options)->map(fn (array $item) => (object) $item),
            'counts' => $this->dashboard_counts,
            'currentRevenue' => $this->current_revenue,
            'previousRevenue' => $this->previous_revenue,
            'revenueChange' => $this->revenue_change,
            'revenueSeries' => $this->revenue_series_data,
            'packageDistribution' => $this->package_distribution_data,
            'summaryCurrency' => $this->summary_currency,
            'monthReportFrom' => $this->month_report_from,
            'monthReportTo' => $this->month_report_to,
        ]);
    }

    private function loadReferenceData(): void
    {
        $this->member_options = Member::query()
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'membership_code', 'assigned_period'])
            ->map(fn (Member $member) => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'membership_code' => $member->membership_code,
                'assigned_period' => $member->assigned_period,
            ])->all();

        $allPackages = Package::query()
            ->orderBy('name')
            ->get(['id', 'name', 'duration_value', 'duration_unit', 'price_yer', 'price_sar', 'description', 'is_active']);

        $this->package_options = $allPackages
            ->where('is_active', true)
            ->values()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'duration_value' => $package->duration_value,
                'duration_unit' => $package->duration_unit,
                'price_yer' => $package->price_yer,
                'price_sar' => $package->price_sar,
                'description' => $package->description,
            ])->all();

        $this->all_package_options = $allPackages
            ->map(fn (Package $package) => ['id' => $package->id, 'name' => $package->name])
            ->all();
    }

    private function loadDashboardData(): void
    {
        $now = CarbonImmutable::now('Asia/Aden');
        $monthStart = $now->startOfMonth();
        $nextMonthStart = $monthStart->addMonth();
        $previousMonthStart = $monthStart->subMonth();
        $this->summary_currency = in_array($this->summary_currency, ['YER', 'SAR'], true)
            ? $this->summary_currency
            : 'YER';

        $counts = DB::selectOne(
            "select count(*) as total,
                count(*) filter (where status='active') as active,
                count(*) filter (where status='financial_overdue') as overdue,
                count(*) filter (where status='expiring_soon') as expiring,
                count(*) filter (where status='upcoming') as upcoming,
                count(*) filter (where status='expired') as expired,
                count(*) filter (where status='expired' and end_date >= ? and end_date < ?) as expired_this_month,
                count(*) filter (where created_at >= ? and created_at < ?) as new_this_month
             from subscriptions",
            [$monthStart->toDateString(), $nextMonthStart->toDateString(), $monthStart->utc(), $nextMonthStart->utc()]
        );
        $this->dashboard_counts = [
            'total' => (int) $counts->total,
            'active' => (int) $counts->active,
            'overdue' => (int) $counts->overdue,
            'expiring' => (int) $counts->expiring,
            'upcoming' => (int) $counts->upcoming,
            'expired' => (int) $counts->expired,
            'expired_this_month' => (int) $counts->expired_this_month,
            'new_this_month' => (int) $counts->new_this_month,
        ];

        $revenueRow = DB::selectOne(
            "select
                coalesce(sum(amount) filter (where paid_at >= ? and paid_at < ?), 0) as current_month,
                coalesce(sum(amount) filter (where paid_at >= ? and paid_at < ?), 0) as previous_month
             from subscription_payments
             where status='completed' and currency=?",
            [$monthStart->utc(), $nextMonthStart->utc(), $previousMonthStart->utc(), $monthStart->utc(), $this->summary_currency]
        );
        $this->current_revenue = (float) ($revenueRow->current_month ?? 0);
        $this->previous_revenue = (float) ($revenueRow->previous_month ?? 0);
        $this->revenue_change = $this->previous_revenue > 0
            ? round((($this->current_revenue - $this->previous_revenue) / $this->previous_revenue) * 100, 1)
            : ($this->current_revenue > 0 ? 100.0 : 0.0);

        $chartStart = $monthStart->subMonths(5);
        $monthKeyExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "to_char(paid_at at time zone 'Asia/Aden', 'YYYY-MM')"
            : "strftime('%Y-%m', paid_at)";

        $monthlyRows = collect(DB::select(
            "select {$monthKeyExpression} as month_key, coalesce(sum(amount), 0) as total
             from subscription_payments
             where status='completed' and currency=? and paid_at >= ? and paid_at < ?
             group by 1 order by 1",
            [$this->summary_currency, $chartStart->utc(), $nextMonthStart->utc()]
        ))->keyBy('month_key');
        $this->revenue_series_data = collect(range(0, 5))->map(function (int $offset) use ($chartStart, $monthlyRows) {
            $month = $chartStart->addMonths($offset);
            $row = $monthlyRows->get($month->format('Y-m'));
            $month->locale('ar');

            return ['key' => $month->format('Y-m'), 'label' => $month->translatedFormat('M'), 'value' => (float) data_get($row, 'total', 0)];
        })->all();

        $distributionRows = collect(DB::select(
            "select package_name_snapshot as name, count(*) as total from subscriptions
             where status in ('active','financial_overdue','expiring_soon')
             group by package_name_snapshot order by count(*) desc, package_name_snapshot asc limit 4"
        ));
        $distributionTotal = max(1, (int) $distributionRows->sum('total'));
        $this->package_distribution_data = $distributionRows->map(fn ($row) => [
            'name' => $row->name,
            'total' => (int) $row->total,
            'percent' => round(((int) $row->total / $distributionTotal) * 100, 1),
        ])->all();
        $this->month_report_from = $monthStart->toDateString();
        $this->month_report_to = $now->toDateString();
    }

    private function syncFullPaymentAmount(): void
    {
        if ($this->payment_plan !== 'full' || ! $this->package_id) {
            return;
        }

        $package = Package::find($this->package_id);
        if (! $package) {
            return;
        }

        $price = $this->currency === 'SAR' ? $package->price_sar : $package->price_yer;
        if ($price === null) {
            $this->first_payment_amount = '';

            return;
        }

        $finalPrice = max(0, round((float) $price - max(0, (float) ($this->discount_amount ?: 0)), 2));
        $this->first_payment_amount = number_format($finalPrice, 2, '.', '');
    }
}
