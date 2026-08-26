<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private AuditService $audit,
        private PermissionService $permissions,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Expense
    {
        abort_unless($this->permissions->allows($actor, 'expenses.manage'), 403);

        if (blank($data['receipt_path'] ?? null)) {
            throw ValidationException::withMessages([
                'receipt' => 'إرفاق الفاتورة أو الإيصال مطلوب لحفظ المصروف.',
            ]);
        }

        $this->paymentPolicy->validate([
            ...$data,
            'proof_path' => $data['receipt_path'],
        ]);

        if (($data['payment_method'] ?? null) === 'cash') {
            $data['transfer_service'] = null;
            $data['transfer_reference'] = null;
        }

        if (! is_numeric($data['amount'] ?? null) || (float) $data['amount'] <= 0) {
            throw ValidationException::withMessages(['amount' => 'مبلغ المصروف يجب أن يكون أكبر من صفر.']);
        }

        $currency = strtoupper((string) ($data['currency'] ?? ''));
        if (! in_array($currency, ['YER', 'SAR'], true)) {
            throw ValidationException::withMessages(['currency' => 'عملة المصروف غير صحيحة.']);
        }

        return DB::transaction(function () use ($data, $actor, $currency) {
            $expense = Expense::create([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'amount' => round((float) $data['amount'], 2),
                'currency' => $currency,
                'expense_date' => $data['expense_date'],
                'payment_method' => $data['payment_method'],
                'transfer_service' => $data['transfer_service'] ?? null,
                'transfer_reference' => $data['transfer_reference'] ?? null,
                'receipt_path' => $data['receipt_path'],
                'notes' => $data['notes'] ?? null,
                'status' => 'approved',
                'created_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->log($actor, 'finance', 'expense.created', $expense, null, $expense->toArray());

            return $expense;
        });
    }

    public function cancel(Expense $expense, string $reason, User $actor): Expense
    {
        abort_unless($this->permissions->allows($actor, 'expenses.manage'), 403);

        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'سبب الإلغاء يجب أن يكون بين 3 و2000 حرف.']);
        }

        return DB::transaction(function () use ($expense, $reason, $actor) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);

            if ($expense->status === 'cancelled') {
                throw ValidationException::withMessages(['expense' => 'المصروف ملغي بالفعل.']);
            }

            $expense->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log($actor, 'finance', 'expense.cancelled', $expense);

            return $expense->fresh();
        });
    }
}
