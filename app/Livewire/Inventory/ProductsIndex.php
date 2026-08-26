<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\PermissionService;
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
#[Title('المخزون - WINNER GYM')]
class ProductsIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showQuickSaleModal = false;

    public ?int $editingProductId = null;

    public ?int $category_id = null;

    public string $name = '';

    public string $barcode = '';

    public string $purchase_cost = '0';

    public string $selling_price = '0';

    public string $currency = 'YER';

    public int $minimum_quantity = 0;

    public int $opening_quantity = 0;

    public string $notes = '';

    public ?TemporaryUploadedFile $product_image = null;

    public string $new_category_name = '';

    public string $saleProductSearch = '';

    /** @var array<int|string, array<string, mixed>> */
    public array $saleCart = [];

    public string $saleCustomerName = '';

    public string $salePaymentMethod = 'cash';

    public string $saleTransferReference = '';

    public string $saleDiscount = '0';

    public bool $canManageProducts = false;

    public bool $canManagePurchases = false;

    public bool $canViewPurchases = false;

    public bool $canCreateSales = false;

    public bool $canViewSales = false;

    public bool $canDiscountSales = false;

    public function mount(PermissionService $permissions): void
    {
        $user = auth()->user();

        abort_unless(
            $permissions->allows($user, 'products.view')
            || $permissions->allows($user, 'products.manage')
            || $permissions->allows($user, 'inventory.view')
            || $permissions->allows($user, 'inventory.manage')
            || $permissions->allows($user, 'purchases.view')
            || $permissions->allows($user, 'purchases.manage')
            || $permissions->allows($user, 'sales.view')
            || $permissions->allows($user, 'sales.create')
            || $permissions->allows($user, 'sales.cancel'),
            403
        );

        $this->canManageProducts = $permissions->allows($user, 'products.manage') || $permissions->allows($user, 'inventory.manage');
        $this->canManagePurchases = $permissions->allows($user, 'purchases.manage')
            || $permissions->allows($user, 'inventory.manage');
        $this->canViewPurchases = $this->canManagePurchases || $permissions->allows($user, 'purchases.view');
        $this->canCreateSales = $permissions->allows($user, 'sales.create')
            || $permissions->allows($user, 'inventory.manage');
        $this->canViewSales = $this->canCreateSales || $permissions->allows($user, 'sales.view') || $permissions->allows($user, 'sales.cancel');
        $this->canDiscountSales = $permissions->allows($user, 'discounts.formal') || $user->role === 'owner';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSalePaymentMethod(string $value): void
    {
        if ($value === 'cash') {
            $this->saleTransferReference = '';
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        abort_unless($this->canManageProducts, 403);
        $this->resetProductForm();
        $this->showCreateModal = true;
    }

    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetValidation();
        $this->resetProductForm();
    }

    public function create(PermissionService $permissions, AuditService $audit): void
    {
        abort_unless(
            $permissions->allows(auth()->user(), 'products.manage')
            || $permissions->allows(auth()->user(), 'inventory.manage'),
            403
        );

        $validated = $this->validate($this->createRules(), $this->validationMessages());
        $category = $this->resolveCategory($validated);
        $imagePath = $this->product_image?->store('product-images', 'public');

        $product = DB::transaction(function () use ($validated, $category, $imagePath, $audit): Product {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => trim($validated['name']),
                'image_path' => $imagePath,
                'barcode' => trim((string) ($validated['barcode'] ?? '')) ?: null,
                'purchase_cost' => $validated['purchase_cost'],
                'selling_price' => $validated['selling_price'],
                'currency' => $validated['currency'],
                'current_quantity' => (int) $validated['opening_quantity'],
                'minimum_quantity' => $validated['minimum_quantity'],
                'status' => 'active',
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]);

            if ((int) $validated['opening_quantity'] > 0) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'opening_balance',
                    'quantity_delta' => (int) $validated['opening_quantity'],
                    'quantity_before' => 0,
                    'quantity_after' => (int) $validated['opening_quantity'],
                    'unit_cost' => $validated['purchase_cost'],
                    'reference_type' => 'product',
                    'reference_id' => $product->id,
                    'created_by' => auth()->id(),
                    'notes' => 'رصيد افتتاحي عند إضافة المنتج',
                    'created_at' => now(),
                ]);
            }

            $audit->log(auth()->user(), 'inventory', 'product.created', $product);

            return $product;
        });

        $opening = (int) $validated['opening_quantity'];
        $this->closeModal();
        $this->dispatch('product-saved');
        session()->flash('success', $opening > 0
            ? 'تم إنشاء المنتج وإضافة الرصيد الافتتاحي للمخزون بنجاح.'
            : 'تم إنشاء المنتج. لإضافة كمية لاحقاً استخدم «شراء جديد» حتى تبقى حركة المخزون صحيحة.');
    }

    public function openEdit(int $productId): void
    {
        abort_unless($this->canManageProducts, 403);

        $product = Product::findOrFail($productId);
        $this->editingProductId = $product->id;
        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->barcode = (string) ($product->barcode ?? '');
        $this->purchase_cost = (string) $product->purchase_cost;
        $this->selling_price = (string) $product->selling_price;
        $this->currency = $product->currency;
        $this->minimum_quantity = (int) $product->minimum_quantity;
        $this->notes = (string) ($product->notes ?? '');
        $this->product_image = null;
        $this->new_category_name = '';
        $this->showEditModal = true;
    }

    public function updateProduct(PermissionService $permissions, AuditService $audit): void
    {
        abort_unless(
            $permissions->allows(auth()->user(), 'products.manage')
            || $permissions->allows(auth()->user(), 'inventory.manage'),
            403
        );

        $product = Product::findOrFail($this->editingProductId);
        $validated = $this->validate([
            'category_id' => ['nullable', 'required_without:new_category_name', 'integer', 'exists:product_categories,id'],
            'new_category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'product_image' => ['nullable', 'image', 'max:2048'],
        ], $this->validationMessages());

        $category = $this->resolveCategory($validated);
        $before = $product->only(['category_id', 'name', 'barcode', 'selling_price', 'minimum_quantity', 'status', 'notes', 'image_path']);
        $payload = [
            'category_id' => $category->id,
            'name' => $validated['name'],
            'barcode' => $validated['barcode'] ?: null,
            'selling_price' => $validated['selling_price'],
            'minimum_quantity' => $validated['minimum_quantity'],
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->product_image) {
            $payload['image_path'] = $this->product_image->store('product-images', 'public');
        }

        $product->update($payload);
        $audit->log(auth()->user(), 'inventory', 'product.updated', $product, $before, $product->fresh()->only(array_keys($before)));

        $this->closeModal();
        session()->flash('success', 'تم تحديث بيانات المنتج. الكمية وتكلفة الشراء تتغيران فقط عبر حركات المخزون والمشتريات المعتمدة.');
    }

    public function toggle(int $productId, PermissionService $permissions, AuditService $audit): void
    {
        abort_unless(
            $permissions->allows(auth()->user(), 'products.manage')
            || $permissions->allows(auth()->user(), 'inventory.manage'),
            403
        );

        $product = Product::findOrFail($productId);
        $old = ['status' => $product->status];
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);
        $audit->log(auth()->user(), 'inventory', 'product.status_changed', $product, $old, ['status' => $product->status]);
    }

    public function openQuickSale(?int $productId = null): void
    {
        abort_unless($this->canCreateSales, 403);
        $this->resetQuickSale();
        $this->showQuickSaleModal = true;

        if ($productId) {
            $this->addSaleProduct($productId);
        }
    }

    public function closeQuickSale(): void
    {
        $this->showQuickSaleModal = false;
        $this->resetValidation();
        $this->resetQuickSale();
    }

    public function addSaleProduct(int $productId): void
    {
        abort_unless($this->canCreateSales, 403);

        $product = Product::query()->where('status', 'active')->findOrFail($productId);

        if ((int) $product->current_quantity <= 0) {
            $this->addError('quickSale', 'هذا المنتج غير متوفر حالياً في المخزون.');

            return;
        }

        $cartCurrency = collect($this->saleCart)->first()['currency'] ?? null;
        if ($cartCurrency && $cartCurrency !== $product->currency) {
            $this->addError('quickSale', 'لا يمكن جمع منتجات بعملتين مختلفتين في فاتورة بيع واحدة. أكمل الفاتورة الحالية أولاً.');

            return;
        }

        $key = (string) $product->id;
        if (isset($this->saleCart[$key])) {
            if ($this->saleCart[$key]['quantity'] >= (int) $product->current_quantity) {
                $this->addError('quickSale', 'وصلت للكمية المتاحة من هذا المنتج.');

                return;
            }
            $this->saleCart[$key]['quantity']++;
        } else {
            $this->saleCart[$key] = [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => (string) ($product->barcode ?? ''),
                'currency' => $product->currency,
                'price' => (float) $product->selling_price,
                'cost' => (float) $product->purchase_cost,
                'stock' => (int) $product->current_quantity,
                'quantity' => 1,
            ];
        }

        $this->resetErrorBag('quickSale');
    }

    public function increaseSaleQuantity(int $productId): void
    {
        $key = (string) $productId;
        if (! isset($this->saleCart[$key])) {
            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);
        if (! $product || $this->saleCart[$key]['quantity'] >= (int) $product->current_quantity) {
            $this->addError('quickSale', 'لا توجد كمية إضافية متاحة من هذا المنتج.');

            return;
        }

        $this->saleCart[$key]['quantity']++;
        $this->resetErrorBag('quickSale');
    }

    public function decreaseSaleQuantity(int $productId): void
    {
        $key = (string) $productId;
        if (! isset($this->saleCart[$key])) {
            return;
        }

        if ($this->saleCart[$key]['quantity'] <= 1) {
            unset($this->saleCart[$key]);

            return;
        }

        $this->saleCart[$key]['quantity']--;
    }

    public function removeSaleProduct(int $productId): void
    {
        unset($this->saleCart[(string) $productId]);
    }

    public function completeQuickSale(PermissionService $permissions, InventoryService $service): void
    {
        abort_unless(
            $permissions->allows(auth()->user(), 'sales.create')
            || $permissions->allows(auth()->user(), 'inventory.manage'),
            403
        );

        $validated = $this->validate([
            'saleCustomerName' => ['nullable', 'string', 'max:255'],
            'salePaymentMethod' => ['required', Rule::in(['cash', 'transfer'])],
            'saleTransferReference' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->salePaymentMethod === 'transfer')],
            'saleDiscount' => ['required', 'numeric', 'min:0'],
        ], [
            'saleTransferReference.required' => 'أدخل رقم أو مرجع التحويل.',
            'saleDiscount.numeric' => 'قيمة الخصم يجب أن تكون رقماً صحيحاً.',
        ]);

        if (empty($this->saleCart)) {
            $this->addError('quickSale', 'أضف منتجاً واحداً على الأقل إلى سلة البيع.');

            return;
        }

        $currency = collect($this->saleCart)->first()['currency'] ?? 'YER';
        $discount = $this->canDiscountSales ? max(0, (float) $validated['saleDiscount']) : 0;

        $sale = $service->createSale([
            'member_id' => null,
            'customer_name' => trim((string) ($validated['saleCustomerName'] ?? '')) ?: null,
            'currency' => $currency,
            'payment_method' => $validated['salePaymentMethod'],
            'transfer_reference' => $validated['salePaymentMethod'] === 'transfer'
                ? trim((string) ($validated['saleTransferReference'] ?? ''))
                : null,
            'discount_type' => $discount > 0 ? 'amount' : null,
            'discount_value' => $discount,
            'items' => collect($this->saleCart)->map(fn ($row) => [
                'product_id' => (int) $row['id'],
                'quantity' => (int) $row['quantity'],
                'actual_unit_price' => null,
            ])->values()->all(),
        ], auth()->user());

        $this->showQuickSaleModal = false;
        $this->resetQuickSale();
        session()->flash(
            'success',
            'تم البيع بنجاح · '.$sale->sale_number.' · '.number_format((float) $sale->total_amount, 0).' '.$sale->currency.' · تم خصم الكمية وتسجيل العملية في المالية.'
        );
    }

    /** @return array<string, list<mixed>> */
    private function createRules(): array
    {
        return [
            'category_id' => ['nullable', 'required_without:new_category_name', 'integer', 'exists:product_categories,id'],
            'new_category_name' => ['nullable', 'required_without:category_id', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'purchase_cost' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'minimum_quantity' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'product_image' => ['nullable', 'image', 'max:2048'],
            'opening_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @param array<string, mixed> $validated */
    private function resolveCategory(array $validated): ProductCategory
    {
        $newName = trim((string) ($validated['new_category_name'] ?? ''));

        if ($newName !== '') {
            $category = ProductCategory::firstOrCreate(
                ['name' => $newName],
                ['is_active' => true],
            );

            if (! $category->is_active) {
                $category->update(['is_active' => true]);
            }

            return $category;
        }

        return ProductCategory::query()->findOrFail((int) $validated['category_id']);
    }

    /** @return array<string, string> */
    private function validationMessages(): array
    {
        return [
            'category_id.required_without' => 'اختر تصنيفاً أو اكتب اسم تصنيف جديد.',
            'new_category_name.required_without' => 'اختر تصنيفاً أو اكتب اسم تصنيف جديد.',
            'name.required' => 'أدخل اسم المنتج.',
            'barcode.unique' => 'هذا الباركود مستخدم لمنتج آخر.',
            'purchase_cost.required' => 'أدخل تكلفة الشراء.',
            'selling_price.required' => 'أدخل سعر البيع.',
            'product_image.image' => 'ملف صورة المنتج يجب أن يكون صورة صالحة.',
            'product_image.max' => 'حجم صورة المنتج يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }

    private function resetProductForm(): void
    {
        $this->reset(['editingProductId', 'category_id', 'new_category_name', 'name', 'barcode', 'notes', 'product_image']);
        $this->purchase_cost = '0';
        $this->selling_price = '0';
        $this->currency = 'YER';
        $this->minimum_quantity = 0;
        $this->opening_quantity = 0;
    }

    private function resetQuickSale(): void
    {
        $this->saleProductSearch = '';
        $this->saleCart = [];
        $this->saleCustomerName = '';
        $this->salePaymentMethod = 'cash';
        $this->saleTransferReference = '';
        $this->saleDiscount = '0';
    }

    public function render(): View
    {
        $categories = ProductCategory::query()->where('is_active', true)->orderBy('name')->get();

        $productsQuery = Product::query()
            ->with('category')
            ->when($this->search !== '', function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('barcode', 'ilike', $term);
                });
            })
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category_id', (int) $this->categoryFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('status', 'inactive'))
            ->when($this->statusFilter === 'low', fn ($q) => $q->where('status', 'active')->whereColumn('current_quantity', '<=', 'minimum_quantity'))
            ->orderByRaw("CASE WHEN status = 'active' AND current_quantity <= minimum_quantity THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at');

        $summary = Product::query()
            ->selectRaw('COUNT(*) AS total_products')
            ->selectRaw('COALESCE(SUM(current_quantity), 0) AS total_units')
            ->selectRaw("COUNT(*) FILTER (WHERE status = 'active' AND current_quantity <= minimum_quantity) AS low_stock_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'YER' THEN current_quantity * purchase_cost ELSE 0 END), 0) AS inventory_value_yer")
            ->selectRaw("COALESCE(SUM(CASE WHEN currency = 'SAR' THEN current_quantity * purchase_cost ELSE 0 END), 0) AS inventory_value_sar")
            ->first();

        $totalProducts = (int) ($summary->total_products ?? 0);
        $totalUnits = (int) ($summary->total_units ?? 0);
        $lowStockCount = (int) ($summary->low_stock_count ?? 0);

        $lowStockAlerts = Product::query()
            ->with('category')
            ->where('status', 'active')
            ->whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->orderBy('current_quantity')
            ->limit(5)
            ->get();

        $categoryIds = $categories->pluck('id');
        $categoryCounts = Product::query()
            ->when($categoryIds->isNotEmpty(), fn ($q) => $q->whereIn('category_id', $categoryIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categoryPalette = ['#1378ff', '#12c97a', '#f4a000', '#ff4655', '#7b61ff', '#12b7d8'];
        $distribution = [];
        $gradientParts = [];
        $cursor = 0.0;
        $totalForDistribution = max(1, (int) $categoryCounts->sum());
        foreach ($categories as $index => $category) {
            $count = (int) ($categoryCounts[$category->id] ?? 0);
            if ($count === 0) {
                continue;
            }
            $percent = ($count / $totalForDistribution) * 100;
            $color = $categoryPalette[$index % count($categoryPalette)];
            $distribution[] = compact('category', 'count', 'percent', 'color');
            $gradientParts[] = sprintf('%s %.2f%% %.2f%%', $color, $cursor, $cursor + $percent);
            $cursor += $percent;
        }
        $categoryGradient = $gradientParts ? 'conic-gradient('.implode(',', $gradientParts).')' : 'conic-gradient(#132235 0 100%)';

        return view('livewire.inventory.products-index', [
            'categories' => $categories,
            'products' => $productsQuery->paginate(10),
            'totalProducts' => $totalProducts,
            'totalUnits' => $totalUnits,
            'lowStockCount' => $lowStockCount,
            'inventoryValueYER' => (float) ($summary->inventory_value_yer ?? 0),
            'inventoryValueSAR' => (float) ($summary->inventory_value_sar ?? 0),
            'categorySuggestions' => ['مشروبات', 'مكملات غذائية', 'ملابس رياضية', 'أدوات تدريب', 'وجبات خفيفة', 'عناية شخصية', 'أخرى'],
            'lowStockAlerts' => $lowStockAlerts,
            'distribution' => $distribution,
            'categoryGradient' => $categoryGradient,
        ]);
    }
}
