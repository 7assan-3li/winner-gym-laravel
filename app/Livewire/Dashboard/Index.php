<?php

namespace App\Livewire\Dashboard;

use App\Models\Appointment;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\SubscriptionPayment;
use App\Services\AttendanceService;
use App\Services\AuditService;
use App\Services\ExpenseService;
use App\Services\MembershipService;
use App\Services\PaymentPolicy;
use App\Services\PaymentService;
use App\Services\PermissionService;
use App\Services\ReportService;
use App\Services\SubscriptionService;
use App\Support\NumberFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('لوحة التحكم - WINNER GYM')]
class Index extends Component
{
    use WithFileUploads;

    public string $modal = '';

    public string $globalSearch = '';

    public string $globalSearchType = 'all';

    public string $notificationType = 'all';

    public string $dashboardPeriod = 'month';

    // Member quick action
    public string $member_full_name = '';

    public string $member_phone = '';

    public string $member_gender = 'male';

    public string $member_assigned_period = 'men';

    public ?string $member_birth_date = null;

    public ?int $member_age = null;

    public string $member_address = '';

    public string $member_identity_number = '';

    public string $member_notes = '';

    // Attendance quick action
    public string $attendance_method = 'membership_code';

    public string $attendance_identifier = '';

    /** @var array<string, string>|null */
    public ?array $attendance_success = null;

    // Subscription quick action
    public ?int $sub_member_id = null;

    public ?int $sub_package_id = null;

    public string $sub_period = 'men';

    public string $sub_start_date = '';

    public string $sub_currency = 'YER';

    public string $sub_discount_amount = '0';

    public string $sub_payment_plan = 'full';

    public string $sub_first_payment_amount = '';

    public int $sub_installment_count = 1;

    /** @var list<string> */
    public array $sub_installment_due_dates = [];

    public string $sub_payment_method = 'cash';

    public string $sub_transfer_service = '';

    public string $sub_transfer_reference = '';

    public string $sub_notes = '';

    public ?TemporaryUploadedFile $sub_payment_proof = null;

    public ?int $created_subscription_id = null;

    // Payment quick action
    public ?int $payment_member_id = null;

    public ?int $payment_installment_id = null;

    public string $payment_amount = '';

    public string $payment_currency = 'YER';

    public string $payment_method = 'cash';

    public string $payment_transfer_service = '';

    public string $payment_transfer_reference = '';

    public string $payment_notes = '';

    public ?TemporaryUploadedFile $payment_proof = null;

    public bool $requireTransferReference = true;

    public bool $requirePaymentProof = false;

    // Expense quick action
    public ?int $expense_category_id = null;

    public string $expense_title = '';

    public string $expense_amount = '';

    public string $expense_currency = 'YER';

    public string $expense_date = '';

    public string $expense_payment_method = 'cash';

    public string $expense_transfer_reference = '';

    public string $expense_notes = '';

    public ?TemporaryUploadedFile $expense_receipt = null;

    // Product quick action
    public ?int $product_category_id = null;

    public string $product_name = '';

    public string $product_barcode = '';

    public string $product_purchase_cost = '0';

    public string $product_selling_price = '0';

    public string $product_currency = 'YER';

    public int $product_minimum_quantity = 0;

    public string $product_notes = '';

    public ?TemporaryUploadedFile $product_image = null;

    public function mount(PaymentPolicy $paymentPolicy): void
    {
        $this->requireTransferReference = $paymentPolicy->requiresTransferReference();
        $this->requirePaymentProof = $paymentPolicy->requiresProof();
        $this->sub_start_date = now('Asia/Aden')->toDateString();
        $this->expense_date = now('Asia/Aden')->toDateString();
    }

    public function openModal(string $name): void
    {
        $allowed = ['member', 'subscription', 'attendance', 'payment', 'expense', 'product', 'search', 'notifications'];
        abort_unless(in_array($name, $allowed, true), 404);
        $this->resetValidation();
        $this->attendance_success = null;
        $this->modal = $name;
    }

    public function closeModal(): void
    {
        $this->resetValidation();
        $this->modal = '';
    }

    public function setGlobalSearchType(string $type): void
    {
        abort_unless(in_array($type, ['all', 'member', 'subscription', 'product'], true), 404);

        $this->globalSearchType = $type;
    }

    public function setNotificationType(string $type): void
    {
        abort_unless(in_array($type, ['all', 'subscriptions', 'payments', 'inventory', 'system'], true), 404);

        $this->notificationType = $type;
    }

