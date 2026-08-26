<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\Purchase;
use App\Services\InventoryService;
use App\Services\PermissionService;
use App\Services\PurchaseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
#[Title('المشتريات - WINNER GYM')]
class PurchasesIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $currencyFilter = '';

    public bool $showCreateModal = false;

    public bool $canManage = false;

    public string $purchase_date = '';

    public string $supplier_name = '';

    public string $supplier_invoice = '';

    public string $currency = 'YER';

    public string $payment_method = 'cash';

    public string $transfer_service = 'العمقي';

    public string $transfer_reference = '';

    public ?TemporaryUploadedFile $purchase_document = null;

    public string $notes = '';

    /** @var array<int, array<string, int|float|string>> */
    public array $items = [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0]];

    public ?int $cancelPurchaseId = null;

    public string $cancellation_reason = '';

    public function mount(PermissionService $permissions): void
    {
        $user = auth()->user();
        abort_unless(
            $permissions->allows($user, 'purchases.view')
            || $permissions->allows($user, 'purchases.manage')
            || $permissions->allows($user, 'inventory.manage'),
            403
        );

        $this->canManage = $permissions->allows($user, 'purchases.manage')
            || $permissions->allows($user, 'inventory.manage');
        $this->purchase_date = now('Asia/Aden')->toDateString();

        if ($this->canManage && request()->boolean('new')) {
            $this->resetPurchaseForm();
            $this->showCreateModal = true;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCurrencyFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        abort_unless($this->canManage, 403);
        $this->resetPurchaseForm();
        $this->showCreateModal = true;
    }

    public function closeCreate(): void
    {
        $this->showCreateModal = false;
        $this->resetValidation();
        $this->resetPurchaseForm();
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function create(PurchaseService $service): void
    {
        abort_unless($this->canManage, 403);

        $validated = $this->validate([
            'purchase_date' => ['required', 'date'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_invoice' => ['required', 'string', 'max:120'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'transfer_service' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer'), Rule::in(['العمقي', 'الكريمي', 'البسيري'])],
            'transfer_reference' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->payment_method === 'transfer')],
            'purchase_document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_name.required' => 'أدخل اسم المورد حتى يمكن تتبع عملية الشراء.',
            'supplier_invoice.required' => 'أدخل رقم فاتورة المورد.',
            'purchase_document.required' => 'أرفق فاتورة المورد أو سند الشراء.',
            'transfer_reference.required' => 'أدخل رقم أو مرجع التحويل.',
            'items.*.product_id.required' => 'اختر المنتج في كل سطر.',
            'items.*.quantity.min' => 'كمية المنتج يجب أن تكون وحدة واحدة على الأقل.',
            'items.*.unit_cost.required' => 'أدخل تكلفة الوحدة لكل منتج.',
        ]);

        $validated['supplier_name'] = trim($validated['supplier_name'] ?? '') ?: null;
        $validated['supplier_invoice'] = trim($validated['supplier_invoice'] ?? '') ?: null;
        $validated['transfer_service'] = $validated['payment_method'] === 'transfer'
            ? $validated['transfer_service']
            : null;
        $validated['transfer_reference'] = $validated['payment_method'] === 'transfer'
            ? trim((string) ($validated['transfer_reference'] ?? ''))
            : null;
        $validated['proof_path'] = $this->purchase_document->store('purchase-documents', 'local');
        unset($validated['purchase_document']);

        try {
            $purchase = $service->create($validated, auth()->user());
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($validated['proof_path']);
            throw $exception;
        }
        $this->showCreateModal = false;
        $this->resetPurchaseForm();
        $this->dispatch('purchase-saved');

        session()->flash('success', 'تم إنشاء الشراء '.$purchase->purchase_number.' كعملية معلقة. اعتمدها بعد استلام البضاعة فعلياً ليزداد المخزون.');
    }

    public function approve(int $purchaseId, InventoryService $service): void
    {
        abort_unless($this->canManage, 403);
        $service->approvePurchase(Purchase::findOrFail($purchaseId), auth()->user());
        session()->flash('success', 'تم اعتماد الشراء وإضافة الكميات إلى المخزون وتحديث تكلفة الشراء.');
    }

    public function startCancel(int $purchaseId): void
    {
        abort_unless($this->canManage, 403);
        $purchase = Purchase::findOrFail($purchaseId);
        abort_unless($purchase->status === 'pending', 422);
        $this->cancelPurchaseId = $purchase->id;
        $this->cancellation_reason = '';
    }

    public function cancel(PurchaseService $service): void
    {
        abort_unless($this->canManage, 403);
        $this->validate([
            'cancelPurchaseId' => ['required', 'integer', 'exists:purchases,id'],
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $service->cancel(Purchase::findOrFail($this->cancelPurchaseId), $this->cancellation_reason, auth()->user());
        $this->reset(['cancelPurchaseId', 'cancellation_reason']);
        session()->flash('success', 'تم إلغاء عملية الشراء المعلقة بدون تغيير المخزون.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'currencyFilter']);
        $this->resetPage();
    }

    private function resetPurchaseForm(): void
    {
        $this->purchase_date = now('Asia/Aden')->toDateString();
        $this->supplier_name = '';
        $this->supplier_invoice = '';
        $this->currency = 'YER';
        $this->payment_method = 'cash';
        $this->transfer_service = 'العمقي';
        $this->transfer_reference = '';
        $this->purchase_document = null;
        $this->notes = '';
        $this->items = [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0]];
    }

    public function render(): View
    {
        $now = CarbonImmutable::now('Asia/Aden');
        $monthStart = $now->startOfMonth()->toDateString();
        $monthEnd = $now->endOfMonth()->toDateString();

        $purchasesQuery = Purchase::query()
            ->with(['items.product'])
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('purchase_number', 'ilike', $term)
                        ->orWhere('supplier_name', 'ilike', $term)
                        ->orWhere('supplier_invoice', 'ilike', $term)
                        ->orWhere('notes', 'ilike', $term);
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->currencyFilter !== '', fn ($q) => $q->where('currency', $this->currencyFilter))
            ->latest('purchase_date')
            ->latest('id');

        $summary = Purchase::query()
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'pending') AS pending_count")
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'approved' AND purchase_date BETWEEN ? AND ?) AS approved_month_count", [$monthStart, $monthEnd])
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'cancelled' AND purchase_date BETWEEN ? AND ?) AS cancelled_month_count", [$monthStart, $monthEnd])
            ->first();

        $amounts = DB::table('purchases as p')
            ->join('purchase_items as pi', 'pi.purchase_id', '=', 'p.id')
            ->selectRaw("COALESCE(SUM(CASE WHEN p.status = 'approved' AND p.purchase_date BETWEEN ? AND ? AND p.currency = 'YER' THEN pi.line_total ELSE 0 END), 0) AS approved_yer", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN p.status = 'approved' AND p.purchase_date BETWEEN ? AND ? AND p.currency = 'SAR' THEN pi.line_total ELSE 0 END), 0) AS approved_sar", [$monthStart, $monthEnd])
            ->selectRaw("COALESCE(SUM(CASE WHEN p.status = 'pending' AND p.currency = 'YER' THEN pi.line_total ELSE 0 END), 0) AS pending_yer")
            ->selectRaw("COALESCE(SUM(CASE WHEN p.status = 'pending' AND p.currency = 'SAR' THEN pi.line_total ELSE 0 END), 0) AS pending_sar")
            ->selectRaw("COALESCE(SUM(CASE WHEN p.status = 'approved' AND p.purchase_date BETWEEN ? AND ? THEN pi.quantity ELSE 0 END), 0) AS received_units_month", [$monthStart, $monthEnd])
            ->first();

        return view('livewire.inventory.purchases-index', [
            'products' => Product::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'currency', 'current_quantity', 'purchase_cost']),
            'purchases' => $purchasesQuery->paginate(12),
            'pendingCount' => (int) ($summary->pending_count ?? 0),
            'approvedMonthCount' => (int) ($summary->approved_month_count ?? 0),
            'cancelledMonthCount' => (int) ($summary->cancelled_month_count ?? 0),
            'receivedUnitsMonth' => (int) ($amounts->received_units_month ?? 0),
            'approvedYER' => (float) ($amounts->approved_yer ?? 0),
            'approvedSAR' => (float) ($amounts->approved_sar ?? 0),
            'pendingYER' => (float) ($amounts->pending_yer ?? 0),
            'pendingSAR' => (float) ($amounts->pending_sar ?? 0),
            'formSubtotal' => collect($this->items)->sum(fn ($row) => max(0, (int) ($row['quantity'] ?? 0)) * max(0, (float) ($row['unit_cost'] ?? 0))),
        ]);
    }
}
