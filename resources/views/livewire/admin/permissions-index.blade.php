<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div><h1 class="wg-title">الصلاحيات</h1><div class="wg-subtitle">صلاحيات حسب الدور + استثناءات خاصة لموظف محدد</div></div>
        <button wire:click="resetDefaults" wire:confirm="استعادة الصلاحيات الافتراضية لكل الأدوار؟" class="wg-btn">استعادة الافتراضي</button>
    </div>
    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif

    <div style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px">
        <div class="wg-card wg-card-pad"><div style="display:flex;justify-content:space-between"><strong>المالك</strong><span class="wg-badge wg-badge-blue">كامل</span></div><div class="wg-muted" style="font-size:10px;margin-top:8px">كل الصلاحيات دائمًا</div></div>
        @foreach($roles as $roleKey=>$roleLabel)
            @php($count = isset($permissions[$roleKey]) ? $permissions[$roleKey]->where('allowed',true)->count() : 0)
            <button wire:click="$set('selectedRole','{{ $roleKey }}')" class="wg-card wg-card-pad" style="text-align:right;cursor:pointer;border-color:{{ $selectedRole===$roleKey ? '#2488ff' : 'var(--wg-border)' }}">
                <div style="display:flex;justify-content:space-between"><strong>{{ $roleLabel }}</strong><span class="wg-badge {{ $selectedRole===$roleKey ? 'wg-badge-blue' : 'wg-badge-green' }}">{{ $count }}</span></div><div class="wg-muted" style="font-size:10px;margin-top:8px">صلاحية مفعلة</div>
            </button>
        @endforeach
    </div>

    <div class="wg-card wg-card-pad">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px"><div><h2 class="wg-section-title">مصفوفة {{ $roles[$selectedRole] ?? '' }}</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">انقر على الحالة لتفعيل أو إلغاء الصلاحية.</div></div><input wire:model.live.debounce.250ms="search" class="wg-field" style="max-width:260px" placeholder="ابحث عن صلاحية..."></div>
        <div style="display:grid;gap:14px">
            @foreach($abilities as $group=>$items)
                <div>
                    <div style="font-weight:800;color:#fff;font-size:11px;margin-bottom:8px">{{ $group }}</div>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px">
                        @foreach($items as $ability=>$label)
                            @php($allowed = (bool) (($permissions->get($selectedRole, collect())->get($ability)?->allowed) ?? false))
                            <button wire:click="toggle('{{ $selectedRole }}','{{ $ability }}')" class="wg-finance-box" style="cursor:pointer;text-align:right;display:flex;align-items:center;justify-content:space-between;gap:10px">
                                <div><div style="font-size:10px;font-weight:700;color:#dce5f1">{{ $label }}</div><div class="wg-muted" dir="ltr" style="font-size:8px;margin-top:3px;text-align:right">{{ $ability }}</div></div>
                                <span class="wg-badge {{ $allowed ? 'wg-badge-green' : 'wg-badge-red' }}">{{ $allowed ? 'مسموح' : 'غير مسموح' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="wg-two">
        <div class="wg-card wg-card-pad">
            <h2 class="wg-section-title">استثناء خاص لموظف</h2><div class="wg-muted" style="font-size:10px;margin:5px 0 12px">يمكن السماح أو المنع لموظف واحد بدون تغيير صلاحيات بقية دوره.</div>
            <div style="display:grid;gap:9px">
                <select wire:model="overrideUserId" class="wg-select"><option value="">اختر الموظف</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->username }}</option>@endforeach</select>
                <select wire:model="overrideAbility" class="wg-select"><option value="">اختر الصلاحية</option>@foreach($abilities as $group=>$items)<optgroup label="{{ $group }}">@foreach($items as $ability=>$label)<option value="{{ $ability }}">{{ $label }}</option>@endforeach</optgroup>@endforeach</select>
                <select wire:model="overrideValue" class="wg-select"><option value="inherit">حسب الدور (بدون استثناء)</option><option value="allow">سماح خاص</option><option value="deny">منع خاص</option></select>
                <button wire:click="saveOverride" class="wg-btn wg-btn-primary">حفظ الاستثناء</button>
            </div>
        </div>
        <div class="wg-card">
            <div class="wg-card-pad"><h2 class="wg-section-title">آخر الاستثناءات</h2></div>
            @forelse($overrides as $ov)
                <div class="wg-alert"><div><div style="font-weight:700;color:#fff">{{ $ov->user?->name }}</div><div class="wg-muted" dir="ltr" style="font-size:9px;margin-top:3px">{{ $ov->ability }}</div></div><div style="display:flex;gap:6px;align-items:center"><span class="wg-badge {{ $ov->allowed ? 'wg-badge-green' : 'wg-badge-red' }}">{{ $ov->allowed ? 'سماح' : 'منع' }}</span><button wire:click="removeOverride({{ $ov->id }})" class="wg-btn wg-btn-sm">إزالة</button></div></div>
            @empty<div class="wg-card-pad wg-muted" style="font-size:10px">لا توجد استثناءات خاصة.</div>@endforelse
        </div>
    </div>
</div>