    public function updatedDashboardPeriod(string $period): void
    {
        if (! in_array($period, ['day', 'week', 'month'], true)) {
            $this->dashboardPeriod = 'month';
        }
    }

    public function createMember(MembershipService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'members.create') || $permissions->allows(auth()->user(), 'members.manage'), 403);

        $data = $this->validate([
            'member_full_name' => ['required', 'string', 'max:255'],
            'member_phone' => ['required', 'string', 'max:30', 'unique:members,phone'],
            'member_gender' => ['required', Rule::in(['male', 'female'])],
            'member_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'member_age' => ['nullable', 'integer', 'min:5', 'max:100'],
            'member_assigned_period' => ['required', Rule::in(['men', 'women'])],
            'member_address' => ['nullable', 'string', 'max:255'],
            'member_identity_number' => ['nullable', 'string', 'max:100'],
            'member_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        if (($data['member_gender'] === 'male' && $data['member_assigned_period'] !== 'men') || ($data['member_gender'] === 'female' && $data['member_assigned_period'] !== 'women')) {
            $this->addError('member_assigned_period', 'الفترة لا تطابق جنس العضو.');

            return;
        }

        $member = $service->create([
            'full_name' => $data['member_full_name'],
            'phone' => $data['member_phone'],
            'gender' => $data['member_gender'],
            'birth_date' => $data['member_birth_date'] ?: null,
            'age' => $data['member_age'] ?: null,
            'assigned_period' => $data['member_assigned_period'],
            'address' => $data['member_address'] ?: null,
            'identity_number' => $data['member_identity_number'] ?: null,
            'notes' => $data['member_notes'] ?: null,
        ], auth()->user());

        $this->reset(['member_full_name', 'member_phone', 'member_birth_date', 'member_age', 'member_address', 'member_identity_number', 'member_notes']);
        $this->member_gender = 'male';
        $this->member_assigned_period = 'men';
        $this->modal = '';
        $this->forgetDashboardCache();
        session()->flash('success', 'تمت إضافة العضو بنجاح: '.$member->membership_code);
    }

    public function recordAttendance(AttendanceService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'attendance.record'), 403);

        $data = $this->validate([
            'attendance_method' => ['required', Rule::in(['name', 'phone', 'membership_code', 'barcode', 'qr'])],
            'attendance_identifier' => ['required', 'string', 'max:255'],
        ]);

        $attendance = $service->record($data['attendance_method'], trim($data['attendance_identifier']), auth()->user());
        $this->attendance_success = [
            'name' => $attendance->member->full_name,
            'code' => $attendance->member->membership_code,
            'time' => now('Asia/Aden')->format('h:i A'),
        ];
        $this->attendance_identifier = '';
        $this->forgetDashboardCache();
    }

    public function updatedSubPaymentPlan(): void
    {
        if ($this->sub_payment_plan === 'full') {
            $this->sub_installment_count = 1;
            $this->sub_installment_due_dates = [];
        } elseif ($this->sub_installment_count < 2) {
            $this->sub_installment_count = 2;
            $this->updatedSubInstallmentCount();
        }
        $this->syncSubscriptionPaymentAmount();
    }

    public function updatedSubInstallmentCount(): void
    {
        $count = max(0, $this->sub_installment_count - 1);
        $this->sub_installment_due_dates = array_slice($this->sub_installment_due_dates, 0, $count);
        while (count($this->sub_installment_due_dates) < $count) {
            $next = now('Asia/Aden')->addMonths(count($this->sub_installment_due_dates) + 1)->toDateString();
            $this->sub_installment_due_dates[] = $next;
        }
    }

    public function updatedSubMemberId(): void
    {
        if ($this->sub_member_id) {
            $member = $this->cachedDashboardRecord('members', $this->sub_member_id);
            if ($member) {
                $this->sub_period = $member['assigned_period'];
            }
        }
    }

    public function updatedSubPackageId(): void
    {
        $this->syncSubscriptionPaymentAmount();
    }

    public function updatedSubCurrency(): void
    {
        $this->syncSubscriptionPaymentAmount();
    }

    public function updatedSubDiscountAmount(): void
    {
        $this->syncSubscriptionPaymentAmount();
    }

    private function syncSubscriptionPaymentAmount(): void
    {
        if (! $this->sub_package_id) {
            return;
        }
        $package = $this->cachedDashboardRecord('packages', $this->sub_package_id);
        if (! $package) {
            return;
        }
        $price = $this->sub_currency === 'SAR' ? ($package['price_sar'] ?? null) : ($package['price_yer'] ?? null);
        if ($price === null) {
            return;
        }
        $final = max(0, (float) $price - (float) ($this->sub_discount_amount ?: 0));
        $this->sub_first_payment_amount = (string) ($this->sub_payment_plan === 'full' ? $final : round($final * .5, 2));
    }

    /** @return array<string, mixed>|null */
    private function cachedDashboardRecord(string $group, int $id): ?array
    {
        $today = CarbonImmutable::now('Asia/Aden')->toDateString();
        $common = Cache::store('file')->get('winner-gym:dashboard:'.$this->getId().':common:'.$today, []);

        if (is_array($common)) {
            $items = $common[$group] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (is_array($item) && (int) ($item['id'] ?? 0) === $id) {
                        return $item;
                    }
                }
            }
        }

        return match ($group) {
            'members' => Member::find($id)?->toArray(),
            'packages' => Package::find($id)?->toArray(),
            default => null,
        };
    }

    public function createSubscription(SubscriptionService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'subscriptions.create') || $permissions->allows(auth()->user(), 'subscriptions.manage'), 403);

        $this->sub_discount_amount = NumberFormatter::clean($this->sub_discount_amount);
        $this->sub_first_payment_amount = NumberFormatter::clean($this->sub_first_payment_amount);

        $data = $this->validate([
            'sub_member_id' => ['required', 'exists:members,id'],
            'sub_package_id' => ['required', 'exists:packages,id'],
            'sub_period' => ['required', Rule::in(['men', 'women'])],
            'sub_start_date' => ['required', 'date'],
            'sub_currency' => ['required', Rule::in(['YER', 'SAR'])],
            'sub_discount_amount' => ['required', 'numeric', 'min:0'],
            'sub_payment_plan' => ['required', Rule::in(['full', 'installments'])],
            'sub_installment_count' => ['required', 'integer', 'min:1', 'max:24'],
            'sub_first_payment_amount' => ['required', 'numeric', 'min:0'],
            'sub_payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'sub_transfer_service' => ['nullable', 'required_if:sub_payment_method,transfer', 'string', 'max:255'],
            'sub_transfer_reference' => ['nullable', Rule::requiredIf($this->sub_payment_method === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'sub_notes' => ['nullable', 'string', 'max:3000'],
            'sub_installment_due_dates' => ['array'],
            'sub_installment_due_dates.*' => ['nullable', 'date', 'after_or_equal:sub_start_date'],
            'sub_payment_proof' => ['nullable', Rule::requiredIf($this->sub_payment_method === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'sub_transfer_service.required_if' => 'اسم خدمة التحويل أو الصرافة مطلوب.',
            'sub_transfer_reference.required_if' => 'رقم مرجع السند مطلوب.',
            'sub_payment_proof.required_if' => 'يجب إرفاق صورة أو ملف سند التحويل قبل تأكيد الاشتراك.',
        ]);

        $package = Package::findOrFail((int) $data['sub_package_id']);
        $packagePrice = $data['sub_currency'] === 'SAR' ? $package->price_sar : $package->price_yer;
        if ($packagePrice === null) {
            $this->addError('sub_currency', 'لا يوجد سعر للباقة بهذه العملة.');

            return;
        }
        $finalPrice = max(0, round((float) $packagePrice - (float) $data['sub_discount_amount'], 2));

        if ($data['sub_payment_plan'] === 'full') {
            $data['sub_first_payment_amount'] = $finalPrice;
            $this->sub_first_payment_amount = (string) $finalPrice;
        } elseif ((float) $data['sub_first_payment_amount'] > $finalPrice) {
            $this->addError('sub_first_payment_amount', 'الدفعة الأولى لا يمكن أن تتجاوز المبلغ الكامل للاشتراك.');

            return;
        }

        if (Member::findOrFail((int) $data['sub_member_id'])->assigned_period !== $data['sub_period']) {
            $this->addError('sub_period', 'الفترة لا تطابق فترة العضو.');

            return;
        }

        $proofPath = $this->sub_payment_proof?->store('subscription-payment-proofs', 'local');

        $subscription = $service->create([
            'member_id' => $data['sub_member_id'],
            'package_id' => $data['sub_package_id'],
            'period' => $data['sub_period'],
            'start_date' => $data['sub_start_date'],
            'currency' => $data['sub_currency'],
            'discount_amount' => $data['sub_discount_amount'],
            'payment_plan' => $data['sub_payment_plan'],
            'installment_count' => $data['sub_payment_plan'] === 'full' ? 1 : $data['sub_installment_count'],
            'first_payment_amount' => $data['sub_first_payment_amount'],
            'payment_method' => $data['sub_payment_method'],
            'transfer_service' => $data['sub_transfer_service'] ?: null,
            'transfer_reference' => $data['sub_transfer_reference'] ?: null,
            'notes' => $data['sub_notes'] ?: null,
            'installment_due_dates' => $data['sub_payment_plan'] === 'full' ? [] : $data['sub_installment_due_dates'],
            'proof_path' => $proofPath,
        ], auth()->user());

        $this->forgetDashboardCache();
        $this->created_subscription_id = $subscription->id;
        $this->modal = 'subscription-success';
    }

    public function finishSubscriptionSuccess(): void
    {
        $this->resetSubscriptionForm();
        $this->modal = '';
    }

    public function resetSubscriptionForm(): void
    {
        $this->reset(['sub_member_id', 'sub_package_id', 'sub_discount_amount', 'sub_first_payment_amount', 'sub_transfer_service', 'sub_transfer_reference', 'sub_notes', 'sub_installment_due_dates', 'sub_payment_proof', 'created_subscription_id']);
        $this->sub_start_date = now('Asia/Aden')->toDateString();
        $this->sub_currency = 'YER';
        $this->sub_period = 'men';
        $this->sub_payment_plan = 'full';
        $this->sub_payment_method = 'cash';
        $this->sub_installment_count = 1;
    }

    public function updatedPaymentMemberId(): void
    {
        $this->payment_installment_id = null;
        $this->payment_amount = '';
        if (! $this->payment_member_id) {
            return;
        }

        $installment = SubscriptionInstallment::query()
            ->whereHas('subscription', fn ($q) => $q->where('member_id', $this->payment_member_id)->whereIn('status', ['active', 'financial_overdue', 'expiring_soon']))
            ->whereIn('status', ['pending', 'overdue'])
            ->with('subscription')
            ->orderBy('due_date')
            ->first();

        if ($installment) {
            $this->payment_installment_id = $installment->id;
            $this->payment_amount = (string) $installment->amount;
            $this->payment_currency = $installment->subscription->currency;
        }
    }

    public function receivePayment(PaymentService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'payments.create'), 403);

        $this->payment_amount = NumberFormatter::clean($this->payment_amount);

        $data = $this->validate([
            'payment_member_id' => ['required', 'exists:members,id'],
            'payment_installment_id' => ['required', 'exists:subscription_installments,id'],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
            'payment_currency' => ['required', Rule::in(['YER', 'SAR'])],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'payment_transfer_service' => ['nullable', 'required_if:payment_method,transfer', 'string', 'max:255'],
            'payment_transfer_reference' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requireTransferReference), 'string', 'max:255'],
            'payment_proof' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer' && $this->requirePaymentProof), 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        if ($data['payment_method'] === 'transfer' && $this->requireTransferReference && blank($data['payment_transfer_reference'])) {
            $this->addError('payment_transfer_reference', 'مرجع التحويل مطلوب.');

            return;
        }

        $proofPath = $this->payment_proof?->store('subscription-payment-proofs', 'local');
        $installment = SubscriptionInstallment::findOrFail((int) $data['payment_installment_id']);
        $service->payInstallment($installment, [
            'amount' => $data['payment_amount'],
            'currency' => $data['payment_currency'],
            'payment_method' => $data['payment_method'],
            'transfer_service' => $data['payment_transfer_service'] ?: null,
            'transfer_reference' => $data['payment_transfer_reference'] ?: null,
            'proof_path' => $proofPath,
        ], auth()->user());

        $this->reset(['payment_member_id', 'payment_installment_id', 'payment_amount', 'payment_transfer_service', 'payment_transfer_reference', 'payment_notes', 'payment_proof']);
        $this->payment_currency = 'YER';
        $this->payment_method = 'cash';
        $this->modal = '';
        $this->forgetDashboardCache();
        session()->flash('success', 'تم استلام الدفعة بنجاح.');
    }

    public function createExpense(ExpenseService $service, PermissionService $permissions): void
    {
        abort_unless($permissions->allows(auth()->user(), 'expenses.manage'), 403);

        $this->expense_amount = NumberFormatter::clean($this->expense_amount);

        $data = $this->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'expense_title' => ['required', 'string', 'max:255'],
            'expense_amount' => ['required', 'numeric', 'gt:0'],
            'expense_currency' => ['required', Rule::in(['YER', 'SAR'])],
            'expense_date' => ['required', 'date'],
            'expense_payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'expense_transfer_reference' => ['nullable', 'string', 'max:255'],
            'expense_notes' => ['nullable', 'string', 'max:3000'],
            'expense_receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $receiptPath = $this->expense_receipt?->store('expense-receipts', 'local');
        $service->create([
            'category_id' => $data['expense_category_id'],
            'title' => $data['expense_title'],
            'amount' => $data['expense_amount'],
            'currency' => $data['expense_currency'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['expense_payment_method'],
            'transfer_reference' => $data['expense_transfer_reference'] ?: null,
            'receipt_path' => $receiptPath,
            'notes' => $data['expense_notes'] ?: null,
        ], auth()->user());

        $this->reset(['expense_category_id', 'expense_title', 'expense_amount', 'expense_transfer_reference', 'expense_notes', 'expense_receipt']);
        $this->expense_currency = 'YER';
        $this->expense_payment_method = 'cash';
        $this->expense_date = now('Asia/Aden')->toDateString();
        $this->modal = '';
        $this->forgetDashboardCache();
        session()->flash('success', 'تم تسجيل المصروف بنجاح.');
    }

    public function createProduct(PermissionService $permissions, AuditService $audit): void
    {
        abort_unless($permissions->allows(auth()->user(), 'products.manage'), 403);

        $this->product_purchase_cost = NumberFormatter::clean($this->product_purchase_cost);
        $this->product_selling_price = NumberFormatter::clean($this->product_selling_price);

        $data = $this->validate([
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'product_purchase_cost' => ['required', 'numeric', 'min:0'],
            'product_selling_price' => ['required', 'numeric', 'min:0'],
            'product_currency' => ['required', Rule::in(['YER', 'SAR'])],
            'product_minimum_quantity' => ['required', 'integer', 'min:0'],
            'product_notes' => ['nullable', 'string', 'max:3000'],
            'product_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $imagePath = $this->product_image?->store('product-images', 'local');
        $product = Product::create([
            'category_id' => $data['product_category_id'],
            'name' => $data['product_name'],
            'image_path' => $imagePath,
            'barcode' => $data['product_barcode'] ?: null,
            'purchase_cost' => $data['product_purchase_cost'],
            'selling_price' => $data['product_selling_price'],
            'currency' => $data['product_currency'],
            'current_quantity' => 0,
            'minimum_quantity' => $data['product_minimum_quantity'],
            'status' => 'active',
            'notes' => $data['product_notes'] ?: null,
        ]);
        $audit->log(auth()->user(), 'inventory', 'product.created', $product);

        $this->reset(['product_category_id', 'product_name', 'product_barcode', 'product_notes', 'product_image']);
        $this->product_purchase_cost = '0';
        $this->product_selling_price = '0';
        $this->product_minimum_quantity = 0;
        $this->product_currency = 'YER';
        $this->modal = '';
        $this->forgetDashboardCache();
        session()->flash('success', 'تمت إضافة المنتج. أضف الكمية من شاشة المشتريات حتى تُسجل حركة المخزون.');
    }

    private function forgetDashboardCache(): void
    {
        $cache = Cache::store('file');
        $today = CarbonImmutable::now('Asia/Aden')->toDateString();

        $cache->forget('winner-gym:dashboard:'.$this->getId().':common:'.$today);
        foreach (['day', 'week', 'month'] as $period) {
            $cache->forget('winner-gym:dashboard:'.$this->getId().':period:'.$today.':'.$period);
        }
    }

    public function render(ReportService $reports): View
    {
        $now = CarbonImmutable::now('Asia/Aden');
        $today = $now->toDateString();
        $yesterday = $now->subDay()->toDateString();
        $expiringEnd = $now->addDays(30)->toDateString();

        [$periodStart, $periodEndExclusive] = match ($this->dashboardPeriod) {
            'day' => [$now->startOfDay(), $now->addDay()->startOfDay()],
            'week' => [$now->startOfWeek(), $now->startOfWeek()->addWeek()],
            default => [$now->startOfMonth(), $now->startOfMonth()->addMonth()],
        };
        $dashboardPeriodLabel = match ($this->dashboardPeriod) {
            'day' => 'اليوم',
            'week' => 'هذا الأسبوع',
            default => 'هذا الشهر',
        };

        $dashboardCache = Cache::store('file');
        $dashboardCommon = $dashboardCache->remember(
            'winner-gym:dashboard:'.$this->getId().':common:'.$today,
            now()->addMinutes(30),
            function () use ($today, $yesterday, $expiringEnd) {
                $row = DB::selectOne(
                    <<<'SQL'
                    select
                        (select count(*) from members) as total_members,
                        (select count(*) from members where status = 'active') as active_members,
                        (select count(*) from attendances where attendance_date = :today_attendance) as attendance_today,
                        (select count(*) from attendances where attendance_date = :yesterday_attendance) as attendance_yesterday,
                        (select count(*) from subscriptions where status = 'financial_overdue') as overdue_subscriptions,
                        (select count(*) from subscriptions where status in ('active','expiring_soon') and end_date between :today_expiring and :expiring_end) as expiring_soon,
                        (select count(*) from appointments where appointment_date = :today_appointments and status <> 'cancelled') as appointments_today,
                        (select count(*) from products where current_quantity <= minimum_quantity and status = 'active') as stock_alerts
                    SQL,
                    [
                        'today_attendance' => $today,
                        'yesterday_attendance' => $yesterday,
                        'today_expiring' => $today,
                        'expiring_end' => $expiringEnd,
                        'today_appointments' => $today,
                    ]
                );

                return [
                    'stats' => (array) $row,
                    'recent_subscriptions' => Subscription::query()->with('member:id,full_name,membership_code')->latest('id')->limit(5)->get()->toArray(),
                    'today_appointments' => Appointment::query()->with(['member:id,full_name', 'nutritionClient:id,full_name', 'nutritionist:id,name'])->whereDate('appointment_date', $today)->where('status', '<>', 'cancelled')->orderBy('start_time')->limit(5)->get()->toArray(),
                    'members' => Member::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'membership_code', 'phone', 'assigned_period'])->toArray(),
                    'packages' => Package::where('is_active', true)->orderBy('name')->get()->toArray(),
                    'expense_categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get()->toArray(),
                    'product_categories' => ProductCategory::where('is_active', true)->orderBy('name')->get()->toArray(),
                    'latest_payment' => SubscriptionPayment::with('subscription.member')->where('status', 'completed')->latest('paid_at')->first()?->toArray(),
                    'latest_member' => Member::latest('id')->first()?->toArray(),
                    'low_stock_product' => Product::where('status', 'active')->whereColumn('current_quantity', '<=', 'minimum_quantity')->orderBy('current_quantity')->first()?->toArray(),
                    'next_appointment' => Appointment::with(['member', 'nutritionClient'])->whereDate('appointment_date', '>=', $today)->where('status', '<>', 'cancelled')->orderBy('appointment_date')->orderBy('start_time')->first()?->toArray(),
                ];
            }
        );
        $row = (object) $dashboardCommon['stats'];

        $stats = [
            'total_members' => (int) $row->total_members,
            'active_members' => (int) $row->active_members,
            'attendance_today' => (int) $row->attendance_today,
            'attendance_yesterday' => (int) $row->attendance_yesterday,
            'overdue_subscriptions' => (int) $row->overdue_subscriptions,
            'expiring_soon' => (int) $row->expiring_soon,
            'appointments_today' => (int) $row->appointments_today,
            'stock_alerts' => (int) $row->stock_alerts,
        ];

        $dashboardPeriodData = $dashboardCache->remember(
            'winner-gym:dashboard:'.$this->getId().':period:'.$today.':'.$this->dashboardPeriod,
            now()->addMinutes(30),
            function () use ($reports, $periodStart, $periodEndExclusive) {
                $finance = [
                    'YER' => $reports->summary($periodStart->toDateString(), $periodEndExclusive->subDay()->toDateString(), 'all', 'YER'),
                    'SAR' => $reports->summary($periodStart->toDateString(), $periodEndExclusive->subDay()->toDateString(), 'all', 'SAR'),
                ];

                $paidAtProjection = DB::connection()->getDriverName() === 'pgsql'
                    ? "paid_at at time zone 'Asia/Aden'"
                    : 'paid_at';
                $soldAtProjection = DB::connection()->getDriverName() === 'pgsql'
                    ? "sold_at at time zone 'Asia/Aden'"
                    : 'sold_at';

                $revenueRows = DB::select(
                    <<<SQL
            select local_at, amount
            from (
                select {$paidAtProjection} as local_at, amount
                from subscription_payments
                where currency = 'YER' and status = 'completed' and paid_at >= :sub_from and paid_at < :sub_to
                union all
                select {$paidAtProjection} as local_at, amount
                from appointment_payments
                where currency = 'YER' and status = 'paid' and paid_at >= :nutrition_from and paid_at < :nutrition_to
                union all
                select {$soldAtProjection} as local_at, total_amount as amount
                from sales
                where currency = 'YER' and status = 'completed' and sold_at >= :sales_from and sold_at < :sales_to
            ) revenue
            order by local_at
            SQL,
                    [
                        'sub_from' => $periodStart->utc(), 'sub_to' => $periodEndExclusive->utc(),
                        'nutrition_from' => $periodStart->utc(), 'nutrition_to' => $periodEndExclusive->utc(),
                        'sales_from' => $periodStart->utc(), 'sales_to' => $periodEndExclusive->utc(),
                    ]
                );

                return [
                    'finance' => $finance,
                    'revenue_rows' => array_map(fn ($row) => (array) $row, $revenueRows),
                ];
            }
        );
        $finance = $dashboardPeriodData['finance'];
        $revenueRows = array_map(fn (array $row) => (object) $row, $dashboardPeriodData['revenue_rows']);

        $seriesDefinition = match ($this->dashboardPeriod) {
            'day' => collect(range(0, 5))->map(fn (int $slot) => [
                'key' => 'hour-'.$slot,
                'label' => ['12 ص', '4 ص', '8 ص', '12 م', '4 م', '8 م'][$slot],
            ]),
            'week' => collect(range(0, 6))->map(function (int $day) use ($periodStart) {
                $date = $periodStart->addDays($day);

                $date->locale('ar');

                return ['key' => $date->toDateString(), 'label' => $date->translatedFormat('l')];
            }),
            default => collect(range(0, (int) ceil($periodStart->daysInMonth / 7) - 1))->map(fn (int $week) => [
                'key' => 'week-'.$week,
                'label' => 'الأسبوع '.($week + 1),
            ]),
        };
        $seriesTotals = array_fill(0, $seriesDefinition->count(), 0.0);

        foreach ($revenueRows as $revenueRow) {
            $localAt = CarbonImmutable::parse($revenueRow->local_at, 'Asia/Aden');
            $index = match ($this->dashboardPeriod) {
                'day' => intdiv($localAt->hour, 4),
                'week' => (int) $periodStart->startOfDay()->diffInDays($localAt->startOfDay()),
                default => intdiv($localAt->day - 1, 7),
            };

            if (array_key_exists($index, $seriesTotals)) {
                $seriesTotals[$index] += (float) $revenueRow->amount;
            }
        }

        $revenueSeries = $seriesDefinition->map(fn (array $point, int $index) => [
            ...$point,
            'value' => $seriesTotals[$index],
        ])->values();

        $objectify = static fn (array $data): \stdClass => (object) $data;
        $recentSubscriptions = collect($dashboardCommon['recent_subscriptions'])->map(function (array $item) use ($objectify) {
            $subscription = $objectify($item);
            $subscription->start_date = filled($item['start_date'] ?? null) ? CarbonImmutable::parse($item['start_date']) : null;

            return $subscription;
        });
        $todayAppointments = collect($dashboardCommon['today_appointments'])->map($objectify);
        $members = collect($dashboardCommon['members'])->map($objectify);
        $packages = collect($dashboardCommon['packages'])->map($objectify);
        $expenseCategories = collect($dashboardCommon['expense_categories'])->map($objectify);
        $productCategories = collect($dashboardCommon['product_categories'])->map($objectify);

        $selectedSubMember = $this->sub_member_id ? $members->firstWhere('id', $this->sub_member_id) : null;
        $selectedSubPackage = $this->sub_package_id ? $packages->firstWhere('id', $this->sub_package_id) : null;
        $subOriginalPrice = $selectedSubPackage ? (float) ($this->sub_currency === 'SAR' ? ($selectedSubPackage->price_sar ?? 0) : ($selectedSubPackage->price_yer ?? 0)) : 0;
        $subFinalPrice = max(0, $subOriginalPrice - (float) ($this->sub_discount_amount ?: 0));
        $subRemaining = max(0, $subFinalPrice - (float) ($this->sub_first_payment_amount ?: 0));
        $subRemainingInstallments = max(1, $this->sub_installment_count - 1);
        $subInstallmentAmount = $this->sub_payment_plan === 'installments' ? round($subRemaining / $subRemainingInstallments, 2) : 0;

        $paymentSubscription = null;
        $paymentInstallment = null;
        if ($this->payment_installment_id) {
            $paymentInstallment = SubscriptionInstallment::with(['subscription.member'])->find($this->payment_installment_id);
            $paymentSubscription = $paymentInstallment?->subscription;
        }

        $createdSubscription = $this->created_subscription_id
            ? Subscription::with(['member', 'installments', 'payments'])->find($this->created_subscription_id)
            : null;

        $globalResults = collect();
        if (mb_strlen(trim($this->globalSearch)) >= 2) {
            $term = '%'.trim($this->globalSearch).'%';
            $membersFound = Member::query()->where(fn ($q) => $q->where('full_name', 'ilike', $term)->orWhere('phone', 'ilike', $term)->orWhere('membership_code', 'ilike', $term))->limit(4)->get()->map(fn ($m) => ['type' => 'member', 'title' => $m->full_name, 'subtitle' => $m->membership_code.' · '.$m->phone, 'meta' => $m->status]);
            $productsFound = Product::query()->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('barcode', 'ilike', $term))->limit(4)->get()->map(fn ($p) => ['type' => 'product', 'title' => $p->name, 'subtitle' => 'المخزون: '.$p->current_quantity, 'meta' => $p->barcode ?: 'بدون باركود']);
            $subsFound = Subscription::query()->with('member')->whereHas('member', fn ($q) => $q->where('full_name', 'ilike', $term)->orWhere('membership_code', 'ilike', $term))->limit(4)->get()->map(fn ($s) => ['type' => 'subscription', 'title' => $s->package_name_snapshot, 'subtitle' => $s->member->full_name, 'meta' => $s->status]);
            $globalResults = $membersFound->concat($subsFound)->concat($productsFound);

            if ($this->globalSearchType !== 'all') {
                $globalResults = $globalResults->where('type', $this->globalSearchType);
            }

            $globalResults = $globalResults->take(12)->values();
        }

        $latestPayment = $dashboardCommon['latest_payment'] ? $objectify($dashboardCommon['latest_payment']) : null;
        if ($latestPayment && filled($dashboardCommon['latest_payment']['paid_at'] ?? null)) {
            $latestPayment->paid_at = CarbonImmutable::parse($dashboardCommon['latest_payment']['paid_at']);
        }
        $latestMember = $dashboardCommon['latest_member'] ? $objectify($dashboardCommon['latest_member']) : null;
        if ($latestMember && filled($dashboardCommon['latest_member']['created_at'] ?? null)) {
            $latestMember->created_at = CarbonImmutable::parse($dashboardCommon['latest_member']['created_at']);
        }
        $lowStockProduct = $dashboardCommon['low_stock_product'] ? $objectify($dashboardCommon['low_stock_product']) : null;
        $nextAppointment = $dashboardCommon['next_appointment'] ? $objectify($dashboardCommon['next_appointment']) : null;
        if ($nextAppointment && filled($dashboardCommon['next_appointment']['appointment_date'] ?? null)) {
            $nextAppointment->appointment_date = CarbonImmutable::parse($dashboardCommon['next_appointment']['appointment_date']);
        }

        return view('livewire.dashboard.index', [
            'stats' => $stats,
            'finance' => $finance,
            'dashboardPeriodLabel' => $dashboardPeriodLabel,
            'recentSubscriptions' => $recentSubscriptions,
            'todayAppointments' => $todayAppointments,
            'revenueSeries' => $revenueSeries,
            'members' => $members,
            'packages' => $packages,
            'expenseCategories' => $expenseCategories,
            'productCategories' => $productCategories,
            'selectedSubMember' => $selectedSubMember,
            'selectedSubPackage' => $selectedSubPackage,
            'subOriginalPrice' => $subOriginalPrice,
            'subFinalPrice' => $subFinalPrice,
            'subRemaining' => $subRemaining,
            'subInstallmentAmount' => $subInstallmentAmount,
            'paymentSubscription' => $paymentSubscription,
            'paymentInstallment' => $paymentInstallment,
            'createdSubscription' => $createdSubscription,
            'globalResults' => $globalResults,
            'latestPayment' => $latestPayment,
            'latestMember' => $latestMember,
            'lowStockProduct' => $lowStockProduct,
            'nextAppointment' => $nextAppointment,
        ]);
    }
}
