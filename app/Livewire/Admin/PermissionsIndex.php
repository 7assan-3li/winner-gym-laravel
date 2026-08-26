<?php

namespace App\Livewire\Admin;

use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الصلاحيات - WINNER GYM')]
class PermissionsIndex extends Component
{
    public string $selectedRole = 'manager';

    public string $search = '';

    public string $overrideUserId = '';

    public string $overrideAbility = '';

    public string $overrideValue = 'inherit';

    /** @var array<string, string> */
    public array $roles = [
        'manager' => 'المدير',
        'reception' => 'الاستقبال',
        'accountant' => 'المحاسب',
        'nutritionist' => 'اختصاصي التغذية',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function toggle(string $role, string $ability, AuditService $audit): void
    {
        abort_unless(isset($this->roles[$role]), 422);
        $permission = RolePermission::firstOrNew(['role' => $role, 'ability' => $ability]);
        $permission->allowed = ! (bool) ($permission->exists ? $permission->allowed : false);
        $permission->save();
        $audit->log(auth()->user(), 'security', 'role_permission.changed', $permission, null, [
            'role' => $role, 'ability' => $ability, 'allowed' => $permission->allowed,
        ]);
    }

    public function resetDefaults(AuditService $audit): void
    {
        $defaults = $this->defaultPermissions();
        DB::transaction(function () use ($defaults) {
            foreach (array_keys($this->roles) as $role) {
                RolePermission::where('role', $role)->update(['allowed' => false]);
                foreach ($defaults[$role] ?? [] as $ability) {
                    RolePermission::updateOrCreate(
                        ['role' => $role, 'ability' => $ability],
                        ['allowed' => true]
                    );
                }
            }
        });
        $audit->log(auth()->user(), 'security', 'role_permissions.reset', null);
        session()->flash('success', 'تمت استعادة الصلاحيات الافتراضية.');
    }

    public function saveOverride(AuditService $audit): void
    {
        $this->validate([
            'overrideUserId' => ['required', 'integer', 'exists:users,id'],
            'overrideAbility' => ['required', 'string', 'max:120'],
            'overrideValue' => ['required', 'in:inherit,allow,deny'],
        ]);

        $user = User::findOrFail($this->overrideUserId);
        abort_if($user->role === 'owner', 422, 'المالك يملك جميع الصلاحيات ولا يحتاج استثناءات.');

        if ($this->overrideValue === 'inherit') {
            UserPermission::where('user_id', $user->id)->where('ability', $this->overrideAbility)->delete();
        } else {
            UserPermission::updateOrCreate(
                ['user_id' => $user->id, 'ability' => $this->overrideAbility],
                [
                    'allowed' => $this->overrideValue === 'allow',
                    'granted_by' => auth()->id(),
                ]
            );
        }

        $audit->log(auth()->user(), 'security', 'user_permission.override', $user, null, [
            'ability' => $this->overrideAbility, 'value' => $this->overrideValue,
        ]);
        session()->flash('success', 'تم حفظ الاستثناء الخاص بالموظف.');
    }

    public function removeOverride(int $id, AuditService $audit): void
    {
        $override = UserPermission::findOrFail($id);
        $audit->log(auth()->user(), 'security', 'user_permission.removed', $override->user, null, ['ability' => $override->ability]);
        $override->delete();
    }

    public function render(): View
    {
        $abilities = $this->abilityCatalog();
        if ($this->search !== '') {
            $term = mb_strtolower($this->search);
            $abilities = collect($abilities)->map(function ($items) use ($term) {
                return array_filter($items, fn ($label, $ability) => str_contains(mb_strtolower($label.' '.$ability), $term), ARRAY_FILTER_USE_BOTH);
            })->filter()->all();
        }

        $permissions = RolePermission::get()->groupBy('role')->map(fn ($rows) => $rows->keyBy('ability'));
        $users = User::where('role', '!=', 'owner')->orderBy('name')->get(['id', 'name', 'username', 'role']);
        $overrides = UserPermission::with('user')->orderByDesc('id')->limit(30)->get();

        return view('livewire.admin.permissions-index', compact('abilities', 'permissions', 'users', 'overrides'));
    }

    /** @return array<string, array<string, string>> */
    private function abilityCatalog(): array
    {
        return [
            'الأعضاء' => [
                'members.view' => 'عرض بيانات الأعضاء', 'members.create' => 'إضافة عضو', 'members.update' => 'تعديل بيانات عضو', 'members.manage' => 'إدارة كاملة للأعضاء',
            ],
            'الاشتراكات والمدفوعات' => [
                'subscriptions.view' => 'عرض الاشتراكات', 'subscriptions.create' => 'إنشاء اشتراك', 'subscriptions.manage' => 'إدارة الاشتراكات', 'payments.view' => 'عرض المدفوعات', 'payments.create' => 'استلام دفعة', 'payments.reverse' => 'عكس دفعة', 'refunds.process' => 'معالجة استرداد',
            ],
            'الحضور' => [
                'attendance.view' => 'عرض سجل الحضور', 'attendance.record' => 'تسجيل حضور',
            ],
            'المالية' => [
                'expenses.view' => 'عرض المصروفات', 'expenses.manage' => 'إدارة المصروفات', 'reports.finance' => 'التقارير المالية', 'audit.financial' => 'تدقيق العمليات المالية',
            ],
            'المخزون والمبيعات' => [
                'products.view' => 'عرض المنتجات', 'products.manage' => 'إدارة المنتجات', 'inventory.view' => 'عرض المخزون', 'inventory.manage' => 'إدارة المخزون', 'purchases.view' => 'عرض المشتريات', 'purchases.manage' => 'إدارة المشتريات', 'sales.view' => 'عرض المبيعات', 'sales.create' => 'إنشاء بيع', 'sales.cancel' => 'إلغاء بيع', 'discounts.formal' => 'اعتماد الخصومات',
            ],
            'التغذية' => [
                'appointments.view' => 'عرض المواعيد', 'appointments.create' => 'حجز موعد', 'appointments.manage' => 'إدارة جميع المواعيد', 'appointments.update_unpaid' => 'تعديل موعد غير مدفوع', 'appointments.own' => 'مواعيدي فقط', 'appointments.complete_own' => 'إكمال مواعيدي', 'appointments.cancel_unpaid_own' => 'إلغاء مواعيدي غير المدفوعة', 'nutrition_clients.create' => 'إضافة عميل تغذية', 'measurements.own' => 'تسجيل القياسات', 'nutrition.view' => 'عرض قسم التغذية',
            ],
            'التقارير والإدارة' => [
                'reports.operational' => 'التقارير التشغيلية', 'staff.view' => 'عرض الموظفين', 'staff.manage' => 'إدارة الموظفين', 'branches.manage' => 'إدارة الفروع', 'periods.manage' => 'إدارة الفترات', 'whatsapp.manage' => 'إدارة واتساب', 'backups.manage' => 'إدارة النسخ الاحتياطية',
            ],
        ];
    }

    /** @return array<string, list<string>> */
    private function defaultPermissions(): array
    {
        return [
            'manager' => ['members.view', 'members.manage', 'subscriptions.view', 'subscriptions.manage', 'attendance.view', 'attendance.record', 'products.view', 'products.manage', 'inventory.manage', 'sales.view', 'sales.create', 'sales.cancel', 'discounts.formal', 'appointments.view', 'appointments.manage', 'nutrition.view', 'reports.operational'],
            'reception' => ['members.view', 'members.create', 'members.update', 'subscriptions.view', 'subscriptions.create', 'payments.create', 'attendance.view', 'attendance.record', 'products.view', 'sales.create', 'appointments.view', 'appointments.create', 'appointments.update_unpaid', 'nutrition_clients.create'],
            'accountant' => ['payments.view', 'payments.reverse', 'refunds.process', 'expenses.view', 'expenses.manage', 'purchases.view', 'purchases.manage', 'sales.view', 'inventory.view', 'inventory.manage', 'reports.finance', 'audit.financial'],
            'nutritionist' => ['appointments.own', 'appointments.complete_own', 'appointments.cancel_unpaid_own', 'measurements.own'],
        ];
    }
}
