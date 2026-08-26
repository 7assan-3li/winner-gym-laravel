<?php

namespace App\Livewire\Nutrition;

use App\Models\Appointment;
use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Member;
use App\Models\NutritionClient;
use App\Models\User;
use App\Services\MeasurementService;
use App\Services\PermissionService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('القياسات - WINNER GYM')]
class MeasurementsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $client_type = 'member';

    public ?int $member_id = null;

    public ?int $nutrition_client_id = null;

    public ?int $nutritionist_id = null;

    public ?int $appointment_id = null;

    public string $notes = '';

    /** @var array<string, mixed> */
    public array $values = [];

    public bool $showMeasurementModal = false;

    public bool $canRecordMeasurements = false;

    public function mount(PermissionService $permissions): void
    {
        $user = auth()->user();

        abort_unless(
            $user->role === 'owner'
            || $user->role === 'manager'
            || $permissions->allows($user, 'measurements.own')
            || $permissions->allows($user, 'appointments.manage'),
            403
        );

        $this->canRecordMeasurements = true;
        if ($user->role === 'nutritionist') {
            $this->nutritionist_id = $user->id;
        }

        $appointmentId = (int) request()->query('appointment', 0);
        if ($appointmentId > 0) {
            $this->prefillFromAppointment($appointmentId);
            $this->showMeasurementModal = true;
        }
    }

    public function updatedSearch(): void
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

    public function openMeasurement(?int $appointmentId = null): void
    {
        $this->resetMeasurementForm();
        if ($appointmentId) {
            $this->prefillFromAppointment($appointmentId);
        }
        $this->showMeasurementModal = true;
    }

    public function closeMeasurement(): void
    {
        $this->showMeasurementModal = false;
        $this->resetValidation();
        $this->resetMeasurementForm();
    }

    public function save(MeasurementService $service): void
    {
        $user = auth()->user();

        $this->validate([
            'client_type' => ['required', 'in:member,external'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'nutrition_client_id' => ['nullable', 'integer', 'exists:nutrition_clients,id'],
            'nutritionist_id' => ['required', 'integer', 'exists:users,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($user->role === 'nutritionist' && (int) $this->nutritionist_id !== $user->id) {
            abort(403);
        }

        $memberId = $this->client_type === 'member' ? $this->member_id : null;
        $clientId = $this->client_type === 'external' ? $this->nutrition_client_id : null;

        if (! $memberId && ! $clientId) {
            $this->addError('client', 'اختر العضو أو عميل التغذية غير العضو.');

            return;
        }

        $hasValue = collect($this->values)->contains(fn ($v) => $v !== null && $v !== '');
        if (! $hasValue) {
            $this->addError('values', 'أدخل قياساً واحداً على الأقل.');

            return;
        }

        if ($this->appointment_id) {
            $appointment = Appointment::findOrFail($this->appointment_id);
            if ($appointment->member_id !== $memberId || $appointment->nutrition_client_id !== $clientId) {
                $this->addError('appointment_id', 'الموعد المحدد لا يخص هذا العميل.');

                return;
            }
        }

        $service->record([
            'appointment_id' => $this->appointment_id,
            'member_id' => $memberId,
            'nutrition_client_id' => $clientId,
            'nutritionist_id' => $this->nutritionist_id,
            'notes' => $this->notes ?: null,
            'values' => $this->values,
        ], $user);

        $this->showMeasurementModal = false;
        $this->resetMeasurementForm();
        session()->flash('success', 'تم حفظ القياسات وحساب BMI تلقائياً عند توفر الوزن والطول.');
    }

    private function prefillFromAppointment(int $appointmentId): void
    {
        $query = Appointment::query();
        if (auth()->user()->role === 'nutritionist') {
            $query->where('nutritionist_id', auth()->id());
        }
        $appointment = $query->findOrFail($appointmentId);
        $this->appointment_id = $appointment->id;
        $this->nutritionist_id = $appointment->nutritionist_id;
        if ($appointment->member_id) {
            $this->client_type = 'member';
            $this->member_id = $appointment->member_id;
            $this->nutrition_client_id = null;
        } else {
            $this->client_type = 'external';
            $this->nutrition_client_id = $appointment->nutrition_client_id;
            $this->member_id = null;
        }
    }

    private function resetMeasurementForm(): void
    {
        $user = auth()->user();
        $this->client_type = 'member';
        $this->member_id = null;
        $this->nutrition_client_id = null;
        $this->nutritionist_id = $user->role === 'nutritionist' ? $user->id : null;
        $this->appointment_id = null;
        $this->notes = '';
        $this->values = [];
    }

    public function render(): View
    {
        $user = auth()->user();
        $query = Measurement::query()->with(['member', 'nutritionClient', 'values.type']);

        if ($user->role === 'nutritionist') {
            $query->where('nutritionist_id', $user->id);
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('member', fn ($m) => $m->where('full_name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('membership_code', 'like', $term))
                    ->orWhereHas('nutritionClient', fn ($c) => $c->where('full_name', 'like', $term)->orWhere('phone', 'like', $term));
            });
        }

        $latest = (clone $query)->latest('measured_at')->first();
        if ($latest) {
            $latest->loadMissing('values.type', 'member', 'nutritionClient');
        }

        return view('livewire.nutrition.measurements-index', [
            'measurements' => $query->latest('measured_at')->paginate(10),
            'latestMeasurement' => $latest,
            'latestValues' => $latest?->values?->mapWithKeys(fn ($v) => [$v->type->code => $v])->all() ?? [],
            'types' => MeasurementType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'members' => Member::query()->where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'membership_code', 'phone']),
            'clients' => NutritionClient::query()->orderBy('full_name')->get(['id', 'full_name', 'phone']),
            'nutritionists' => User::query()->where('role', 'nutritionist')->where('is_active', true)->orderByRaw('COALESCE(name, username)')->get(['id', 'name', 'username']),
        ]);
    }
}
