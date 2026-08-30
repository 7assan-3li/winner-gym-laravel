<?php

namespace App\Livewire\Nutrition;

use App\Models\Appointment;
use App\Models\Measurement;
use App\Models\Member;
use App\Models\NutritionClient;
use App\Models\NutritionistSchedule;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\AuditService;
use App\Services\PaymentPolicy;
use App\Services\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('التغذية - WINNER GYM')]
class AppointmentsIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $selectedDate = '';

    public string $statusFilter = '';

    public string $nutritionistFilter = '';

    public ?int $selectedAppointmentId = null;

    public bool $showBookingModal = false;

    public bool $showClientModal = false;

    public bool $showScheduleModal = false;

    public bool $showPaymentModal = false;

    public bool $showCancelModal = false;

    public bool $showReverseModal = false;

    public bool $showEditModal = false;

    public string $client_type = 'member';

    public ?int $member_id = null;

    public ?int $nutrition_client_id = null;

    public ?int $nutritionist_id = null;

    public string $appointment_date = '';

    public string $start_time = '16:00';

    public int $duration_minutes = 30;

    public string $service_type = 'consultation';

    public string $visit_type = 'in_person';

    public string $price = '';

    public string $currency = 'YER';

    public string $notes = '';

    public string $new_client_name = '';

    public string $new_client_phone = '';

    public string $new_client_gender = 'male';

    public string $new_client_notes = '';

    public string $payment_method = 'cash';

    public string $transfer_service = 'العمقي';

    public string $transfer_reference = '';

    public ?TemporaryUploadedFile $payment_proof = null;

    public string $cancellation_reason = '';

    public string $reversal_reason = '';

    public int $schedule_day = 0;

    public string $schedule_start = '16:00';

    public string $schedule_end = '20:00';

    public bool $canCreateAppointments = false;

    public bool $canManageAppointments = false;

    public bool $canManagePayments = false;

    public bool $canReversePayments = false;

    public bool $canManageSchedules = false;

    public bool $canCreateClients = false;

    public bool $canRecordMeasurements = false;

    public function mount(PermissionService $permissions): void
    {
        $user = auth()->user();

        abort_unless(
            $user->role === 'owner'
            || $permissions->allows($user, 'appointments.view')
            || $permissions->allows($user, 'appointments.create')
            || $permissions->allows($user, 'appointments.own')
            || $permissions->allows($user, 'appointments.manage'),
            403
        );

        $this->selectedDate = now('Asia/Aden')->toDateString();
        $this->appointment_date = $this->selectedDate;

        $this->canCreateAppointments = $user->role === 'owner'
            || $user->role === 'manager'
            || $permissions->allows($user, 'appointments.create')
            || $permissions->allows($user, 'appointments.own')
            || $permissions->allows($user, 'appointments.manage');

        $this->canManageAppointments = $user->role === 'owner'
            || $user->role === 'manager'
            || $permissions->allows($user, 'appointments.manage')
            || $permissions->allows($user, 'appointments.update_unpaid')
            || $permissions->allows($user, 'appointments.complete_own')
            || $permissions->allows($user, 'appointments.cancel_unpaid_own');

        $this->canManagePayments = in_array($user->role, ['owner', 'manager', 'accountant'], true)
            || $permissions->allows($user, 'payments.create')
            || $permissions->allows($user, 'appointments.manage');

        $this->canReversePayments = in_array($user->role, ['owner', 'manager'], true)
            || $permissions->allows($user, 'payments.reverse');

        $this->canManageSchedules = in_array($user->role, ['owner', 'manager'], true)
            || $user->role === 'nutritionist';

        $this->canCreateClients = in_array($user->role, ['owner', 'manager', 'nutritionist'], true)
            || $permissions->allows($user, 'nutrition_clients.create')
            || $permissions->allows($user, 'appointments.manage');

        $this->canRecordMeasurements = in_array($user->role, ['owner', 'manager'], true)
            || $permissions->allows($user, 'measurements.own')
            || $permissions->allows($user, 'appointments.manage');

        if ($user->role === 'nutritionist') {
            $this->nutritionist_id = $user->id;
            $this->nutritionistFilter = (string) $user->id;
        }

        if (request()->boolean('new') && $this->canCreateAppointments) {
            $this->showBookingModal = true;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedDate(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNutritionistFilter(): void
    {
        $this->resetPage();
    }

    public function updatedClientType(): void
    {
        if ($this->client_type === 'member') {
            $this->nutrition_client_id = null;
        } else {
            $this->member_id = null;
        }
    }

    public function updatedPaymentMethod(string $value): void
    {
        if ($value === 'cash') {
            $this->transfer_service = 'العمقي';
            $this->transfer_reference = '';
            $this->reset('payment_proof');
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        if (auth()->user()->role !== 'nutritionist') {
            $this->nutritionistFilter = '';
        }
        $this->selectedDate = now('Asia/Aden')->toDateString();
        $this->resetPage();
    }

    public function openBooking(): void
    {
        abort_unless($this->canCreateAppointments, 403);
        $this->resetBookingForm();
        $this->appointment_date = $this->selectedDate ?: now('Asia/Aden')->toDateString();
        $this->showBookingModal = true;
    }

    public function closeBooking(): void
    {
        $this->showBookingModal = false;
        $this->showEditModal = false;
        $this->resetValidation();
        $this->resetBookingForm();
    }

    public function openClient(): void
    {
        abort_unless($this->canCreateClients, 403);
        $this->resetValidation();
        $this->showClientModal = true;
    }

    public function closeClient(): void
    {
        $this->showClientModal = false;
        $this->resetValidation();
        $this->reset(['new_client_name', 'new_client_phone', 'new_client_notes']);
        $this->new_client_gender = 'male';
    }

    public function createClient(AuditService $audit): void
    {
        abort_unless($this->canCreateClients, 403);

        $this->new_client_phone = trim($this->new_client_phone);

        $validated = $this->validate([
            'new_client_name' => ['required', 'string', 'max:255'],
            'new_client_phone' => ['required', 'string', 'max:30', Rule::unique('nutrition_clients', 'phone')],
            'new_client_gender' => ['required', Rule::in(['male', 'female'])],
            'new_client_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $client = NutritionClient::create([
            'full_name' => $validated['new_client_name'],
            'phone' => $validated['new_client_phone'],
            'gender' => $validated['new_client_gender'],
            'notes' => $validated['new_client_notes'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $audit->log(auth()->user(), 'nutrition', 'nutrition_client.created', $client);
        $this->nutrition_client_id = $client->id;
        $this->client_type = 'external';
        $this->closeClient();
        $this->showBookingModal = true;
        $this->dispatch('nutrition-client-saved');
        session()->flash('success', 'تمت إضافة عميل التغذية غير العضو ويمكنك حجز موعد له الآن.');
    }

    public function openSchedule(?int $nutritionistId = null): void
    {
        abort_unless($this->canManageSchedules, 403);
        $this->resetValidation();

        if (auth()->user()->role === 'nutritionist') {
            $this->nutritionist_id = (int) auth()->id();
        } elseif ($nutritionistId && User::query()->where('role', 'nutritionist')->where('is_active', true)->whereKey($nutritionistId)->exists()) {
            $this->nutritionist_id = $nutritionistId;
        }

        $this->showScheduleModal = true;
    }

    public function closeSchedule(): void
    {
        $this->showScheduleModal = false;
        $this->resetValidation();
    }

    public function addSchedule(AuditService $audit): void
    {
        abort_unless($this->canManageSchedules, 403);
        $user = auth()->user();

        $validated = $this->validate([
            'nutritionist_id' => ['required', 'integer', 'exists:users,id'],
            'schedule_day' => ['required', 'integer', 'between:0,6'],
            'schedule_start' => ['required', 'date_format:H:i'],
            'schedule_end' => ['required', 'date_format:H:i', 'after:schedule_start'],
        ]);

        if ($user->role === 'nutritionist' && (int) $validated['nutritionist_id'] !== $user->id) {
            abort(403);
        }

        $nutritionist = User::query()->where('role', 'nutritionist')->where('is_active', true)->findOrFail((int) $validated['nutritionist_id']);

        $schedule = NutritionistSchedule::updateOrCreate([
            'nutritionist_id' => $nutritionist->id,
            'day_of_week' => $validated['schedule_day'],
            'start_time' => $validated['schedule_start'],
            'end_time' => $validated['schedule_end'],
        ], ['is_active' => true]);

        $audit->log($user, 'nutrition', 'nutrition_schedule.saved', $schedule);
        $this->showScheduleModal = false;
        $this->dispatch('nutrition-schedule-saved');
        session()->flash('success', 'تم حفظ فترة عمل اختصاصي التغذية.');
    }

    public function chooseSlot(string $time): void
    {
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->start_time = $time;
        }
    }

    public function book(AppointmentService $service, PermissionService $permissions): void
    {
        abort_unless($this->canCreateAppointments, 403);
        $user = auth()->user();

        $validated = $this->validate($this->bookingRules());
        $this->normalizeClientSelection($validated);

        if ($user->role === 'nutritionist' && (int) $validated['nutritionist_id'] !== $user->id) {
            abort(403);
        }

        $appointment = $service->book($validated, $user);
        $this->selectedDate = $appointment->appointment_date->toDateString();
        $this->selectedAppointmentId = $appointment->id;
        $this->statusFilter = '';
        $this->nutritionistFilter = '';
        $this->search = '';
        $this->showBookingModal = false;
        $this->resetBookingForm();
        $this->dispatch('nutrition-appointment-saved');
        session()->flash('success', 'تم حفظ وتأكيد حجز موعد التغذية بنجاح! رقم الموعد: #'.$appointment->id.' - يظهر الآن في الجدول أدناه.');
    }

    public function openEdit(int $appointmentId): void
    {
        abort_unless($this->canManageAppointments, 403);
        $appointment = $this->appointmentForActor($appointmentId);
        abort_if(in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true), 422, 'لا يمكن تعديل موعد مكتمل أو ملغي أو مسجل كعدم حضور.');
        $user = auth()->user();
        $hasBroadManage = in_array($user->role, ['owner', 'manager'], true) || $user->hasGymPermission('appointments.manage');
        if (! $hasBroadManage && $appointment->payment_status === 'paid') {
            abort(403);
        }

        $this->selectedAppointmentId = $appointment->id;
        $this->client_type = $appointment->member_id ? 'member' : 'external';
        $this->member_id = $appointment->member_id;
        $this->nutrition_client_id = $appointment->nutrition_client_id;
        $this->nutritionist_id = $appointment->nutritionist_id;
        $this->appointment_date = $appointment->appointment_date->toDateString();
        $this->start_time = substr((string) $appointment->start_time, 0, 5);
        $this->duration_minutes = (int) $appointment->duration_minutes;
        $this->service_type = (string) ($appointment->service_type ?: 'consultation');
        $this->visit_type = (string) ($appointment->visit_type ?: 'in_person');
        $this->price = (string) $appointment->price;
        $this->currency = $appointment->currency;
        $this->notes = (string) ($appointment->notes ?? '');
        $this->showEditModal = true;
    }

    public function updateAppointment(AppointmentService $service): void
    {
        abort_unless($this->canManageAppointments, 403);
        $appointment = $this->appointmentForActor((int) $this->selectedAppointmentId);
        $validated = $this->validate($this->bookingRules());
        $this->normalizeClientSelection($validated);

        if ($appointment->payment_status === 'paid') {
            $validated['price'] = (float) $appointment->price;
            $validated['currency'] = $appointment->currency;
        }

        $service->reschedule($appointment, $validated, auth()->user());
        $this->selectedDate = $validated['appointment_date'];
        $this->showEditModal = false;
        $this->resetBookingForm();
        $this->dispatch('nutrition-appointment-saved');
        session()->flash('success', 'تم تحديث الموعد بدون إنشاء حجز مكرر.');
    }

    public function selectAppointment(int $appointmentId): void
    {
        $appointment = $this->appointmentForActor($appointmentId);
        $this->selectedAppointmentId = $appointment->id;
    }

    public function confirm(int $appointmentId, AppointmentService $service): void
    {
        abort_unless($this->canManageAppointments, 403);
        $appointment = $this->appointmentForActor($appointmentId);
        $service->confirm($appointment, auth()->user());
        $this->selectedAppointmentId = $appointmentId;
        session()->flash('success', 'تم تأكيد الموعد.');
    }

    public function openPayment(int $appointmentId): void
    {
        abort_unless($this->canManagePayments, 403);
        $appointment = $this->appointmentForActor($appointmentId);
        abort_if($appointment->payment_status === 'paid', 422, 'هذا الموعد مدفوع بالفعل.');
        abort_if($appointment->status === 'cancelled', 422, 'لا يمكن تحصيل موعد ملغي.');

        $this->selectedAppointmentId = $appointmentId;
        $this->payment_method = 'cash';
        $this->transfer_service = 'العمقي';
        $this->transfer_reference = '';
        $this->payment_proof = null;
        $this->showPaymentModal = true;
    }

    public function closePayment(): void
    {
        $this->showPaymentModal = false;
        $this->resetValidation();
        $this->payment_method = 'cash';
        $this->transfer_service = 'العمقي';
        $this->transfer_reference = '';
        $this->payment_proof = null;
    }

    public function pay(AppointmentService $service, PaymentPolicy $paymentPolicy): void
    {
        abort_unless($this->canManagePayments, 403);
        $appointment = $this->appointmentForActor((int) $this->selectedAppointmentId);

        $validated = $this->validate([
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
        ]);

        $proofPath = $validated['payment_method'] === 'transfer' && $this->payment_proof
            ? $this->payment_proof->store('appointment-payment-proofs', 'local')
            : null;

        try {
            $service->pay($appointment, [
                'amount' => $appointment->price,
                'currency' => $appointment->currency,
                'payment_method' => $validated['payment_method'],
                'transfer_service' => $validated['payment_method'] === 'transfer' ? $validated['transfer_service'] : null,
                'transfer_reference' => $validated['payment_method'] === 'transfer' ? ($validated['transfer_reference'] ?: null) : null,
                'proof_path' => $proofPath,
            ], auth()->user());
        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        $this->closePayment();
        $this->dispatch('nutrition-payment-saved');
        session()->flash('success', 'تم استلام قيمة الموعد وتأكيد الحجز. ستظهر الإيرادات ضمن المالية.');
    }

    public function complete(int $appointmentId, AppointmentService $service): void
    {
        $user = auth()->user();
        $appointment = $this->appointmentForActor($appointmentId);
        $allowed = in_array($user->role, ['owner', 'manager'], true)
            || $user->hasGymPermission('appointments.manage')
            || ($user->hasGymPermission('appointments.complete_own') && $appointment->nutritionist_id === $user->id);
        abort_unless($allowed, 403);
        $service->complete($appointment, $user);
        $this->selectedAppointmentId = $appointmentId;
        session()->flash('success', 'تم إكمال جلسة التغذية. يمكنك الآن تسجيل القياسات المرتبطة بالعميل.');
    }

    public function markNoShow(int $appointmentId, AppointmentService $service): void
    {
        $user = auth()->user();
        $appointment = $this->appointmentForActor($appointmentId);
        $allowed = in_array($user->role, ['owner', 'manager', 'reception'], true)
            || $user->hasGymPermission('appointments.manage')
            || $user->hasGymPermission('appointments.update_unpaid')
            || ($user->hasGymPermission('appointments.complete_own') && $appointment->nutritionist_id === $user->id);
        abort_unless($allowed, 403);
        $service->markNoShow($appointment, $user);
        $this->selectedAppointmentId = $appointmentId;
        session()->flash('success', 'تم تسجيل العميل كـ لم يحضر.');
    }

    public function openCancel(int $appointmentId): void
    {
        abort_unless($this->canManageAppointments, 403);
        $appointment = $this->appointmentForActor($appointmentId);
        if ($appointment->payment_status === 'paid') {
            $this->addError('appointmentAction', 'الموعد مدفوع. اعكس الدفعة أولاً ثم يمكنك إلغاء الموعد.');

            return;
        }
        $this->selectedAppointmentId = $appointmentId;
        $this->cancellation_reason = '';
        $this->showCancelModal = true;
    }

    public function cancel(AppointmentService $service): void
    {
        abort_unless($this->canManageAppointments, 403);
        $validated = $this->validate(['cancellation_reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $appointment = $this->appointmentForActor((int) $this->selectedAppointmentId);
        $service->cancelUnpaid($appointment, $validated['cancellation_reason'], auth()->user());
        $this->showCancelModal = false;
        $this->cancellation_reason = '';
        session()->flash('success', 'تم إلغاء الموعد مع الاحتفاظ بسبب الإلغاء في السجل.');
    }

    public function openReversePayment(int $appointmentId): void
    {
        abort_unless($this->canReversePayments, 403);
        $appointment = $this->appointmentForActor($appointmentId);
        abort_unless($appointment->payment_status === 'paid', 422);
        $this->selectedAppointmentId = $appointmentId;
        $this->reversal_reason = '';
        $this->showReverseModal = true;
    }

    public function reversePayment(AppointmentService $service): void
    {
        abort_unless($this->canReversePayments, 403);
        $validated = $this->validate(['reversal_reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $appointment = $this->appointmentForActor((int) $this->selectedAppointmentId);
        $service->reversePayment($appointment, $validated['reversal_reason'], auth()->user());
        $this->showReverseModal = false;
        $this->reversal_reason = '';
        session()->flash('success', 'تم عكس دفعة الموعد وإعادته إلى غير مدفوع.');
    }

    public function gotoMeasurements(?int $appointmentId = null): void
    {
        $url = route('nutrition.measurements');
        if ($appointmentId) {
            $url .= '?appointment='.$appointmentId;
        }
        $this->redirect($url, navigate: true);
    }

    /** @return array<string, list<mixed>> */
    private function bookingRules(): array
    {
        return [
            'client_type' => ['required', Rule::in(['member', 'external'])],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'nutrition_client_id' => ['nullable', 'integer', 'exists:nutrition_clients,id'],
            'nutritionist_id' => ['required', 'integer', 'exists:users,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:'.now('Asia/Aden')->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'service_type' => ['required', Rule::in(['consultation', 'follow_up', 'body_analysis', 'meal_plan', 'measurement'])],
            'visit_type' => ['required', Rule::in(['in_person', 'remote'])],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['YER', 'SAR'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @param array<string, mixed> $validated */
    private function normalizeClientSelection(array &$validated): void
    {
        if ($validated['client_type'] === 'member') {
            $validated['nutrition_client_id'] = null;
            if (! $validated['member_id']) {
                $this->addError('member_id', 'اختر العضو.');
                throw ValidationException::withMessages(['member_id' => 'اختر العضو.']);
            }
        } else {
            $validated['member_id'] = null;
            if (! $validated['nutrition_client_id']) {
                $this->addError('nutrition_client_id', 'اختر عميل التغذية غير العضو.');
                throw ValidationException::withMessages(['nutrition_client_id' => 'اختر عميل التغذية غير العضو.']);
            }
        }
    }

    private function resetBookingForm(): void
    {
        $user = auth()->user();
        $this->client_type = 'member';
        $this->member_id = null;
        $this->nutrition_client_id = null;
        $this->nutritionist_id = $user->role === 'nutritionist' ? $user->id : null;
        $this->appointment_date = $this->selectedDate ?: now('Asia/Aden')->toDateString();
        $this->start_time = '16:00';
        $this->duration_minutes = 30;
        $this->service_type = 'consultation';
        $this->visit_type = 'in_person';
        $this->price = '';
        $this->currency = 'YER';
        $this->notes = '';
    }

    private function appointmentForActor(int $appointmentId): Appointment
    {
        $query = Appointment::query()->with(['member', 'nutritionClient', 'nutritionist', 'payment']);
        if (auth()->user()->role === 'nutritionist') {
            $query->where('nutritionist_id', auth()->id());
        }

        return $query->findOrFail($appointmentId);
    }

    /** @return list<string> */
    private function availableSlots(): array
    {
        if (! $this->nutritionist_id || ! $this->appointment_date || $this->duration_minutes < 10) {
            return [];
        }

        try {
            $date = CarbonImmutable::parse($this->appointment_date, 'Asia/Aden');
        } catch (\Throwable) {
            return [];
        }

        $schedules = NutritionistSchedule::query()
            ->where('nutritionist_id', $this->nutritionist_id)
            ->where('day_of_week', $date->dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $existing = Appointment::query()
            ->where('nutritionist_id', $this->nutritionist_id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->when($this->showEditModal && $this->selectedAppointmentId, fn ($q) => $q->where('id', '<>', $this->selectedAppointmentId))
            ->get(['start_time', 'end_time']);

        $slots = [];
        foreach ($schedules as $schedule) {
            $cursor = CarbonImmutable::parse($date->toDateString().' '.substr((string) $schedule->start_time, 0, 5), 'Asia/Aden');
            $periodEnd = CarbonImmutable::parse($date->toDateString().' '.substr((string) $schedule->end_time, 0, 5), 'Asia/Aden');

            while ($cursor->addMinutes($this->duration_minutes)->lte($periodEnd)) {
                $slotEnd = $cursor->addMinutes($this->duration_minutes);
                $overlap = $existing->contains(function ($appointment) use ($date, $cursor, $slotEnd) {
                    $existingStart = CarbonImmutable::parse($date->toDateString().' '.substr((string) $appointment->start_time, 0, 5), 'Asia/Aden');
                    $existingEnd = CarbonImmutable::parse($date->toDateString().' '.substr((string) $appointment->end_time, 0, 5), 'Asia/Aden');

                    return $existingStart->lt($slotEnd) && $existingEnd->gt($cursor);
                });

                $isPast = $date->isToday() && $cursor->lte(CarbonImmutable::now('Asia/Aden'));
                if (! $overlap && ! $isPast) {
                    $slots[] = $cursor->format('H:i');
                }
                $cursor = $cursor->addMinutes(30);
            }
        }

        return array_values(array_unique($slots));
    }

    /** @return array{measurement: Measurement|null, values: array<string, mixed>} */
    private function latestMeasurementData(): array
    {
        $query = Measurement::query()->with(['member', 'nutritionClient', 'values.type']);
        if (auth()->user()->role === 'nutritionist') {
            $query->where('nutritionist_id', auth()->id());
        }

        $measurement = $query->latest('measured_at')->first();
        if (! $measurement) {
            return ['measurement' => null, 'values' => []];
        }

        return [
            'measurement' => $measurement,
            'values' => $measurement->values->mapWithKeys(fn ($v) => [$v->type->code => $v])->all(),
        ];
    }

    public function render(): View
    {
        $user = auth()->user();
        $date = $this->selectedDate ?: now('Asia/Aden')->toDateString();

        $base = Appointment::query()->with(['nutritionist', 'member', 'nutritionClient', 'payment']);
        if ($user->role === 'nutritionist') {
            $base->where('nutritionist_id', $user->id);
        }

        $appointmentsQuery = (clone $base)
            ->whereDate('appointment_date', $date)
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->nutritionistFilter !== '', fn ($q) => $q->where('nutritionist_id', (int) $this->nutritionistFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($qq) use ($term) {
                    $qq->whereHas('member', fn ($m) => $m->where('full_name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('membership_code', 'like', $term))
                        ->orWhereHas('nutritionClient', fn ($c) => $c->where('full_name', 'like', $term)->orWhere('phone', 'like', $term));
                });
            })
            ->orderBy('start_time');

        $todayDate = now('Asia/Aden')->toDateString();
        $dayBase = (clone $base)->whereDate('appointment_date', $todayDate);
        $stats = [
            'total' => (clone $dayBase)->count(),
            'confirmed' => (clone $dayBase)->where('status', 'confirmed')->count(),
            'unpaid' => (clone $dayBase)->where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled'])->count(),
            'no_show' => (clone $dayBase)->where('status', 'no_show')->count(),
            'clients' => (clone $base)->whereNotNull('member_id')->distinct()->count('member_id') + (clone $base)->whereNotNull('nutrition_client_id')->distinct()->count('nutrition_client_id'),
        ];

        $now = CarbonImmutable::now('Asia/Aden');
        $upcoming = (clone $base)
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
            ->where(function ($q) use ($now) {
                $q->whereDate('appointment_date', '>', $now->toDateString())
                    ->orWhere(function ($qq) use ($now) {
                        $qq->whereDate('appointment_date', $now->toDateString())
                            ->whereTime('start_time', '>=', $now->format('H:i:s'));
                    });
            })
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $selectedAppointment = $this->selectedAppointmentId
            ? $this->appointmentForActor($this->selectedAppointmentId)
            : null;

        $nutritionists = User::query()
            ->where('role', 'nutritionist')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(name, username)')
            ->get(['id', 'name', 'username']);

        $latestMeasurement = $this->latestMeasurementData();

        return view('livewire.nutrition.appointments-index', [
            'appointments' => $appointmentsQuery->paginate(10),
            'stats' => $stats,
            'upcoming' => $upcoming,
            'selectedAppointment' => $selectedAppointment,
            'members' => ($this->showBookingModal || $this->showEditModal)
                ? Member::query()->where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'membership_code', 'phone'])
                : collect(),
            'clients' => ($this->showBookingModal || $this->showEditModal)
                ? NutritionClient::query()->orderBy('full_name')->get(['id', 'full_name', 'phone', 'gender'])
                : collect(),
            'nutritionists' => $nutritionists,
            'availableSlots' => ($this->showBookingModal || $this->showEditModal) ? $this->availableSlots() : [],
            'latestMeasurement' => $latestMeasurement['measurement'],
            'latestMeasurementValues' => $latestMeasurement['values'],
            'clinicClients' => NutritionClient::query()
                ->withCount('appointments')
                ->withMax('appointments', 'appointment_date')
                ->latest('id')
                ->limit(8)
                ->get(),
            'todaySchedules' => NutritionistSchedule::query()
                ->where('day_of_week', CarbonImmutable::now('Asia/Aden')->dayOfWeek)
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get()
                ->groupBy('nutritionist_id'),
        ]);
    }
}
