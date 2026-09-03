<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use App\Services\PaymentPolicy;
use App\Services\PermissionService;
use App\Support\NumberFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المصروفات - WINNER GYM')]
class ExpensesIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $filterCurrency = 'YER';

    public string $filterStatus = 'approved';

    public string $filterCategory = '';

    public string $fromDate = '';

    public string $toDate = '';

    public bool $showCreateModal = false;

    public bool $showCancelModal = false;

    public ?int $category_id = null;

    public string $title = '';

    public string $amount = '';

    public string $currency = 'YER';

    public string $expense_date = '';

    public string $payment_method = 'cash';

    public string $transfer_service = 'العمقي';

    public string $transfer_reference = '';

    public string $notes = '';

    public ?TemporaryUploadedFile $receipt = null;

    public bool $createNewCategory = false;

    public string $new_category_name = '';

    public ?int $cancelExpenseId = null;

    public string $cancellation_reason = '';

    public function mount(PermissionService $permissions): void
    {
        $canView = $permissions->allows(auth()->user(), 'expenses.view')
            || $permissions->allows(auth()->user(), 'expenses.manage');

        abort_unless($canView, 403);

        $this->expense_date = now('Asia/Aden')->toDateString();
        $this->currency = $this->filterCurrency;

        if (request()->boolean('new') && $permissions->allows(auth()->user(), 'expenses.manage')) {
            $this->createNewCategory = ! ExpenseCategory::query()->where('is_active', true)->exists();
            $this->showCreateModal = true;
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

    public function updatedFilterCategory(): void
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
        $this->reset(['search', 'filterCategory', 'fromDate', 'toDate']);
        $this->filterStatus = 'approved';
        $this->filterCurrency = 'YER';
        $this->currency = 'YER';
        $this->resetPage();
    }

    public function openCreate(): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'expenses.manage'), 403);
        $this->resetCreateForm();
        $this->createNewCategory = ! ExpenseCategory::query()->where('is_active', true)->exists();
        $this->showCreateModal = true;
    }

    public function closeCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetValidation();
    }

    public function create(ExpenseService $service, PaymentPolicy $paymentPolicy): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'expenses.manage'), 403);

        $this->amount = NumberFormatter::clean($this->amount);

        $validated = $this->validate([
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'transfer_service' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer'), Rule::in(['العمقي', 'الكريمي', 'البسيري'])],
            'transfer_reference' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->payment_method === 'transfer' && $paymentPolicy->requiresTransferReference())],
            'notes' => ['nullable', 'string', 'max:3000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'title.required' => 'اسم المصروف مطلوب.',
            'amount.required' => 'المبلغ مطلوب.',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'receipt.required' => 'إرفاق الفاتورة أو الإيصال مطلوب لحفظ المصروف.',
            'receipt.mimes' => 'الإيصال يجب أن يكون JPG أو PNG أو PDF.',
            'receipt.max' => 'حجم الإيصال يجب ألا يتجاوز 2MB.',
        ]);

        if ($this->createNewCategory) {
            $categoryName = preg_replace('/\s+/u', ' ', trim($validated['new_category_name'] ?? ''));

            if ($categoryName === '') {
                $this->addError('new_category_name', 'اكتب اسم تصنيف المصروف الجديد.');

                return;
            }

            $category = ExpenseCategory::query()->firstOrCreate(
                ['name' => $categoryName],
                ['is_active' => true],
            );

            if (! $category->is_active) {
                $category->update(['is_active' => true]);
            }

            $validated['category_id'] = $category->id;
        } elseif (blank($validated['category_id'] ?? null)) {
            $this->addError('category_id', 'اختر تصنيف المصروف أو أضف تصنيفًا جديدًا.');

            return;
        }

        $validated['transfer_service'] = $validated['payment_method'] === 'transfer'
            ? $validated['transfer_service']
            : null;
        $validated['transfer_reference'] = $validated['payment_method'] === 'transfer'
            ? trim((string) ($validated['transfer_reference'] ?? ''))
            : null;
        $validated['receipt_path'] = $this->receipt->store('expenses/receipts', 'local');

        unset($validated['receipt'], $validated['new_category_name']);

        try {
            $service->create($validated, auth()->user());
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($validated['receipt_path']);
            throw $exception;
        }

        $this->showCreateModal = false;
        $this->filterCurrency = $this->currency;
        $this->resetCreateForm();
        $this->resetPage();
        $this->dispatch('expense-created');

        session()->flash('success', 'تم تسجيل المصروف واعتماده بنجاح.');
    }

    public function confirmCancel(int $expenseId): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'expenses.manage'), 403);

        $expense = Expense::findOrFail($expenseId);
        abort_if($expense->status === 'cancelled', 422);

        $this->cancelExpenseId = $expenseId;
        $this->cancellation_reason = '';
        $this->showCancelModal = true;
    }

    public function cancel(ExpenseService $service): void
    {
        abort_unless(app(PermissionService::class)->allows(auth()->user(), 'expenses.manage'), 403);

        $this->validate([
            'cancelExpenseId' => ['required', 'integer', 'exists:expenses,id'],
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'cancellation_reason.required' => 'سبب الإلغاء مطلوب.',
            'cancellation_reason.min' => 'سبب الإلغاء قصير جدًا.',
        ]);

        $service->cancel(
            Expense::findOrFail($this->cancelExpenseId),
            $this->cancellation_reason,
            auth()->user()
        );

        $this->reset(['cancelExpenseId', 'cancellation_reason']);
        $this->showCancelModal = false;
        $this->resetPage();
        $this->dispatch('expense-cancelled');
        session()->flash('success', 'تم إلغاء المصروف مع الاحتفاظ بالسجل المالي.');
    }

    private function resetCreateForm(): void
    {
        $this->reset(['category_id', 'title', 'amount', 'transfer_reference', 'notes', 'receipt', 'new_category_name']);
        $this->transfer_service = 'العمقي';
        $this->createNewCategory = false;
        $this->currency = $this->filterCurrency;
        $this->payment_method = 'cash';
        $this->expense_date = now('Asia/Aden')->toDateString();
        $this->resetValidation();
    }

    /** @return Builder<Expense> */
    private function filteredQuery(bool $includeStatus = true): Builder
    {
        return Expense::query()
            ->with('category')
            ->where('currency', $this->filterCurrency)
            ->when($includeStatus && $this->filterStatus !== 'all', fn (Builder $q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCategory !== '', fn (Builder $q) => $q->where('category_id', (int) $this->filterCategory))
            ->when($this->fromDate !== '', fn (Builder $q) => $q->whereDate('expense_date', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn (Builder $q) => $q->whereDate('expense_date', '<=', $this->toDate))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('title', 'ilike', $term)
                        ->orWhere('transfer_reference', 'ilike', $term)
                        ->orWhereHas('category', fn (Builder $cat) => $cat->where('name', 'ilike', $term));
                });
            });
    }

    public function render(): View
    {
        $now = CarbonImmutable::now('Asia/Aden');
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();
        $today = $now->toDateString();
        $sixMonthsAgo = $now->copy()->startOfMonth()->subMonths(5);
        $currency = $this->filterCurrency;

        $base = Expense::query()->where('currency', $currency);

        $stats = (clone $base)->selectRaw(
            "COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END),0) AS approved_total,
             COALESCE(SUM(CASE WHEN status = 'approved' AND expense_date BETWEEN ? AND ? THEN amount ELSE 0 END),0) AS month_total,
             COALESCE(SUM(CASE WHEN status = 'approved' AND expense_date = ? THEN amount ELSE 0 END),0) AS today_total,
             COALESCE(SUM(CASE WHEN status = 'approved' AND payment_method = 'cash' THEN amount ELSE 0 END),0) AS cash_total,
             COALESCE(SUM(CASE WHEN status = 'approved' AND payment_method = 'transfer' THEN amount ELSE 0 END),0) AS transfer_total,
             COUNT(CASE WHEN status = 'approved' THEN 1 END) AS approved_count,
             COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled_count",
            [$monthStart, $monthEnd, $today]
        )->first();

        $expenseRows = Expense::query()
            ->where('currency', $currency)
            ->where('status', 'approved')
            ->whereDate('expense_date', '>=', $sixMonthsAgo->toDateString())
            ->selectRaw("TO_CHAR(expense_date, 'YYYY-MM') AS month_key, SUM(amount) AS total")
            ->groupBy('month_key')
            ->pluck('total', 'month_key');

        $months = collect(range(5, 0))->map(function (int $back) use ($now, $expenseRows) {

            $date = $now->copy()->startOfMonth()->subMonths($back);
            $key = $date->format('Y-m');
            $date->locale('ar');

            return [
                'key' => $key,
                'label' => $date->translatedFormat('M'),
                'expense' => (float) ($expenseRows[$key] ?? 0),
            ];
        });

        $categoryRows = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->where('expenses.currency', $currency)
            ->where('expenses.status', 'approved')
            ->selectRaw('expense_categories.name AS name, SUM(expenses.amount) AS total')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $categoryTotal = max(1, (float) $categoryRows->sum('total'));
        $categoryDistribution = $categoryRows->map(function (Expense $row) use ($categoryTotal): array {
            $total = (float) data_get($row, 'total', 0);

            return [
                'name' => (string) data_get($row, 'name', ''),
                'total' => $total,
                'percent' => round(($total / $categoryTotal) * 100, 1),
            ];
        });

        $recentExpenses = Expense::query()
            ->with('category')
            ->where('currency', $currency)
            ->latest('expense_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.finance.expenses-index', [
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'expenses' => $this->filteredQuery()->latest('expense_date')->latest('id')->paginate(10),
            'stats' => $stats,
            'months' => $months,
            'categoryDistribution' => $categoryDistribution,
            'recentExpenses' => $recentExpenses,
            'categorySuggestions' => ['الإيجار', 'الرواتب', 'الكهرباء والمياه', 'الصيانة', 'النظافة', 'التسويق', 'مستلزمات النادي', 'رسوم وخدمات', 'أخرى'],
            'canManage' => app(PermissionService::class)->allows(auth()->user(), 'expenses.manage'),
        ]);
    }
}
