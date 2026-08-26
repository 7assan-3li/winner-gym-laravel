<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\NutritionistSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        private AuditService $audit,
        private PaymentPolicy $paymentPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function book(array $data, User $actor): Appointment
    {
        try {
            return DB::transaction(function () use ($data, $actor) {
                $nutritionist = User::findOrFail((int) $data['nutritionist_id']);

                if ($nutritionist->role !== 'nutritionist' || ! $nutritionist->is_active) {
                    throw ValidationException::withMessages(['nutritionist_id' => 'اختصاصي التغذية غير متاح.']);
                }

                $date = CarbonImmutable::parse($data['appointment_date'], 'Asia/Aden');
                $startCarbon = CarbonImmutable::parse($date->toDateString().' '.$data['start_time'], 'Asia/Aden');
                $duration = (int) $data['duration_minutes'];
                $endCarbon = $startCarbon->addMinutes($duration);

                if (! $endCarbon->isSameDay($startCarbon) || $endCarbon->format('H:i:s') <= $startCarbon->format('H:i:s')) {
                    throw ValidationException::withMessages([
                        'start_time' => 'يجب أن تنتهي جلسة الموعد قبل منتصف الليل في نفس اليوم (قبل 23:59). يرجى اختيار وقت أبكر أو تقليل مدة الجلسة.',
                    ]);
                }

                $start = $startCarbon->format('H:i:s');
                $end = $endCarbon->format('H:i:s');

                $this->assertNotPast($date, $start);

                $day = $date->dayOfWeek;

                $insideSchedule = NutritionistSchedule::query()
                    ->where('nutritionist_id', $nutritionist->id)
                    ->where('day_of_week', $day)
                    ->where('is_active', true)
                    ->whereTime('start_time', '<=', $start)
                    ->whereTime('end_time', '>=', $end)
                    ->exists();

                if (! $insideSchedule) {
                    throw ValidationException::withMessages(['appointment' => 'الموعد خارج جدول اختصاصي التغذية.']);
                }

                $this->assertNoBookingConflict($data, $nutritionist, $date, $start, $end);

                $appointment = Appointment::create([
                    'nutritionist_id' => $nutritionist->id,
                    'member_id' => $data['member_id'] ?? null,
                    'nutrition_client_id' => $data['nutrition_client_id'] ?? null,
                    'appointment_date' => $date->toDateString(),
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_minutes' => $duration,
                    'service_type' => $data['service_type'],
                    'visit_type' => $data['visit_type'],
                    'price' => $data['price'],
                    'currency' => strtoupper($data['currency']),
                    'status' => 'booked',
                    'payment_status' => 'unpaid',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $actor->id,
                ]);

                $this->audit->log($actor, 'nutrition', 'appointment.created', $appointment);

                return $appointment;
            });
        } catch (QueryException $exception) {
            $this->throwFriendlyDuplicateError($exception);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function pay(Appointment $appointment, array $data, User $actor): AppointmentPayment
    {
        if (! is_numeric($data['amount'] ?? null)) {
            throw ValidationException::withMessages(['amount' => 'مبلغ الدفع غير صحيح.']);
        }

        $this->paymentPolicy->validate($data);
        if ($data['payment_method'] === 'cash') {
            $data['transfer_service'] = null;
            $data['transfer_reference'] = null;
            $data['proof_path'] = null;
        }

        return DB::transaction(function () use ($appointment, $data, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if ($appointment->payment_status === 'paid') {
                throw ValidationException::withMessages(['payment' => 'الموعد مدفوع بالفعل.']);
            }

            if ($appointment->status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'لا يمكن تحصيل قيمة موعد ملغي.']);
            }

            if (strtoupper((string) ($data['currency'] ?? '')) !== $appointment->currency) {
                throw ValidationException::withMessages(['currency' => 'عملة الدفع لا تطابق عملة الموعد.']);
            }

            if (abs((float) $data['amount'] - (float) $appointment->price) > 0.009) {
                throw ValidationException::withMessages(['amount' => 'يجب دفع قيمة الموعد كاملة.']);
            }

            $payment = AppointmentPayment::create([
                'appointment_id' => $appointment->id,
                'amount' => $appointment->price,
                'currency' => $appointment->currency,
                'payment_method' => $data['payment_method'],
                'transfer_service' => $data['transfer_service'] ?? null,
                'transfer_reference' => $data['transfer_reference'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
                'created_by' => $actor->id,
            ]);

            $appointment->update([
                'payment_status' => 'paid',
                'status' => $appointment->status === 'booked' ? 'confirmed' : $appointment->status,
            ]);

            $this->audit->log($actor, 'finance', 'appointment.payment.created', $payment);

            return $payment;
        });
    }

    public function confirm(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن تأكيد هذا الموعد في حالته الحالية.']);
            }

            $appointment->update(['status' => 'confirmed']);
            $this->audit->log($actor, 'nutrition', 'appointment.confirmed', $appointment);

            return $appointment->fresh();
        });
    }

    public function markNoShow(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن تسجيل عدم الحضور لهذا الموعد.']);
            }

            $sessionStart = CarbonImmutable::parse(
                $appointment->appointment_date->toDateString().' '.$appointment->start_time,
                'Asia/Aden',
            );
            if (CarbonImmutable::now('Asia/Aden')->lt($sessionStart)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن تسجيل عدم الحضور قبل وقت الموعد.']);
            }

            $appointment->update(['status' => 'no_show']);
            $this->audit->log($actor, 'nutrition', 'appointment.no_show', $appointment);

            return $appointment->fresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function reschedule(Appointment $appointment, array $data, User $actor): Appointment
    {
        try {
            return DB::transaction(function () use ($appointment, $data, $actor) {
                $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

                if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true)) {
                    throw ValidationException::withMessages(['appointment' => 'لا يمكن تعديل موعد مكتمل أو ملغي أو مسجل كعدم حضور.']);
                }

                $nutritionist = User::findOrFail((int) $data['nutritionist_id']);
                if ($nutritionist->role !== 'nutritionist' || ! $nutritionist->is_active) {
                    throw ValidationException::withMessages(['nutritionist_id' => 'اختصاصي التغذية غير متاح.']);
                }

                $date = CarbonImmutable::parse($data['appointment_date'], 'Asia/Aden');
                $startCarbon = CarbonImmutable::parse($date->toDateString().' '.$data['start_time'], 'Asia/Aden');
                $duration = (int) $data['duration_minutes'];
                $endCarbon = $startCarbon->addMinutes($duration);

                if (! $endCarbon->isSameDay($startCarbon) || $endCarbon->format('H:i:s') <= $startCarbon->format('H:i:s')) {
                    throw ValidationException::withMessages([
                        'start_time' => 'يجب أن تنتهي جلسة الموعد قبل منتصف الليل في نفس اليوم (قبل 23:59). يرجى اختيار وقت أبكر أو تقليل مدة الجلسة.',
                    ]);
                }

                $start = $startCarbon->format('H:i:s');
                $end = $endCarbon->format('H:i:s');

                $this->assertNotPast($date, $start);

                $insideSchedule = NutritionistSchedule::query()
                    ->where('nutritionist_id', $nutritionist->id)
                    ->where('day_of_week', $date->dayOfWeek)
                    ->where('is_active', true)
                    ->whereTime('start_time', '<=', $start)
                    ->whereTime('end_time', '>=', $end)
                    ->exists();

                if (! $insideSchedule) {
                    throw ValidationException::withMessages(['appointment' => 'الموعد خارج جدول اختصاصي التغذية.']);
                }

                $this->assertNoBookingConflict($data, $nutritionist, $date, $start, $end, $appointment->id);

                $before = $appointment->only([
                    'nutritionist_id', 'member_id', 'nutrition_client_id', 'appointment_date', 'start_time', 'end_time',
                    'duration_minutes', 'service_type', 'visit_type', 'price', 'currency', 'notes', 'status', 'payment_status',
                ]);

                $appointment->update([
                    'nutritionist_id' => $nutritionist->id,
                    'member_id' => $data['member_id'] ?? null,
                    'nutrition_client_id' => $data['nutrition_client_id'] ?? null,
                    'appointment_date' => $date->toDateString(),
                    'start_time' => $start,
                    'end_time' => $end,
                    'duration_minutes' => $duration,
                    'service_type' => $data['service_type'],
                    'visit_type' => $data['visit_type'],
                    'price' => $data['price'],
                    'currency' => strtoupper($data['currency']),
                    'notes' => $data['notes'] ?? null,
                ]);

                $this->audit->log($actor, 'nutrition', 'appointment.updated', $appointment, $before, $appointment->fresh()->only(array_keys($before)));

                return $appointment->fresh();
            });
        } catch (QueryException $exception) {
            $this->throwFriendlyDuplicateError($exception);

            throw $exception;
        }
    }

    public function reversePayment(Appointment $appointment, string $reason, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->with('payment')->findOrFail($appointment->id);

            if ($appointment->status === 'completed') {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن عكس دفعة جلسة مكتملة. راجع الإدارة المالية.']);
            }

            $payment = $appointment->payment;
            if (! $payment || $payment->status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'لا توجد دفعة فعالة لعكسها.']);
            }

            $payment->update([
                'status' => 'reversed',
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $appointment->update([
                'payment_status' => 'unpaid',
                'status' => $appointment->status === 'confirmed' ? 'booked' : $appointment->status,
            ]);

            $this->audit->log($actor, 'finance', 'appointment.payment.reversed', $payment);

            return $appointment->fresh();
        });
    }

    public function complete(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($appointment->status, ['booked', 'confirmed'], true)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن إكمال هذا الموعد في حالته الحالية.']);
            }

            if ($appointment->payment_status !== 'paid') {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن إكمال الموعد قبل الدفع.']);
            }

            $sessionEnd = CarbonImmutable::parse(
                $appointment->appointment_date->toDateString().' '.$appointment->end_time,
                'Asia/Aden',
            );

            if (CarbonImmutable::now('Asia/Aden')->lt($sessionEnd)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن إكمال الموعد قبل انتهاء الجلسة.']);
            }

            $appointment->update(['status' => 'completed', 'completed_at' => now()]);

            $this->audit->log($actor, 'nutrition', 'appointment.completed', $appointment);

            return $appointment->fresh();
        });
    }

    public function cancelUnpaid(Appointment $appointment, string $reason, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if ($appointment->payment_status === 'paid') {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن إلغاء موعد بعد الدفع.']);
            }

            if (! in_array($appointment->status, ['booked', 'confirmed'], true)) {
                throw ValidationException::withMessages(['appointment' => 'لا يمكن إلغاء هذا الموعد في حالته الحالية.']);
            }

            $appointment->update([
                'status' => 'cancelled',
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log($actor, 'nutrition', 'appointment.cancelled', $appointment);

            return $appointment->fresh();
        });
    }

    private function assertNotPast(CarbonImmutable $date, string $start): void
    {
        $slotStart = CarbonImmutable::parse($date->toDateString().' '.$start, 'Asia/Aden');

        if ($slotStart->lt(CarbonImmutable::now('Asia/Aden'))) {
            throw ValidationException::withMessages([
                'appointment_date' => 'اختر موعداً قادماً؛ لا يمكن حفظ موعد في وقت مضى.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function assertNoBookingConflict(
        array $data,
        User $nutritionist,
        CarbonImmutable $date,
        string $start,
        string $end,
        ?int $ignoreAppointmentId = null,
    ): void {
        $slotQuery = fn () => Appointment::query()
            ->when($ignoreAppointmentId, fn ($query) => $query->where('id', '<>', $ignoreAppointmentId))
            ->whereDate('appointment_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->whereTime('start_time', '<', $end)
            ->whereTime('end_time', '>', $start);

        $clientConflict = $slotQuery()
            ->where(function ($query) use ($data) {
                if (! empty($data['member_id'])) {
                    $query->where('member_id', $data['member_id']);
                } else {
                    $query->where('nutrition_client_id', $data['nutrition_client_id']);
                }
            })
            ->first(['id', 'start_time', 'end_time']);

        if ($clientConflict) {
            $sameStart = substr((string) $clientConflict->start_time, 0, 5) === substr($start, 0, 5);
            throw ValidationException::withMessages([
                'appointment' => $sameStart
                    ? "هذا العميل لديه الحجز نفسه بالفعل (رقم {$clientConflict->id})."
                    : 'هذا العميل لديه موعد آخر متداخل من '.substr((string) $clientConflict->start_time, 0, 5).' إلى '.substr((string) $clientConflict->end_time, 0, 5).'.',
            ]);
        }

        $nutritionistConflict = $slotQuery()
            ->where('nutritionist_id', $nutritionist->id)
            ->first(['id', 'start_time', 'end_time']);

        if ($nutritionistConflict) {
            throw ValidationException::withMessages([
                'appointment' => 'الأخصائي لديه حجز متداخل من '.substr((string) $nutritionistConflict->start_time, 0, 5).' إلى '.substr((string) $nutritionistConflict->end_time, 0, 5).'. اختر وقتاً متاحاً من المقترحات.',
            ]);
        }
    }

    private function throwFriendlyDuplicateError(QueryException $exception): void
    {
        $message = strtolower($exception->getMessage());
        $isUniqueViolation = in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains($message, 'appointment');

        if ($isUniqueViolation) {
            throw ValidationException::withMessages([
                'appointment' => 'هذا الحجز موجود بالفعل أو تم حجز الوقت قبل لحظات. حدّث الأوقات المتاحة واختر وقتاً آخر.',
            ]);
        }
    }
}
