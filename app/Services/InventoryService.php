<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Member;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private AuditService $audit,
        private PermissionService $permissions,
        private PaymentPolicy $paymentPolicy,
    ) {}

    public function approvePurchase(Purchase $purchase, User $actor): Purchase
    {
        $this->authorizeAny($actor, ['purchases.manage', 'inventory.manage']);

        return DB::transaction(function () use ($purchase, $actor) {
            $purchase = Purchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'pending') {
                throw ValidationException::withMessages(['purchase' => 'لا يمكن اعتماد هذه العملية.']);
            }

            foreach ($purchase->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                if ($product->currency !== $purchase->currency) {
                    throw ValidationException::withMessages(['currency' => 'عملة المنتج لا تطابق عملة الشراء.']);
                }

                $before = (int) $product->current_quantity;
                $after = $before + (int) $item->quantity;
                $currentCost = (float) $product->purchase_cost;
                $receivedCost = (float) $item->unit_cost;
                $averageCost = $after > 0
                    ? round((($before * $currentCost) + ((int) $item->quantity * $receivedCost)) / $after, 2)
                    : $receivedCost;

                $product->update([
                    'current_quantity' => $after,
                    'purchase_cost' => $averageCost,
                ]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'purchase',
                    'quantity_delta' => $item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'created_by' => $actor->id,
                ]);
            }

            $purchase->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->log($actor, 'inventory', 'purchase.approved', $purchase);

            return $purchase->fresh('items');
        });
    }

    /** @param array<string, mixed> $data */
    public function createSale(array $data, User $actor): Sale
    {
        $this->authorizeAny($actor, ['sales.create', 'inventory.manage']);

        $items = $this->validateSaleItems($data['items'] ?? null);
        $currency = strtoupper(trim((string) ($data['currency'] ?? '')));
        if (! in_array($currency, ['YER', 'SAR'], true)) {
            throw ValidationException::withMessages(['currency' => 'عملة البيع غير صحيحة.']);
        }

        $payment = [
            'payment_method' => $data['payment_method'] ?? null,
            'transfer_service' => $data['transfer_service'] ?? null,
            'transfer_reference' => $data['transfer_reference'] ?? null,
            'proof_path' => $data['proof_path'] ?? null,
        ];
        $this->paymentPolicy->validate($payment);

        if ($payment['payment_method'] === 'cash') {
            $payment['transfer_service'] = null;
            $payment['transfer_reference'] = null;
            $payment['proof_path'] = null;
        }

        if (filled($data['member_id'] ?? null) && ! Member::query()->whereKey($data['member_id'])->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['member_id' => 'العضو المحدد غير موجود أو غير نشط.']);
        }

        $customerName = trim((string) ($data['customer_name'] ?? ''));
        if (mb_strlen($customerName) > 255) {
            throw ValidationException::withMessages(['customer_name' => 'اسم العميل أطول من الحد المسموح.']);
        }

        return DB::transaction(function () use ($data, $actor, $items, $currency, $payment, $customerName) {
            $subtotal = 0.0;
            $prepared = [];
            $canOverridePrice = $this->permissions->allows($actor, 'discounts.formal');

            foreach ($items as $row) {
                $product = Product::query()->lockForUpdate()->findOrFail($row['product_id']);
                $quantity = $row['quantity'];

                if ($product->status !== 'active') {
                    throw ValidationException::withMessages(['product' => "المنتج {$product->name} غير نشط ولا يمكن بيعه."]);
                }

                if ($product->current_quantity < $quantity) {
                    throw ValidationException::withMessages(['stock' => "المخزون غير كافٍ للمنتج {$product->name}."]);
                }

                if ($product->currency !== $currency) {
                    throw ValidationException::withMessages(['currency' => 'كل منتجات الفاتورة يجب أن تكون بنفس عملة البيع.']);
                }

                $originalPrice = round((float) $product->selling_price, 2);
                $actualPrice = $row['actual_unit_price'] === null
                    ? $originalPrice
                    : round((float) $row['actual_unit_price'], 2);

                if ($actualPrice < 0) {
                    throw ValidationException::withMessages(['price' => 'سعر البيع الفعلي لا يمكن أن يكون سالبًا.']);
                }

                $priceOverridden = abs($originalPrice - $actualPrice) > 0.009;
                if ($priceOverridden && ! $canOverridePrice) {
                    throw ValidationException::withMessages(['price' => 'لا تملك صلاحية تعديل سعر المنتج.']);
                }

                $lineTotal = round($actualPrice * $quantity, 2);
                $subtotal += $lineTotal;
                $prepared[] = [$product, $quantity, $originalPrice, $actualPrice, $lineTotal, $priceOverridden];
            }

            [$discountType, $discountValue, $discountAmount] = $this->calculateDiscount($data, $subtotal, $actor);

            $sale = Sale::create([
                'sale_number' => 'SALE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'member_id' => $data['member_id'] ?? null,
                'customer_name' => $customerName !== '' ? $customerName : null,
                'currency' => $currency,
                'subtotal' => round($subtotal, 2),
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'total_amount' => round($subtotal - $discountAmount, 2),
                'payment_method' => $payment['payment_method'],
                'transfer_service' => $payment['transfer_service'],
                'transfer_reference' => $payment['transfer_reference'],
                'proof_path' => $payment['proof_path'],
                'status' => 'completed',
                'sold_at' => now(),
                'created_by' => $actor->id,
            ]);

            foreach ($prepared as [$product, $quantity, $originalPrice, $actualPrice, $lineTotal, $priceOverridden]) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'original_unit_price' => $originalPrice,
                    'actual_unit_price' => $actualPrice,
                    'unit_cost' => $product->purchase_cost,
                    'line_total' => $lineTotal,
                    'price_overridden' => $priceOverridden,
                    'price_overridden_by' => $priceOverridden ? $actor->id : null,
                    'price_overridden_at' => $priceOverridden ? now() : null,
                ]);

                $before = (int) $product->current_quantity;
                $after = $before - $quantity;
                $product->update(['current_quantity' => $after]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'sale',
                    'quantity_delta' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $product->purchase_cost,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_by' => $actor->id,
                ]);
            }

            $this->audit->log($actor, 'inventory', 'sale.created', $sale);

            return $sale->load('items');
        });
    }

    public function cancelSale(Sale $sale, string $reason, User $actor): Sale
    {
        $this->authorizeAny($actor, ['sales.cancel', 'inventory.manage']);

        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'سبب الإلغاء يجب أن يكون بين 3 و2000 حرف.']);
        }

        return DB::transaction(function () use ($sale, $reason, $actor) {
            $sale = Sale::query()->with('items')->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'لا يمكن إلغاء هذه العملية.']);
            }

            foreach ($sale->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $before = (int) $product->current_quantity;
                $after = $before + (int) $item->quantity;

                $product->update(['current_quantity' => $after]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'sale_cancel',
                    'quantity_delta' => $item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_by' => $actor->id,
                    'notes' => $reason,
                ]);
            }

            $sale->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log($actor, 'inventory', 'sale.cancelled', $sale);

            return $sale->fresh('items');
        });
    }

    /** @return array<int, array{product_id: int, quantity: int, actual_unit_price: float|null}> */
    private function validateSaleItems(mixed $items): array
    {
        if (! is_array($items) || $items === [] || count($items) > 200) {
            throw ValidationException::withMessages(['items' => 'يجب أن تحتوي الفاتورة على منتج واحد إلى 200 منتج.']);
        }

        $prepared = [];
        $productIds = [];
        foreach ($items as $row) {
            if (! is_array($row) || ! is_numeric($row['product_id'] ?? null) || ! is_numeric($row['quantity'] ?? null)) {
                throw ValidationException::withMessages(['items' => 'بيانات أحد منتجات الفاتورة غير صحيحة.']);
            }

            $productId = (int) $row['product_id'];
            $quantity = (int) $row['quantity'];
            if ($productId <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages(['items' => 'المنتج والكمية يجب أن تكون قيمًا موجبة.']);
            }

            if (in_array($productId, $productIds, true)) {
                throw ValidationException::withMessages(['items' => 'لا يمكن تكرار المنتج نفسه في الفاتورة.']);
            }
            $productIds[] = $productId;

            $actualPrice = $row['actual_unit_price'] ?? null;
            if ($actualPrice !== null && ! is_numeric($actualPrice)) {
                throw ValidationException::withMessages(['price' => 'سعر البيع الفعلي غير صحيح.']);
            }

            $prepared[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'actual_unit_price' => $actualPrice !== null ? (float) $actualPrice : null,
            ];
        }

        return $prepared;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string|null, 1: float, 2: float}
     */
    private function calculateDiscount(array $data, float $subtotal, User $actor): array
    {
        $discountType = filled($data['discount_type'] ?? null) ? (string) $data['discount_type'] : null;
        if ($discountType !== null && ! in_array($discountType, ['percent', 'amount'], true)) {
            throw ValidationException::withMessages(['discount' => 'نوع الخصم غير صحيح.']);
        }

        $discountValue = round((float) ($data['discount_value'] ?? 0), 2);
        if ($discountValue < 0 || ($discountType === 'percent' && $discountValue > 100)) {
            throw ValidationException::withMessages(['discount' => 'قيمة الخصم غير صحيحة.']);
        }

        if ($discountType === null) {
            if ($discountValue > 0) {
                throw ValidationException::withMessages(['discount' => 'حدد نوع الخصم قبل إدخال قيمته.']);
            }

            return [null, 0.0, 0.0];
        }

        if (! $this->permissions->allows($actor, 'discounts.formal')) {
            throw ValidationException::withMessages(['discount' => 'لا تملك صلاحية الخصم الرسمي.']);
        }

        $discountAmount = $discountType === 'percent'
            ? round($subtotal * $discountValue / 100, 2)
            : min($discountValue, $subtotal);

        return [$discountType, $discountValue, $discountAmount];
    }

    /** @param array<int, string> $abilities */
    private function authorizeAny(User $actor, array $abilities): void
    {
        foreach ($abilities as $ability) {
            if ($this->permissions->allows($actor, $ability)) {
                return;
            }
        }

        throw new AuthorizationException('لا تملك صلاحية تنفيذ هذه العملية.');
    }
}
