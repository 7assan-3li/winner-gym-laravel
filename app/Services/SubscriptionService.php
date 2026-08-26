<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\SubscriptionInstallment;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    private const CURRENT_STATUSES = ['active', 'financial_overdue', 'expiring_soon'];

    public function __construct(
        private AuditService $audit,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Subscription
    {
        return DB::transaction(function () use ($data, $actor) {
            $memberId = $this->requiredPositiveId($data, 'member_id');
            $packageId = $this->requiredPositiveId($data, 'package_id');
            $member = Member::query()->lockForUpdate()->findOrFail($memberId);
            $package = Package::query()->findOrFail($packageId);

            if ($member->status !== 'active') {
                throw ValidationException::withMessages(['member_id' => 'لا يمكن إنشاء اشتراك لعضو غير نشط.']);
            }

            if (($data['period'] ?? null) !== $member->assigned_period) {
                throw ValidationException::withMessages(['period' => 'فترة الاشتراك لا تطابق فترة العضو.']);
            }
            if (! $package->is_active) {
                throw ValidationException::withMessages(['package_id' => 'الباقة غير مفعلة.']);
            }

            $currency = strtoupper($data['currency']);
            $price = match ($currency) {
                'YER' => $package->price_yer,
                'SAR' => $package->price_sar,
                default => null,
            };

            if ($price === null) {
                throw ValidationException::withMessages(['currency' => 'لا يوجد سعر لهذه العملة في الباقة.']);
            }

            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            $finalPrice = round((float) $price - $discount, 2);

            if ($discount < 0 || $finalPrice < 0) {
                throw ValidationException::withMessages(['discount_amount' => 'قيمة الخصم غير صحيحة.']);
            }

            $requestedStart = CarbonImmutable::parse($data['start_date'], 'Asia/Aden')->startOfDay();

            $latestScheduled = Subscription::query()
                ->where('member_id', $member->id)
                ->whereIn('status', [...self::CURRENT_STATUSES, 'upcoming'])
                ->lockForUpdate()
                ->latest('end_date')
                ->first();

            if ($latestScheduled && $requestedStart->lte($latestScheduled->end_date)) {
                $requestedStart = CarbonImmutable::parse($latestScheduled->end_date)->addDay();
            }

            $endDate = $this->calculateEndDate(
                $requestedStart,
                (int) $package->duration_value,
                $package->duration_unit,
            );

            $plan = $data['payment_plan'] ?? null;
            if (! in_array($plan, ['full', 'installments'], true)) {
                throw ValidationException::withMessages(['payment_plan' => 'خطة الدفع غير صحيحة.']);
            }
            $count = $plan === 'full'
                ? 1
                : (int) ($data['installment_count'] ?? 0);
            $firstPayment = round((float) ($data['first_payment_amount'] ?? 0), 2);

            $this->paymentPolicy->validate($data);
            $isTransfer = $data['payment_method'] === 'transfer';

            if ($finalPrice <= 0) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'يجب أن تكون قيمة الاشتراك النهائية أكبر من صفر.',
                ]);
            }

            if ($firstPayment <= 0 || $firstPayment - $finalPrice > 0.009) {
                throw ValidationException::withMessages([
                    'first_payment_amount' => 'قيمة الدفعة الأولى يجب أن تكون أكبر من صفر ولا تتجاوز قيمة الاشتراك.',
                ]);
            }

            if ($plan === 'full' && abs($firstPayment - $finalPrice) > 0.009) {
                throw ValidationException::withMessages([
                    'first_payment_amount' => 'الدفع الكامل يجب أن يساوي قيمة الاشتراك النهائية.',
                ]);
            }

            if ($plan === 'installments') {
                if ($count < 2 || $count > 24) {
                    throw ValidationException::withMessages(['installment_count' => 'عدد دفعات التقسيط يجب أن يكون بين 2 و24.']);
                }

                if ($firstPayment + 0.009 >= $finalPrice) {
                    throw ValidationException::withMessages([
                        'first_payment_amount' => 'خطة التقسيط تتطلب بقاء مبلغ على قسط لاحق.',
                    ]);
                }

                if ($firstPayment + 0.009 < round($finalPrice * 0.5, 2)) {
                    throw ValidationException::withMessages([
                        'first_payment_amount' => 'الدفعة الأولى يجب ألا تقل عن 50% من قيمة الاشتراك.',
                    ]);
                }
            }

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'package_id' => $package->id,
                'package_name_snapshot' => $package->name,
                'duration_value_snapshot' => $package->duration_value,
                'duration_unit_snapshot' => $package->duration_unit,
                'period' => $data['period'],
                'start_date' => $requestedStart->toDateString(),
                'end_date' => $endDate->toDateString(),
                'currency' => $currency,
                'price_snapshot' => $price,
                'discount_amount' => $discount,
                'final_price' => $finalPrice,
                'payment_plan' => $plan,
                'installment_count' => $count,
                'status' => $requestedStart->isFuture() ? 'upcoming' : 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $dueDates = $data['installment_due_dates'] ?? [];
            if (! is_array($dueDates)) {
                throw ValidationException::withMessages(['installment_due_dates' => 'صيغة مواعيد الأقساط غير صحيحة.']);
            }
            $this->createInstallments($subscription, $firstPayment, $count, $dueDates);

            $firstInstallment = $subscription->installments()->where('installment_number', 1)->firstOrFail();

            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'installment_id' => $firstInstallment->id,
                'amount' => $firstPayment,
                'currency' => $currency,
                'payment_method' => $data['payment_method'],
                'transfer_service' => $isTransfer ? ($data['transfer_service'] ?? null) : null,
                'transfer_reference' => $isTransfer ? ($data['transfer_reference'] ?? null) : null,
                'proof_path' => $isTransfer ? ($data['proof_path'] ?? null) : null,
                'receipt_number' => $this->receiptNumber(),
                'status' => 'completed',
                'paid_at' => now(),
                'created_by' => $actor->id,
            ]);

            $firstInstallment->update(['status' => 'paid', 'paid_at' => now()]);

            $this->audit->log($actor, 'subscription', 'subscription.created', $subscription, null, $subscription->toArray());
            $this->audit->log($actor, 'finance', 'subscription.payment.created', $payment, null, $payment->toArray());

            return $subscription->load(['member', 'package', 'installments', 'payments']);
        });
    }

    public function refreshFinancialStatus(Subscription $subscription): Subscription
    {
        if (in_array($subscription->status, ['cancelled', 'refunded'], true)) {
            return $subscription->fresh();
        }

        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $todayDate = $today->toDateString();

        if ($subscription->end_date->lt($today)) {
            $subscription->update(['status' => 'expired']);

            return $subscription->fresh();
        }

        if ($subscription->start_date->gt($today)) {
            $subscription->update(['status' => 'upcoming']);

            return $subscription->fresh();
        }

        $hasOverdue = $subscription->installments()
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_date', '<', $todayDate)
            ->exists();

        if ($hasOverdue) {
            $subscription->installments()
                ->where('status', 'pending')
                ->whereDate('due_date', '<', $todayDate)
                ->update(['status' => 'overdue']);
            $subscription->update(['status' => 'financial_overdue']);
        } else {
            $expiringSoonDate = $today->addDays(
                max(1, (int) config('winner-gym.subscriptions.expiring_soon_days', 7)),
            );
            $subscription->update([
                'status' => $subscription->end_date->lte($expiringSoonDate)
                    ? 'expiring_soon'
                    : 'active',
            ]);
        }

        return $subscription->fresh();
    }

    /** @param array<int, mixed> $dueDates */
    private function createInstallments(
        Subscription $subscription,
        float $firstPayment,
        int $count,
        array $dueDates,
    ): void {
        SubscriptionInstallment::create([
            'subscription_id' => $subscription->id,
            'installment_number' => 1,
            'due_date' => $subscription->start_date,
            'amount' => $firstPayment,
            'status' => 'pending',
        ]);

        if ($count === 1) {
            return;
        }

        if (count($dueDates) !== $count - 1) {
            throw ValidationException::withMessages([
                'installment_due_dates' => 'يجب تحديد موعد لكل دفعة متبقية.',
            ]);
        }

        $remaining = round((float) $subscription->final_price - $firstPayment, 2);
        $base = floor(($remaining / ($count - 1)) * 100) / 100;
        $allocated = 0.0;
        $previousDueDate = CarbonImmutable::parse($subscription->start_date, 'Asia/Aden')->startOfDay();

        foreach (range(2, $count) as $number) {
            $dueDateValue = $dueDates[$number - 2] ?? null;
            if (! is_string($dueDateValue) || blank($dueDateValue)) {
                throw ValidationException::withMessages([
                    'installment_due_dates' => 'تاريخ كل قسط متبقٍ مطلوب.',
                ]);
            }

            try {
                $dueDate = CarbonImmutable::parse($dueDateValue, 'Asia/Aden')->startOfDay();
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'installment_due_dates' => 'أحد تواريخ الأقساط غير صحيح.',
                ]);
            }

            if ($dueDate->lte($previousDueDate)) {
                throw ValidationException::withMessages([
                    'installment_due_dates' => 'يجب أن يكون تاريخ كل قسط بعد القسط السابق.',
                ]);
            }
            $previousDueDate = $dueDate;

            $amount = $number === $count
                ? round($remaining - $allocated, 2)
                : $base;

            $allocated = round($allocated + $amount, 2);

            SubscriptionInstallment::create([
                'subscription_id' => $subscription->id,
                'installment_number' => $number,
                'due_date' => $dueDate->toDateString(),
                'amount' => $amount,
                'status' => 'pending',
            ]);
        }
    }

    private function calculateEndDate(CarbonImmutable $start, int $value, string $unit): CarbonImmutable
    {
        $exclusiveEnd = match ($unit) {
            'day' => $start->addDays($value),
            'week' => $start->addWeeks($value),
            'month' => $start->addMonthsNoOverflow($value),
            'year' => $start->addYearsNoOverflow($value),
            default => throw ValidationException::withMessages(['duration_unit' => 'وحدة مدة غير صحيحة.']),
        };

        return $exclusiveEnd->subDay();
    }

    /** @param array<string, mixed> $data */
    private function requiredPositiveId(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        if ((! is_int($value) && ! (is_string($value) && ctype_digit($value))) || (int) $value <= 0) {
            throw ValidationException::withMessages([$key => 'المعرّف المحدد غير صحيح.']);
        }

        return (int) $value;
    }

    private function receiptNumber(): string
    {
        return 'SUB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
    }
}
