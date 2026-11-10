<?php

namespace App\Livewire\Staff;

use App\Models\Branch;
use App\Models\GymPeriod;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('الموظفون - WINNER GYM')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public string $branchFilter = '';

    public bool $showEditor = false;

    public bool $showPassword = false;

    public ?int $editingId = null;

    public ?int $passwordUserId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'reception';

    public string $specialization = '';

    public string $work_period = 'both';

    public string $branch_id = '';

    public string $gym_period_id = '';

    public string $new_password = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        if (request()->string('role')->toString() === 'nutritionist') {
            $this->roleFilter = 'nutritionist';
            $this->role = 'nutritionist';
        }

        if (request()->boolean('new')) {
            $this->showEditor = true;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBranchFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = (string) $u->name;
        $this->username = (string) $u->username;
        $this->email = (string) ($u->email ?? '');
        $this->role = $u->role === 'owner' ? 'manager' : $u->role;
        $this->specialization = (string) ($u->specialization ?? '');
        $this->work_period = (string) ($u->work_period ?: 'both');
        $this->branch_id = (string) ($u->branch_id ?? '');
        $this->gym_period_id = (string) ($u->gym_period_id ?? '');
        $this->password = '';
        $this->showEditor = true;
    }

    public function save(StaffService $service): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($this->editingId)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role' => ['required', Rule::in(['manager', 'reception', 'accountant', 'nutritionist'])],
            'specialization' => ['nullable', 'string', 'max:120'],
            'work_period' => ['required', Rule::in(['men', 'women', 'both'])],
            'branch_id' => ['nullable', Rule::when(filled($this->branch_id), ['integer', 'exists:branches,id'])],
            'gym_period_id' => ['nullable', Rule::when(filled($this->gym_period_id), ['integer', 'exists:gym_periods,id'])],
        ];
        if (! $this->editingId) {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $messages = [
            'name.required' => 'حقل الاسم الكامل مطلوب.',
            'name.string' => 'الاسم يجب أن يكون نصًا صالحًا.',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفًا.',
            'username.required' => 'حقل اسم المستخدم مطلوب.',
            'username.min' => 'اسم المستخدم يجب ألا يقل عن 3 أحرف.',
            'username.max' => 'اسم المستخدم يجب ألا يتجاوز 60 حرفًا.',
            'username.regex' => 'اسم المستخدم يقبل فقط الحروف الإنجليزية والأرقام والنقاط والشرطات.',
            'username.unique' => 'اسم المستخدم هذا مستخدم مسبقًا.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'البريد الإلكتروني هذا مستخدم مسبقًا.',
            'password.required' => 'حقل كلمة المرور مطلوب.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'role.required' => 'حقل الدور الوظيفي مطلوب.',
            'role.in' => 'الدور الوظيفي المختار غير صالح.',
            'work_period.required' => 'حقل نطاق العمل مطلوب.',
            'work_period.in' => 'نطاق العمل المختار غير صالح.',
            'branch_id.integer' => 'الفرع المختار غير صالح.',
            'branch_id.exists' => 'الفرع المختار غير موجود في النظام.',
            'gym_period_id.integer' => 'الفترة المختارة غير صالحة.',
            'gym_period_id.exists' => 'الفترة المختارة غير موجودة في النظام.',
        ];

        try {
            $data = $this->validate($rules, $messages);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $msgs) {
                foreach ((array) $msgs as $msg) {
                    $this->addError($key, $msg);
                }
            }

            return;
        }

        $data['password'] = $this->password;
        $data['branch_id'] = filled($this->branch_id) ? (int) $this->branch_id : null;
        $data['gym_period_id'] = filled($this->gym_period_id) ? (int) $this->gym_period_id : null;

        if ($data['gym_period_id']) {
            $period = GymPeriod::find($data['gym_period_id']);
            if ($period && $data['branch_id'] && (int) $period->branch_id !== (int) $data['branch_id']) {
                $this->addError('gym_period_id', 'الفترة المختارة لا تتبع الفرع المحدد.');

                return;
            }
        }

        try {
            if ($this->editingId) {
                $service->update(User::findOrFail($this->editingId), $data, auth()->user());
                session()->flash('success', 'تم تحديث بيانات الموظف بنجاح.');
            } else {
                $service->create($data, auth()->user());
                session()->flash('success', 'تم إنشاء حساب الموظف بنجاح.');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $msgs) {
                foreach ((array) $msgs as $msg) {
                    $this->addError($key, $msg);
                }
            }

            return;
        } catch (\Throwable $e) {
            $this->addError('name', 'حدث خطأ أثناء حفظ البيانات: '.$e->getMessage());

            return;
        }

        $this->showEditor = false;
        $this->resetEditor();
    }

    public function toggle(int $id, StaffService $service): void
    {
        $service->toggle(User::findOrFail($id), auth()->user());
        session()->flash('success', 'تم تحديث حالة الحساب.');
    }

    public function openPassword(int $id): void
    {
        $this->passwordUserId = $id;
        $this->new_password = '';
        $this->showPassword = true;
    }

    public function resetPassword(StaffService $service): void
    {
        try {
            $this->validate(
                ['new_password' => ['required', 'string', 'min:8']],
                ['new_password.required' => 'حقل كلمة المرور مطلوب.', 'new_password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.']
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $msgs) {
                foreach ((array) $msgs as $msg) {
                    $this->addError($key, $msg);
                }
            }

            return;
        }

        try {
            $service->resetPassword(User::findOrFail($this->passwordUserId), $this->new_password, auth()->user());
            $this->showPassword = false;
            $this->new_password = '';
            session()->flash('success', 'تم تعيين كلمة مرور مؤقتة وسيطلب النظام تغييرها عند الدخول.');
        } catch (\Throwable $e) {
            $this->addError('new_password', 'تعذر إعادة تعيين كلمة المرور: '.$e->getMessage());
        }
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->resetValidation();
    }

    private function resetEditor(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'reception';
        $this->specialization = '';
        $this->work_period = 'both';
        $this->branch_id = '';
        $this->gym_period_id = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['branch', 'gymPeriod'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($x) => $x->where('name', 'ilike', $term)->orWhere('username', 'ilike', $term)->orWhere('email', 'ilike', $term));
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->branchFilter !== '', fn ($q) => $q->where('branch_id', $this->branchFilter))
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(10);

        $branches = Branch::where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get();
        $periods = GymPeriod::with('branch')->where('is_active', true)->orderBy('branch_id')->orderBy('gender')->orderBy('slot_order')->get();

        return view('livewire.staff.index', [
            'users' => $users,
            'branches' => $branches,
            'periods' => $periods,
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'managers' => User::whereIn('role', ['owner', 'manager'])->where('is_active', true)->count(),
                'nutritionists' => User::where('role', 'nutritionist')->where('is_active', true)->count(),
                'reception' => User::where('role', 'reception')->where('is_active', true)->count(),
            ],
        ]);
    }
}
