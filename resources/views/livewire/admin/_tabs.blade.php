@php
$u = auth()->user();
$canAny = fn (array $abilities) => $u->role === 'owner' || collect($abilities)->contains(fn ($ability) => $u->hasGymPermission($ability));
$adminTabs = [
    ['admin.index','نظرة عامة', $canAny(['staff.view','staff.manage','branches.manage','periods.manage','whatsapp.manage','backups.manage','audit.financial'])],
    ['staff.index','الموظفون', $canAny(['staff.view','staff.manage'])],
    ['admin.permissions','الصلاحيات', $u->role === 'owner'],
    ['admin.branches','الفروع', $canAny(['branches.manage'])],
    ['admin.periods','الفترات', $canAny(['periods.manage'])],
    ['whatsapp.index','واتساب', $canAny(['whatsapp.manage'])],
    ['audit.index','سجل التدقيق', $canAny(['audit.financial'])],
    ['backups.index','النسخ الاحتياطي', $canAny(['backups.manage'])],
    ['gym.settings','إعدادات النظام', $u->role === 'owner'],
];
@endphp
<div class="wg-card" style="padding:6px;overflow:auto">
    <div style="display:flex;gap:4px;min-width:max-content">
        @foreach($adminTabs as [$routeName,$label,$allowed])
            @continue(! $allowed)
            @php
                $active = match($routeName) {
                    'staff.index' => request()->routeIs('staff.*'),
                    'whatsapp.index' => request()->routeIs('whatsapp.*'),
                    'audit.index' => request()->routeIs('audit.*'),
                    'backups.index' => request()->routeIs('backups.*'),
                    'gym.settings' => request()->routeIs('gym.settings'),
                    default => request()->routeIs($routeName),
                };
            @endphp
            <a href="{{ route($routeName) }}" wire:navigate class="wg-btn wg-btn-sm {{ $active ? 'wg-btn-primary' : '' }}" style="border-color:{{ $active ? '#2488ff' : 'transparent' }};background:{{ $active ? '' : 'transparent' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
