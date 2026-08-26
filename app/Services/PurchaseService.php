<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        private AuditService $audit,
        private PermissionService $permissions,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Purchase
    {
        abort_unless(
            $this->permissions->allows($actor, 'purchases.manage')
            || $this->permissions->allows($actor, 'inventory.manage'),
            403
        );

        if (blank($data['proof_path'] ?? null)) {
            throw ValidationException::withMessages([
                'purchase_document' => 'إرفاق فاتورة المورد أو سند الشراء مطلوب.',
            ]);
        }

        $this->paymentPolicy->validate($data);
        if ($data['payment_method'] === 'cash') {
            $data['transfer_service'] = null;
            $data['transfer_reference'] = null;
        }

        if (blank($data['supplier_name'] ?? null) || blank($data['supplier_invoice'] ?? null)) {
            throw ValidationException::withMessages(['supplier' => 'اسم المورد ورقم فاتورته مطلوبان.']);
        }

        return DB::transaction(function () use ($data, $actor) {
            $currency = strtoupper((string) ($data['currency'] ?? ''));
            if (! in_array($currency, ['YER', 'SAR'], true)) {
                throw ValidationException::withMessages(['currency' => 'عملة الشراء غير صحيحة.']);
            }
            $prepared = [];
            $seen = [];

            if (! is_array($data['items'] ?? null) || count($data['items']) > 200) {
                throw ValidationException::withMessages(['items' => 'يجب أن تحتوي عملية الشراء على منتج واحد إلى 200 منتج.']);
            }

            foreach ($data['items'] as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $qty = (int) ($row['quantity'] ?? 0);
                $unitCost = round((float) ($row['unit_cost'] ?? 0), 2);

                if ($productId <= 0 || $qty <= 0 || $unitCost < 0) {
                    throw ValidationException::withMessages(['items' => 'تحقق من منتجات وكمية وتكلفة عملية الشراء.']);
                }

                if (isset($seen[$productId])) {
                    throw ValidationException::withMessages(['items' => 'لا تكرر نفس المنتج داخل عملية الشراء.']);
                }
                $seen[$productId] = true;

                $product = Product::query()->findOrFail($productId);

                if ($product->status !== 'active') {
                    throw ValidationException::withMessages(['items' => "المنتج {$product->name} غير نشط."]);
                }

                if ($product->currency !== $currency) {
                    throw ValidationException::withMessages(['currency' => "عملة المنتج {$product->name} لا تطابق عملة الشراء."]);
                }

                $prepared[] = [$product, $qty, $unitCost];
            }

            if ($prepared === []) {
                throw ValidationException::withMessages(['items' => 'أضف منتجًا واحدًا على الأقل.']);
            }

            $purchase = Purchase::create([
                'purchase_number' => 'PUR-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'purchase_date' => $data['purchase_date'],
                'supplier_name' => $data['supplier_name'],
                'supplier_invoice' => $data['supplier_invoice'],
                'currency' => $currency,
                'payment_method' => $data['payment_method'],
                'transfer_service' => $data['transfer_service'] ?? null,
                'transfer_reference' => $data['transfer_reference'] ?? null,
                'proof_path' => $data['proof_path'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($prepared as [$product, $qty, $unitCost]) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => round($qty * $unitCost, 2),
                ]);
            }

            $this->audit->log($actor, 'inventory', 'purchase.created', $purchase);

            return $purchase->load('items.product');
        });
    }

    public function cancel(Purchase $purchase, string $reason, User $actor): Purchase
    {
        abort_unless(
            $this->permissions->allows($actor, 'purchases.manage')
            || $this->permissions->allows($actor, 'inventory.manage'),
            403
        );

        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'سبب الإلغاء يجب أن يكون بين 3 و2000 حرف.']);
        }

        return DB::transaction(function () use ($purchase, $reason, $actor) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status !== 'pending') {
                throw ValidationException::withMessages([
                    'purchase' => 'يمكن إلغاء عملية الشراء قبل الاعتماد فقط.',
                ]);
            }

            $purchase->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log($actor, 'inventory', 'purchase.cancelled', $purchase);

            return $purchase->fresh();
        });
    }
}
