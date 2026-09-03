<?php

namespace App\Livewire\Inventory;

use App\Models\Member;
use App\Models\Product;
use App\Models\Sale;
use App\Services\InventoryService;
use App\Services\PaymentPolicy;
use App\Services\PermissionService;
use App\Support\NumberFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
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
#[Title('المبيعات - WINNER GYM')]
class SalesIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $productSearch = '';

    public string $historySearch = '';

    public string $statusFilter = '';

    public string $currencyFilter = '';

    /** @var array<int|string, array<string, mixed>> */
    public array $cart = [];

    public ?int $member_id = null;

    public string $customer_name = '';

    public string $payment_method = 'cash';

    public string $transfer_service = 'العمقي';

    public string $transfer_reference = '';

    public ?TemporaryUploadedFile $payment_proof = null;

    public string $discount_value = '0';

    public ?int $lastCompletedSaleId = null;

    public ?int $cancelSaleId = null;

    public string $cancellation_reason = '';

    public bool $canCreate = false;

    public bool $canCancel = false;

    public bool $canDiscount = false;

    public function mount(PermissionService $permissions): void
    {
        $user = auth()->user();
        abort_unless(
            $permissions->allows($user, 'sales.create')
            || $permissions->allows($user, 'sales.view')
            || $permissions->allows($user, 'sales.cancel')
            || $permissions->allows($user, 'inventory.manage'),
            403
        );

        $this->canCreate = $permissions->allows($user, 'sales.create') || $permissions->allows($user, 'inventory.manage');
        $this->canCancel = $permissions->allows($user, 'sales.cancel') || $permissions->allows($user, 'inventory.manage');
        $this->canDiscount = $permissions->allows($user, 'discounts.formal') || $user->role === 'owner';
    }

    public function updatedHistorySearch(): void
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

    public function updatedProductSearch(string $value): void
    {
        $barcode = trim($value);
        if ($barcode === '') {
            return;
        }

        $product = Product::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('barcode', $barcode)
            ->first();

        if (! $product) {
            return;
        }

        $this->addProduct($product->id);
        $this->productSearch = '';
    }

    public function updatedPaymentMethod(string $value): void
    {
        if ($value === 'cash') {
            $this->transfer_service = 'العمقي';
            $this->transfer_reference = '';
            $this->reset('payment_proof');
        }
    }

    public function addProduct(int $productId): void
    {
        abort_unless($this->canCreate, 403);

        $key = (string) $productId;
        if (isset($this->cart[$key])) {
            $this->increaseQuantity($productId);

            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);
        if (! $product || (int) $product->current_quantity <= 0) {
            $this->addError('cart', 'المنتج «'.($product ? $product->name : '').'» غير متوفر حالياً.');

            return;
        }

        $cartCurrency = collect($this->cart)->first()['currency'] ?? null;
        if ($cartCurrency && $cartCurrency !== $product->currency) {
            $this->addError('cart', 'أكمل الفاتورة الحالية أولاً؛ لا يمكن جمع YER و SAR في فاتورة واحدة.');

            return;
        }

        $this->cart[$key] = [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => (string) ($product->barcode ?? ''),
            'currency' => $product->currency,
            'price' => (float) $product->selling_price,
            'stock' => (int) $product->current_quantity,
            'quantity' => 1,
            'image_path' => $product->image_path,
        ];

        $this->resetErrorBag('cart');
    }

    public function increaseQuantity(int $productId): void
    {
        $key = (string) $productId;
        if (! isset($this->cart[$key])) {
            return;
        }

        $stock = (int) ($this->cart[$key]['stock'] ?? 0);
        $currentQty = (int) ($this->cart[$key]['quantity'] ?? 0);

        if ($currentQty >= $stock) {
            $this->addError('cart', 'وصلت إلى كامل الكمية المتاحة من «'.($this->cart[$key]['name'] ?? 'المنتج').'».');

            return;
        }

        $this->cart[$key]['quantity']++;
        $this->resetErrorBag('cart');
    }

    public function decreaseQuantity(int $productId): void
    {
        $key = (string) $productId;
        if (! isset($this->cart[$key])) {
            return;
        }

        if ((int) $this->cart[$key]['quantity'] <= 1) {
            unset($this->cart[$key]);
        } else {
            $this->cart[$key]['quantity']--;
        }

        $this->resetErrorBag('cart');
    }

    public function removeProduct(int $productId): void
    {
        unset($this->cart[(string) $productId]);
        $this->resetErrorBag('cart');
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discount_value = '0';
        $this->resetErrorBag('cart');
    }

    public function completeSale(InventoryService $service, PaymentPolicy $paymentPolicy): void
    {
        abort_unless($this->canCreate, 403);

        $this->discount_value = NumberFormatter::clean($this->discount_value);

        $this->validate([
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'transfer_service' => ['nullable', Rule::requiredIf($this->payment_method === 'transfer'), Rule::in(['العمقي', 'الكريمي', 'البسيري'])],
            'transfer_reference' => ['nullable', 'string', 'max:255', Rule::requiredIf(
                $this->payment_method === 'transfer' && $paymentPolicy->requiresTransferReference()
            )],
            'payment_proof' => [
                'nullable',
                Rule::requiredIf($this->payment_method === 'transfer' && $paymentPolicy->requiresProof()),
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
            'discount_value' => ['required', 'numeric', 'min:0'],
        ], [
            'transfer_service.required' => 'اختر جهة التحويل.',
            'transfer_reference.required' => 'أدخل مرجع التحويل قبل إتمام البيع.',
            'payment_proof.required' => 'أرفق سند التحويل قبل إتمام البيع.',
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'اختر منتجاً واحداً على الأقل.');

            return;
        }

        $currency = collect($this->cart)->first()['currency'] ?? 'YER';
        $discount = $this->canDiscount ? max(0, (float) $this->discount_value) : 0;

        $proofPath = $this->payment_method === 'transfer' && $this->payment_proof
            ? $this->payment_proof->store('sale-payment-proofs', 'local')
            : null;

        try {
            $sale = $service->createSale([
                'member_id' => $this->member_id ?: null,
                'customer_name' => trim($this->customer_name) ?: null,
                'currency' => $currency,
                'payment_method' => $this->payment_method,
                'transfer_service' => $this->payment_method === 'transfer' ? $this->transfer_service : null,
                'transfer_reference' => $this->payment_method === 'transfer' ? trim($this->transfer_reference) : null,
                'proof_path' => $proofPath,
                'discount_type' => $discount > 0 ? 'amount' : null,
                'discount_value' => $discount,
                'items' => collect($this->cart)->map(fn ($row) => [
                    'product_id' => (int) $row['id'],
                    'quantity' => (int) $row['quantity'],
                    'actual_unit_price' => null,
                ])->values()->all(),
            ], auth()->user());
        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        $this->lastCompletedSaleId = $sale->id;
        $this->forgetSalesStats();
        $this->resetSaleForm();
        session()->flash('success', 'تم البيع بنجاح · '.$sale->sale_number.' · '.number_format((float) $sale->total_amount, 0).' '.$sale->currency.' · تم خصم المخزون وتسجيل الإيراد في المالية.');
    }

    public function startCancel(int $saleId): void
    {
        abort_unless($this->canCancel, 403);
        $sale = Sale::findOrFail($saleId);
        abort_unless($sale->status === 'completed', 422);
        $this->cancelSaleId = $sale->id;
        $this->cancellation_reason = '';
    }

    public function cancel(InventoryService $service): void
    {
        abort_unless($this->canCancel, 403);
        $this->validate([
            'cancelSaleId' => ['required', 'integer', 'exists:sales,id'],
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $service->cancelSale(Sale::findOrFail($this->cancelSaleId), $this->cancellation_reason, auth()->user());
        $this->forgetSalesStats();
        $this->reset(['cancelSaleId', 'cancellation_reason']);
        session()->flash('success', 'تم إلغاء البيع وإرجاع الكميات للمخزون. العملية تبقى محفوظة في السجل للمراجعة.');
    }

    public function resetHistoryFilters(): void
    {
        $this->reset(['historySearch', 'statusFilter', 'currencyFilter']);
        $this->resetPage();
    }

    private function resetSaleForm(): void
    {
        $this->cart = [];
        $this->member_id = null;
        $this->customer_name = '';
        $this->payment_method = 'cash';
        $this->transfer_service = 'العمقي';
        $this->transfer_reference = '';
        $this->payment_proof = null;
        $this->discount_value = '0';
        $this->productSearch = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $now = CarbonImmutable::now('Asia/Aden');
        $todayStart = $now->startOfDay()->utc();
        $todayEnd = $now->endOfDay()->utc();
        $monthStart = $now->startOfMonth()->utc();
        $monthEnd = $now->endOfMonth()->utc();

        $productQuery = Product::query()
            ->with('category')
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->when(trim($this->productSearch) !== '', function ($query) {
                $term = '%'.trim($this->productSearch).'%';
                $query->where(fn ($q) => $q->where('name', 'ilike', $term)->orWhere('barcode', 'ilike', $term));
            })
            ->orderByDesc('updated_at');

        $salesQuery = Sale::query()
            ->with(['member', 'items.product'])
            ->when(trim($this->historySearch) !== '', function ($query) {
                $term = '%'.trim($this->historySearch).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('sale_number', 'ilike', $term)
                        ->orWhere('customer_name', 'ilike', $term)
                        ->orWhereHas('member', fn ($m) => $m->where('full_name', 'ilike', $term)->orWhere('membership_code', 'ilike', $term));
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->currencyFilter !== '', fn ($q) => $q->where('currency', $this->currencyFilter))
            ->latest('sold_at');

        $stats = Cache::remember(
            $this->salesStatsCacheKey($now),
            now()->addSeconds(45),
            function () use ($todayStart, $todayEnd, $monthStart, $monthEnd): array {
                $sales = DB::table('sales')
                    ->where('status', 'completed')
                    ->whereBetween('sold_at', [$monthStart, $monthEnd])
                    ->selectRaw('COUNT(CASE WHEN sold_at BETWEEN ? AND ? THEN 1 END) AS today_count', [$todayStart, $todayEnd])
                    ->selectRaw("COALESCE(SUM(CASE WHEN sold_at BETWEEN ? AND ? AND currency = 'YER' THEN total_amount ELSE 0 END), 0) AS today_yer", [$todayStart, $todayEnd])
                    ->selectRaw("COALESCE(SUM(CASE WHEN sold_at BETWEEN ? AND ? AND currency = 'SAR' THEN total_amount ELSE 0 END), 0) AS today_sar", [$todayStart, $todayEnd])
                    ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'YER' THEN total_amount ELSE 0 END), 0) AS month_yer")
                    ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'SAR' THEN total_amount ELSE 0 END), 0) AS month_sar")
                    ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'YER' THEN discount_amount ELSE 0 END), 0) AS discount_yer")
                    ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'SAR' THEN discount_amount ELSE 0 END), 0) AS discount_sar")
                    ->first();

                $items = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.status', 'completed')
                    ->whereBetween('sales.sold_at', [$monthStart, $monthEnd])
                    ->selectRaw("COALESCE(SUM(CASE WHEN sales.currency = 'YER' THEN (sale_items.actual_unit_price - sale_items.unit_cost) * sale_items.quantity ELSE 0 END), 0) AS gross_yer")
                    ->selectRaw("COALESCE(SUM(CASE WHEN sales.currency = 'SAR' THEN (sale_items.actual_unit_price - sale_items.unit_cost) * sale_items.quantity ELSE 0 END), 0) AS gross_sar")
                    ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) AS month_units')
                    ->first();

                return [
                    'todayCount' => (int) ($sales->today_count ?? 0),
                    'todayYER' => (float) ($sales->today_yer ?? 0),
                    'todaySAR' => (float) ($sales->today_sar ?? 0),
                    'monthYER' => (float) ($sales->month_yer ?? 0),
                    'monthSAR' => (float) ($sales->month_sar ?? 0),
                    'profitYER' => (float) ($items->gross_yer ?? 0) - (float) ($sales->discount_yer ?? 0),
                    'profitSAR' => (float) ($items->gross_sar ?? 0) - (float) ($sales->discount_sar ?? 0),
                    'monthUnits' => (int) ($items->month_units ?? 0),
                ];
            },
        );

        $cartSubtotal = collect($this->cart)->sum(fn ($row) => (float) $row['price'] * (int) $row['quantity']);
        $cartDiscount = $this->canDiscount ? min(max(0, (float) $this->discount_value), $cartSubtotal) : 0;
        $cartCurrency = collect($this->cart)->first()['currency'] ?? 'YER';

        return view('livewire.inventory.sales-index', [
            'products' => $productQuery->limit(18)->get(),
            'members' => Cache::remember(
                'winner-gym:sales:active-members',
                now()->addMinutes(5),
                fn () => Member::query()->where('status', 'active')->orderBy('full_name')->limit(500)->get(['id', 'full_name', 'membership_code']),
            ),
            'sales' => $salesQuery->paginate(12),
            'cartSubtotal' => $cartSubtotal,
            'cartDiscount' => $cartDiscount,
            'cartTotal' => max(0, $cartSubtotal - $cartDiscount),
            'cartCurrency' => $cartCurrency,
            ...$stats,
        ]);
    }

    private function salesStatsCacheKey(CarbonImmutable $now): string
    {
        return 'winner-gym:sales:stats:'.$now->format('Y-m-d');
    }

    private function forgetSalesStats(): void
    {
        Cache::forget($this->salesStatsCacheKey(CarbonImmutable::now('Asia/Aden')));
    }
}
