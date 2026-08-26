<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div><h1 class="wg-title">الموظفون</h1><div class="wg-subtitle">إضافة الموظفين، تعديل الحسابات، الفروع، الفترات وكلمات المرور</div></div>
        <button wire:click="openCreate" class="wg-btn wg-btn-primary">＋ إضافة موظف</button>
    </div>

    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif
    @if($errors->has('staff'))<div class="wg-errors">{{ $errors->first('staff') }}</div>@endif

    <div class="wg-grid-stats">
        @php($cards = [
            ['إجمالي الحسابات',$stats['total'],'wg-blue'],['نشط',$stats['active'],'wg-green'],['موقوف',$stats['inactive'],'wg-red'],['الإدارة',$stats['managers'],'wg-purple'],['اختصاصيو التغذية',$stats['nutritionists'],'wg-orange'],['الاستقبال',$stats['reception'],'wg-blue'],
        ])
        @foreach($cards as [$label,$value,$color])<div class="wg-card wg-stat"><small>{{ $label }}</small><strong class="{{ $color }}">{{ $value }}</strong><div class="wg-stat-note">حساب</div></div>@endforeach
    </div>

    <div class="wg-card">
        <div class="wg-toolbar">
            <input wire:model.live.debounce.300ms="search" class="wg-field" style="max-width:300px" placeholder="ابحث بالاسم أو المستخدم أو البريد...">
            <select wire:model.live="roleFilter" class="wg-select" style="max-width:190px"><option value="">كل الأدوار</option><option value="owner">المالك</option><option value="manager">المدير</option><option value="reception">الاستقبال</option><option value="accountant">المحاسب</option><option value="nutritionist">اختصاصي التغذية</option></select>
            <select wire:model.live="statusFilter" class="wg-select" style="max-width:160px"><option value="">كل الحالات</option><option value="active">نشط</option><option value="inactive">موقوف</option></select>
            <select wire:model.live="branchFilter" class="wg-select" style="max-width:190px"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
        </div>
        <div class="wg-table-wrap" style="border:0;border-top:1px solid var(--wg-border);border-radius:0 0 10px 10px">
            <table class="wg-table">
                <thead><tr><th>الموظف</th><th>اسم المستخدم</th><th>الدور</th><th>الفرع</th><th>الفترة</th><th>آخر دخول</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td><div style="font-weight:800;color:#fff">{{ $u->name }}</div><div class="wg-muted" style="font-size:9px;margin-top:3px">{{ $u->email ?: 'بدون بريد' }}</div></td>
                        <td dir="ltr">{{ $u->username }}</td>
                        <td><span class="wg-badge {{ $u->role === 'owner' ? 'wg-badge-blue' : ($u->role === 'nutritionist' ? 'wg-badge-purple' : 'wg-badge-orange') }}">{{ match($u->role){'owner'=>'المالك','manager'=>'المدير','reception'=>'الاستقبال','accountant'=>'المحاسب','nutritionist'=>'اختصاصي التغذية',default=>$u->role} }}</span></td>
                        <td>{{ $u->branch?->name ?: 'كل الفروع' }}</td>
                        <td>
                            @if($u->gymPeriod)<div style="font-weight:700">{{ $u->gymPeriod->name }}</div><div class="wg-muted" style="font-size:9px;margin-top:2px" dir="ltr">{{ $u->gymPeriod->start_time ? substr((string)$u->gymPeriod->start_time,0,5).' - '.substr((string)$u->gymPeriod->end_time,0,5) : 'غير محدد' }}</div>
                            @else{{ $u->work_period === 'both' ? 'مرن / الفترتان' : ($u->work_period === 'women' ? 'نساء' : 'رجال') }}@endif
                        </td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'لم يسجل' }}</td>
                        <td><span class="wg-badge {{ $u->is_active ? 'wg-badge-green' : 'wg-badge-red' }}">{{ $u->is_active ? 'نشط' : 'موقوف' }}</span></td>
                        <td><div style="display:flex;gap:5px;flex-wrap:wrap">
                            <button wire:click="edit({{ $u->id }})" class="wg-btn wg-btn-sm">تعديل</button>
                            @if($u->role !== 'owner')
                                <button wire:click="openPassword({{ $u->id }})" class="wg-btn wg-btn-sm">كلمة مرور</button>
                                <button wire:click="toggle({{ $u->id }})" wire:confirm="تأكيد تغيير حالة الحساب؟" class="wg-btn wg-btn-sm {{ $u->is_active ? 'wg-btn-danger' : 'wg-btn-success' }}">{{ $u->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                            @endif
                        </div></td>
                    </tr>
                @empty<tr><td colspan="8" class="wg-muted">لا توجد حسابات مطابقة.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 14px">{{ $users->links() }}</div>
    </div>

    @if($showEditor)
    <div class="wg-modal-backdrop" wire:click.self="closeEditor">
        <div class="wg-modal wg-modal-lg" dir="rtl">
            <div class="wg-modal-head"><div><h2 class="wg-section-title">{{ $editingId ? 'تعديل الموظف' : 'إضافة موظف جديد' }}</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">حدد الدور والفرع والفترة بدقة.</div></div><button wire:click="closeEditor" class="wg-modal-x">×</button></div>
            <form wire:submit="save">
                <div class="wg-modal-body">
                    @if($errors->any())<div class="wg-errors" style="margin-bottom:14px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                    <div class="wg-two">
                        <div><label class="wg-label">الاسم الكامل *</label><input wire:model="name" class="wg-field"></div>
                        <div><label class="wg-label">اسم المستخدم *</label><input wire:model="username" class="wg-field" dir="ltr"></div>
                        <div><label class="wg-label">البريد الإلكتروني</label><input wire:model="email" class="wg-field" dir="ltr"></div>
                        @if(!$editingId)<div><label class="wg-label">كلمة مرور مؤقتة *</label><input wire:model="password" type="password" class="wg-field" dir="ltr"></div>@endif
                        <div><label class="wg-label">الدور *</label><select wire:model="role" class="wg-select"><option value="manager">مدير</option><option value="reception">استقبال</option><option value="accountant">محاسب</option><option value="nutritionist">اختصاصي تغذية</option></select></div>
                        <div><label class="wg-label">الفرع</label><select wire:model.live="branch_id" class="wg-select"><option value="">كل الفروع / الإدارة العامة</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                        <div><label class="wg-label">الفترة المحددة</label><select wire:model="gym_period_id" class="wg-select"><option value="">بدون فترة محددة</option>@foreach($periods as $period)@if($branch_id === '' || (string)$period->branch_id === $branch_id)<option value="{{ $period->id }}">{{ $period->name }} — {{ $period->branch?->name }}</option>@endif @endforeach</select></div>
                        <div><label class="wg-label">نطاق العمل عند عدم تحديد فترة</label><select wire:model="work_period" class="wg-select"><option value="both">مرن / رجال ونساء</option><option value="men">فترات الرجال</option><option value="women">فترات النساء</option></select></div>
                    </div>
                </div>
                <div class="wg-modal-foot"><button class="wg-btn wg-btn-primary">{{ $editingId ? 'حفظ التعديلات' : 'إنشاء الحساب' }}</button><button type="button" wire:click="closeEditor" class="wg-btn">إلغاء</button></div>
            </form>
        </div>
    </div>
    @endif

    @if($showPassword)
    <div class="wg-modal-backdrop">
        <div class="wg-modal" dir="rtl">
            <div class="wg-modal-head"><h2 class="wg-section-title">إعادة تعيين كلمة المرور</h2><button wire:click="$set('showPassword',false)" class="wg-modal-x">×</button></div>
            <form wire:submit="resetPassword"><div class="wg-modal-body"><label class="wg-label">كلمة المرور المؤقتة الجديدة *</label><input wire:model="new_password" type="password" class="wg-field" dir="ltr"><div class="wg-muted" style="font-size:10px;margin-top:8px">سيطلب النظام من الموظف تغييرها بعد تسجيل الدخول.</div>@error('new_password')<div class="wg-red" style="font-size:10px;margin-top:6px">{{ $message }}</div>@enderror</div><div class="wg-modal-foot"><button class="wg-btn wg-btn-primary">حفظ كلمة المرور</button><button type="button" wire:click="$set('showPassword',false)" class="wg-btn">إلغاء</button></div></form>
        </div>
    </div>
    @endif
</div>
