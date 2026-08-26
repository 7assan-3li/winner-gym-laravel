<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private AuditService $audit,
        private SubscriptionService $subscriptions,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function payInstallment(SubscriptionInstallment $installment, array $data, User $actor): SubscriptionPayment
    {
        return DB::transaction(function () use ($installment, $data, $actor) {
            $installment = SubscriptionInstallment::query()->lockForUpdate()->findOrFail($installment->id);
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($installment->subscription_id);

            if (in_array($subscription->status, ['cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages([
                    'installment' => 'لا يمكن تسجيل دفعة على اشتراك ملغي أو مسترد.',
                ]);
            }

            if ($installment->status === 'paid') {
                throw ValidationException::withMessages(['installment' => 'هذه الدفعة مسددة بالفعل.']);
            }

            if (strtoupper($data['currency']) !== $subscription->currency) {
                throw ValidationException::withMessages(['currency' => 'عملة الدفع يجب أن تطابق عملة الاشتراك.']);
            }

            $amount = round((float) $data['amount'], 2);

            if (abs($amount - (float) $installment->amount) > 0.009) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفع يجب أن تساوي قيمة الدفعة المستحقة.']);
            }

            $this->paymentPolicy->validate($data);

            $isTransfer = $data['payment_method'] === 'transfer';

            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'amount' => $amount,
                'currency' => $subscription->currency,
                'payment_method' => $data['payment_method'],
                'transfer_service' => $isTransfer ? ($data['transfer_service'] ?? null) : null,
                'transfer_reference' => $isTransfer ? ($data['transfer_reference'] ?? null) : null,
                'proof_path' => $isTransfer ? ($data['proof_path'] ?? null) : null,
                'receipt_number' => 'SUB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
                'status' => 'completed',
                'paid_at' => now(),
                'created_by' => $actor->id,
            ]);

            $installment->update(['status' => 'paid', 'paid_at' => now()]);
            $this->subscriptions->refreshFinancialStatus($subscription);

            $this->audit->log($actor, 'finance', 'subscription.payment.created', $payment, null, $payment->toArray());

            return $payment;
        });
    }

    public function reverse(SubscriptionPayment $payment, string $reason, User $actor): SubscriptionPayment
    {
        return DB::transaction(function () use ($payment, $reason, $actor) {
            $payment = SubscriptionPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($payment->subscription_id);

            if ($payment->status === 'reversed') {
                throw ValidationException::withMessages(['payment' => 'تم عكس هذه الدفعة سابقًا.']);
            }

            if ($subscription->status === 'refunded' || $subscription->refund()->exists()) {
                throw ValidationException::withMessages([
                    'payment' => 'لا يمكن عكس دفعة بعد تسجيل استرداد للاشتراك.',
                ]);
            }

            $payment->update([
                'status' => 'reversed',
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            if ($payment->installment_id) {
                SubscriptionInstallment::whereKey($payment->installment_id)->update([
                    'status' => 'pending',
                    'paid_at' => null,
                ]);
            }

            $this->subscriptions->refreshFinancialStatus($subscription);

            $this->audit->log($actor, 'finance', 'subscription.payment.reversed', $payment, null, $payment->fresh()->toArray());

            return $payment->fresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function refund(Subscription $subscription, array $data, User $actor): SubscriptionRefund
    {
        return DB::transaction(function () use ($subscription, $data, $actor) {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if (in_array($subscription->status, ['cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'لا يمكن استرداد اشتراك ملغي أو مسترد.',
                ]);
            }

            if ($subscription->refund()->exists()) {
                throw ValidationException::withMessages(['refund' => 'تم تسجيل استرداد لهذا الاشتراك بالفعل.']);
            }

            $paid = (float) $subscription->payments()->where('status', 'completed')->sum('amount');
            $half = round((float) $subscription->final_price / 2, 2);
            $amount = min($half, $paid);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['refund' => 'لا يوجد مبلغ مدفوع قابل للاسترداد.']);
            }

            $this->paymentPolicy->validate($data);

            $isTransfer = $data['payment_method'] === 'transfer';

            $refund = SubscriptionRefund::create([
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => $subscription->currency,
                'payment_method' => $data['payment_method'],
                'transfer_service' => $isTransfer ? ($data['transfer_service'] ?? null) : null,
                'transfer_reference' => $isTransfer ? ($data['transfer_reference'] ?? null) : null,
                'proof_path' => $isTransfer ? ($data['proof_path'] ?? null) : null,
                'reason' => $data['reason'] ?? null,
                'status' => 'completed',
                'processed_by' => $actor->id,
                'processed_at' => now(),
            ]);

            $subscription->update(['status' => 'refunded']);

            $this->audit->log($actor, 'finance', 'subscription.refunded', $refund, null, $refund->toArray());

            return $refund;
        });
    }
}
