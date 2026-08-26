<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الفروع - WINNER GYM')]
class BranchesIndex extends Component
{
    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $manager_name = '';

    public string $notes = '';

    public bool $is_main = false;

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function openCreate(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->editingId = $branch->id;
        foreach (['code', 'name', 'phone', 'address', 'manager_name', 'notes'] as $field) {
            $this->{$field} = (string) ($branch->{$field} ?? '');
        }
        $this->is_main = (bool) $branch->is_main;
        $this->is_active = (bool) $branch->is_active;
        $this->showEditor = true;
    }

    public function save(AuditService $audit): void
    {
        $rules = [
            'code' => ['required', 'string', 'max:40', Rule::unique('branches', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ];
        $data = $this->validate($rules);
        $data['code'] = strtoupper(trim($data['code']));

        if ($this->is_main) {
            Branch::query()->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))->update(['is_main' => false]);
        }

        if ($this->editingId) {
            $branch = Branch::findOrFail($this->editingId);
            $old = $branch->toArray();
            $branch->update($data);
            $audit->log(auth()->user(), 'administration', 'branch.updated', $branch, $old, $branch->fresh()->toArray());
            session()->flash('success', 'تم تحديث الفرع.');
        } else {
            $branch = Branch::create($data);
            $audit->log(auth()->user(), 'administration', 'branch.created', $branch);
            session()->flash('success', 'تم إنشاء الفرع.');
        }

        $this->showEditor = false;
        $this->resetEditor();
    }

    public function toggle(int $id, AuditService $audit): void
    {
        $branch = Branch::findOrFail($id);
        if ($branch->is_main && $branch->is_active) {
            $this->addError('branch', 'لا يمكن تعطيل الفرع الرئيسي. عيّن فرعًا رئيسيًا آخر أولًا.');

            return;
        }
        $branch->update(['is_active' => ! $branch->is_active]);
        $audit->log(auth()->user(), 'administration', $branch->is_active ? 'branch.activated' : 'branch.archived', $branch);
    }

    public function makeMain(int $id, AuditService $audit): void
    {
        Branch::where('is_main', true)->update(['is_main' => false]);
        $branch = Branch::findOrFail($id);
        $branch->update(['is_main' => true, 'is_active' => true]);
        $audit->log(auth()->user(), 'administration', 'branch.main_changed', $branch);
        session()->flash('success', 'تم تعيين الفرع الرئيسي.');
    }

    private function resetEditor(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->phone = '';
        $this->address = '';
        $this->manager_name = '';
        $this->notes = '';
        $this->is_main = false;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.branches-index', [
            'branches' => Branch::withCount(['users', 'periods'])->orderByDesc('is_main')->orderBy('name')->get(),
        ]);
    }
}
