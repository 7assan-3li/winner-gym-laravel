<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\GymPeriod;
use App\Services\AuditService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الفترات - WINNER GYM')]
class PeriodsIndex extends Component
{
    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $branch_id = '';

    public string $name = '';

    public string $gender = 'men';

    public int $slot_order = 1;

    public string $start_time = '';

    public string $end_time = '';

    public string $notes = '';

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function openCreate(string $gender = 'men'): void
    {
        $this->resetEditor();
        $this->gender = in_array($gender, ['men', 'women'], true) ? $gender : 'men';
        $this->slot_order = (int) GymPeriod::where('gender', $this->gender)->max('slot_order') + 1;
        $mainBranchId = Branch::where('is_main', true)->value('id');
        $this->branch_id = $mainBranchId === null ? '' : (string) $mainBranchId;
        $this->name = ($this->gender === 'women' ? 'نساء' : 'رجال').' - فترة '.$this->slot_order;
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $period = GymPeriod::findOrFail($id);
        $this->editingId = $period->id;
        $this->branch_id = (string) ($period->branch_id ?? '');
        $this->name = $period->name;
        $this->gender = $period->gender;
        $this->slot_order = (int) $period->slot_order;
        $this->start_time = $period->start_time ? substr((string) $period->start_time, 0, 5) : '';
        $this->end_time = $period->end_time ? substr((string) $period->end_time, 0, 5) : '';
        $this->notes = (string) ($period->notes ?? '');
        $this->is_active = (bool) $period->is_active;
        $this->showEditor = true;
    }

    public function save(AuditService $audit): void
    {
        $data = $this->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['men', 'women'])],
            'slot_order' => ['required', 'integer', 'min:1', 'max:20'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        if (($this->start_time === '') xor ($this->end_time === '')) {
            $this->addError('start_time', 'حدد وقت البداية والنهاية معًا، أو اتركهما فارغين مؤقتًا.');

            return;
        }
        if ($this->start_time !== '' && $this->end_time <= $this->start_time) {
            $this->addError('end_time', 'وقت النهاية يجب أن يكون بعد وقت البداية.');

            return;
        }

        $duplicate = GymPeriod::query()
            ->where('branch_id', $this->branch_id)
            ->where('gender', $this->gender)
            ->where('slot_order', $this->slot_order)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->exists();
        if ($duplicate) {
            $this->addError('slot_order', 'رقم الفترة مستخدم بالفعل لنفس الجنس في هذا الفرع.');

            return;
        }

        if ($this->start_time !== '' && $this->hasOverlap()) {
            $this->addError('start_time', 'هذه الفترة تتداخل زمنيًا مع فترة أخرى من نفس الجنس في الفرع.');

            return;
        }

        $data['start_time'] = $this->start_time ?: null;
        $data['end_time'] = $this->end_time ?: null;

        if ($this->editingId) {
            $period = GymPeriod::findOrFail($this->editingId);
            $old = $period->toArray();
            $period->update($data);
            $audit->log(auth()->user(), 'administration', 'period.updated', $period, $old, $period->fresh()->toArray());
            session()->flash('success', 'تم تحديث الفترة.');
        } else {
            $period = GymPeriod::create($data);
            $audit->log(auth()->user(), 'administration', 'period.created', $period);
            session()->flash('success', 'تمت إضافة الفترة.');
        }

        $this->showEditor = false;
        $this->resetEditor();
    }

    public function toggle(int $id, AuditService $audit): void
    {
        $period = GymPeriod::findOrFail($id);
        $period->update(['is_active' => ! $period->is_active]);
        $audit->log(auth()->user(), 'administration', $period->is_active ? 'period.activated' : 'period.deactivated', $period);
    }

    private function hasOverlap(): bool
    {
        return GymPeriod::query()
            ->where('branch_id', $this->branch_id)
            ->where('gender', $this->gender)
            ->whereNotNull('start_time')->whereNotNull('end_time')
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->where(function ($q) {
                $q->where('start_time', '<', $this->end_time)
                    ->where('end_time', '>', $this->start_time);
            })->exists();
    }

    private function resetEditor(): void
    {
        $this->editingId = null;
        $this->branch_id = '';
        $this->name = '';
        $this->gender = 'men';
        $this->slot_order = 1;
        $this->start_time = '';
        $this->end_time = '';
        $this->notes = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.periods-index', [
            'branches' => Branch::where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get(),
            'periods' => GymPeriod::with(['branch'])->withCount('users')->orderBy('branch_id')->orderBy('gender')->orderBy('slot_order')->get(),
        ]);
    }
}
