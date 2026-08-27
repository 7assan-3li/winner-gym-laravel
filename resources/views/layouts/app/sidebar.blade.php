<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark" data-wg-theme="dark">
<head>
    <script>
        (function () {
            try {
                const theme = localStorage.getItem('wg-theme') || 'dark';
                const root = document.documentElement;
                root.dataset.wgTheme = theme;
                root.classList.toggle('dark', theme === 'dark');
                root.classList.toggle('wg-light', theme === 'light');
            } catch (e) {}
        })();
    </script>
    @include('partials.head')
    {{-- Load the accepted module styles on EVERY authenticated request.
         This prevents Livewire/browser-cache differences between owner and newly-created staff accounts. --}}
    <link rel="stylesheet" href="{{ asset('winner-gym/dashboard-final-polish.css') }}">
    <link rel="stylesheet" href="{{ asset('winner-gym/members-final.css') }}?v=20260812-3">
    <link rel="stylesheet" href="{{ asset('winner-gym/subscriptions-final.css') }}?v=20260813-2">
    <link rel="stylesheet" href="{{ asset('winner-gym/attendance-final.css') }}">
    <link rel="stylesheet" href="{{ asset('winner-gym/finance-final.css') }}?v=20260823-1">
    <link rel="stylesheet" href="{{ asset('winner-gym/inventory-final.css') }}?v=20260823-2">
    <link rel="stylesheet" href="{{ asset('winner-gym/nutrition-final.css') }}?v=20260823-2">
    <link rel="stylesheet" href="{{ asset('winner-gym/role-consistency-hotfix.css') }}">
    <link rel="stylesheet" href="{{ asset('winner-gym/system-rtl-final.css') }}?v=20260811-3">
    <link rel="stylesheet" href="{{ asset('winner-gym/theme-system-final.css') }}?v=20260811-3">
    <link rel="stylesheet" href="{{ asset('winner-gym/light-theme-hardening-v3.css') }}?v=20260811-1">
    <link rel="stylesheet" href="{{ asset('winner-gym/unified-design-v1.css') }}?v=20260812-5">
    <link rel="stylesheet" href="{{ asset('winner-gym/quick-actions-final.css') }}?v=20260813-1">
</head>
@php
    $wgBodyClass = match (true) {
        request()->routeIs('gym.dashboard') => 'wg-body-dashboard',
        request()->routeIs('members.index') => 'wg-body-members',
        request()->routeIs('subscriptions.index') => 'wg-body-subscriptions',
        request()->routeIs('attendance.index') => 'wg-body-attendance',
        request()->routeIs('payments.*','expenses.*') => 'wg-body-finance',
        request()->routeIs('inventory.*') => 'wg-body-inventory',
        request()->routeIs('nutrition.*') => 'wg-body-nutrition',
        default => 'wg-body-system',
    };
@endphp
<body class="wg-body {{ $wgBodyClass }}">
<div class="wg-app">
    <aside class="wg-sidebar" dir="rtl">
        <a class="wg-brand" href="{{ route('gym.dashboard') }}" wire:navigate aria-label="WINNER GYM">
            <img class="wg-logo-dark" src="{{ asset('winner-gym/logo.png') }}" alt="WINNER GYM" style="width:112px;height:auto;display:block;margin:0 auto">
            <img class="wg-logo-light" src="{{ asset('winner-gym/logo-light.png') }}" alt="WINNER GYM" style="width:112px;height:auto;display:none;margin:0 auto">
        </a>

        @php
            $u = auth()->user();
            $nav = [
                ['gym.dashboard','لوحة التحكم','dashboard'],
                ['members.index','الأعضاء','members'],
                ['subscriptions.index','الاشتراكات','subscriptions'],
                ['packages.index','الباقات','packages'],
                ['attendance.index','الحضور','attendance'],
                ['payments.index','المدفوعات والمصروفات','finance'],
                ['inventory.products','المخزون','inventory'],
                ['nutrition.appointments','التغذية','nutrition'],
                ['reports.index','التقارير','reports'],
                ['admin.index','الإدارة','admin'],
                ['gym.settings','الإعدادات','settings'],
            ];
        @endphp

        <nav class="wg-nav">
            @foreach($nav as [$routeName,$label,$key])
                @php
                    $canAny = fn (array $abilities) => $u->role === 'owner' || collect($abilities)->contains(fn ($ability) => $u->hasGymPermission($ability));
                    $allowed = match($routeName) {
                        'gym.dashboard' => true,
                        'members.index' => $canAny(['members.view','members.manage']),
                        'subscriptions.index' => $canAny(['subscriptions.view','subscriptions.manage','subscriptions.create']),
                        'packages.index' => $u->role === 'owner',
                        'attendance.index' => $canAny(['attendance.view','attendance.record']),
                        'payments.index' => $canAny(['payments.view','payments.create','payments.reverse','refunds.process','expenses.view','expenses.manage','reports.finance','audit.financial']),
                        'inventory.products' => $canAny(['products.view','products.manage','inventory.view','inventory.manage','purchases.view','purchases.manage','sales.view','sales.create','sales.cancel']),
                        'nutrition.appointments' => $canAny(['appointments.view','appointments.create','appointments.manage','appointments.update_unpaid','appointments.own','appointments.complete_own','appointments.cancel_unpaid_own','nutrition_clients.create','measurements.own','nutrition.view']),
                        'reports.index' => $canAny(['reports.operational','reports.finance']),
                        'admin.index' => $canAny(['staff.view','staff.manage','branches.manage','periods.manage','whatsapp.manage','backups.manage']),
                        'gym.settings' => $u->role === 'owner',
                        default => false,
                    };
                    $current = match($routeName) {
                        'packages.index' => request()->routeIs('packages.*'),
                        'payments.index' => request()->routeIs('payments.*','expenses.*'),
                        'inventory.products' => request()->routeIs('inventory.*'),
                        'nutrition.appointments' => request()->routeIs('nutrition.*'),
                        'admin.index' => request()->routeIs('admin.*','staff.*','audit.*','whatsapp.*','backups.*'),
                        'gym.settings' => request()->routeIs('gym.settings','settings.*','profile.*','appearance.*','security.*'),
                        default => request()->routeIs($routeName),
                    };
                @endphp
                @if($allowed)
                <a href="{{ route($routeName) }}" wire:navigate class="{{ $current ? 'is-active' : '' }}">
                    <svg class="wg-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        @switch($key)
                            @case('dashboard')<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>@break
                            @case('members')<path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M16 11l2 2 4-4"/>@break
                            @case('subscriptions')<path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16M8 14h3"/>@break
                            @case('packages')<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>@break
                            @case('attendance')<path d="M5 4h14v16H5zM8 2v4M16 2v4M8 13l2 2 5-5"/>@break
                            @case('finance')<path d="M3 6h18v12H3zM3 10h18M7 15h3"/>@break
                            @case('inventory')<path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/>@break
                            @case('nutrition')<circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/><path d="M9.5 9.5l5 5M14.5 9.5l-5 5"/>@break
                            @case('reports')<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>@break
                            @case('admin')<path d="M8 20v-2a4 4 0 0 1 4-4h2a4 4 0 0 1 4 4v2M13 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M4 15v5M2 17h4"/>@break
                            @default <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1-2.8 2.8-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.6V21H10v-.1A1.8 1.8 0 0 0 8.9 19a1.8 1.8 0 0 0-2 .4l-.1.1L4 16.7l.1-.1a1.8 1.8 0 0 0 .4-2A1.8 1.8 0 0 0 3 13.5H3V10h.1a1.8 1.8 0 0 0 1.6-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1L7 4l.1.1a1.8 1.8 0 0 0 2 .4A1.8 1.8 0 0 0 10.2 3H14v.1a1.8 1.8 0 0 0 1.1 1.6 1.8 1.8 0 0 0 2-.4l.1-.1L20 7l-.1.1a1.8 1.8 0 0 0-.4 2A1.8 1.8 0 0 0 21 10.2V14h-.1A1.8 1.8 0 0 0 19.4 15z"/>
                        @endswitch
                    </svg>
                    <span>{{ $label }}</span>
                </a>
                @endif
            @endforeach
        </nav>

    </aside>
    <button class="wg-sidebar-scrim" type="button" aria-label="إغلاق القائمة" onclick="window.wgToggleNavigation && window.wgToggleNavigation(false)"></button>

    <main class="wg-main">
        @php
            [$wgPageTitle, $wgPageCrumb, $wgSearchPlaceholder] = match (true) {
                request()->routeIs('gym.dashboard') => ['لوحة التحكم', 'لوحة التحكم', 'ابحث عن عضو، اشتراك، دفعة...'],
                request()->routeIs('members.*') => ['الأعضاء', 'الأعضاء', 'ابحث عن اسم العضو أو رقم الهاتف...'],
                request()->routeIs('packages.*') => ['الباقات', 'الباقات', 'ابحث عن باقة أو مدة أو سعر...'],
                request()->routeIs('subscriptions.*') => ['الاشتراكات', 'الاشتراكات', 'ابحث عن عضو، باقة أو كود اشتراك...'],
                request()->routeIs('attendance.*') => ['الحضور', 'الحضور', 'ابحث عن عضو، كود عضوية أو رقم الهاتف...'],
                request()->routeIs('payments.*','expenses.*') => ['المالية', request()->routeIs('expenses.*') ? 'المصروفات' : 'المدفوعات', 'ابحث عن عضو، إيصال، مصروف أو دفعة...'],
                request()->routeIs('inventory.*') => ['المخزون', 'المخزون', 'ابحث عن منتج، باركود أو عملية مخزون...'],
                request()->routeIs('nutrition.*') => ['التغذية', request()->routeIs('nutrition.measurements') ? 'القياسات' : 'المواعيد', 'ابحث عن عضو، عميل، موعد أو قياس...'],
                request()->routeIs('reports.*') => ['التقارير', 'التقارير', 'ابحث داخل التقارير...'],
                request()->routeIs('admin.*','staff.*','audit.*','whatsapp.*','backups.*') => ['الإدارة', 'الإدارة', 'ابحث عن موظف أو إجراء إداري...'],
                request()->routeIs('gym.settings','settings.*','profile.*','appearance.*','security.*') => ['الإعدادات', 'الإعدادات', 'ابحث في الإعدادات...'],
                default => ['WINNER GYM', 'الرئيسية', 'ابحث في النظام...'],
            };
        @endphp

        <header class="wg-topbar wg-topbar-unified" dir="ltr">
            <div class="wg-unified-page" dir="rtl">
                <h1>{{ $wgPageTitle }}</h1>
                <div class="wg-unified-crumb"><span>الرئيسية</span><i>•</i><span>{{ $wgPageCrumb }}</span></div>
            </div>

            <button class="wg-search wg-unified-search" type="button" dir="rtl" aria-label="البحث" onclick="window.wgHandleTopSearch && window.wgHandleTopSearch()">
                <span>{{ $wgSearchPlaceholder }}</span>
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </button>

            <div class="wg-topbar-tools wg-unified-tools" dir="ltr">
                <button class="wg-top-icon wg-mobile-menu-toggle" type="button" title="فتح القائمة" aria-label="فتح القائمة" aria-expanded="false" onclick="window.wgToggleNavigation && window.wgToggleNavigation()">
                    <svg width="21" height="21" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>

                <details class="wg-user wg-unified-user">
                    <summary dir="rtl">
                        <div class="wg-avatar">{{ mb_substr($u->name ?: $u->username, 0, 1) }}</div>
                        <div class="wg-user-copy">
                            <div class="wg-user-name">{{ $u->name ?: $u->username }}</div>
                            <div class="wg-user-role">{{ match($u->role){'owner'=>'مدير النظام','manager'=>'المدير','reception'=>'الاستقبال','accountant'=>'المحاسب','nutritionist'=>'اختصاصي التغذية',default=>$u->role} }}</div>
                        </div>
                        <span class="wg-user-chevron">⌄</span>
                    </summary>
                    <div class="wg-user-menu wg-user-menu-reference" dir="rtl">
                        <div class="wg-user-menu-head">
                            <div class="wg-user-menu-avatar">{{ mb_substr($u->name ?: $u->username, 0, 1) }}</div>
                            <div><strong>{{ $u->name ?: $u->username }}</strong><span>{{ match($u->role){'owner'=>'المالك','manager'=>'المدير','reception'=>'الاستقبال','accountant'=>'المحاسب','nutritionist'=>'اختصاصي التغذية',default=>$u->role} }}</span></div>
                            <code>{{ $u->username }}</code>
                        </div>
                        <div class="wg-user-menu-row"><span>فترة العمل</span><strong>{{ $u->gymPeriod?->name ?? ($u->work_period === 'both' ? 'مرن / كل الفترات' : ($u->work_period === 'men' ? 'فترات الرجال' : 'فترات النساء')) }}</strong></div>
                        <div class="wg-user-menu-row"><span>آخر دخول</span><strong>{{ $u->last_login_at ? $u->last_login_at->timezone('Asia/Aden')->format('h:i A') : 'اليوم' }}</strong></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><span>↪</span> تسجيل الخروج</button></form>
                    </div>
                </details>

                <span class="wg-top-separator"></span>

                <button class="wg-top-icon wg-theme-toggle" type="button" title="تغيير المظهر" aria-label="تغيير المظهر" onclick="window.wgToggleTheme && window.wgToggleTheme()">
                    <svg class="wg-theme-sun" width="21" height="21" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="wg-theme-moon" width="21" height="21" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8z"/></svg>
                </button>

                @if($u->role === 'owner' || $u->hasGymPermission('sales.view') || $u->hasGymPermission('sales.create'))
                    <a href="{{ route('inventory.sales') }}" wire:navigate class="wg-top-icon wg-quick-cart" title="نقطة البيع" aria-label="نقطة البيع">
                        <svg width="21" height="21" viewBox="0 0 24 24"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L20.5 7H6M10 20h.01M17 20h.01"/></svg>
                    </a>
                @endif

                @if($u->role === 'owner' || $u->hasGymPermission('attendance.record'))
                    <button class="wg-top-icon wg-quick-attendance-trigger" type="button" title="تسجيل الحضور السريع" aria-label="تسجيل الحضور السريع" onclick="window.dispatchEvent(new CustomEvent('wg-open-quick-attendance'))">
                        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8zM14 14h2v2h-2z"/></svg>
                    </button>
                @endif

                @if($u->role === 'owner' || $u->hasGymPermission('members.create') || $u->hasGymPermission('members.manage'))
                    <a href="{{ route('members.index', ['create' => 1]) }}" wire:navigate class="wg-quick-member" title="إضافة عضو جديد">
                        <span>عضو جديد</span>
                        <svg viewBox="0 0 24 24"><path d="M15 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M8.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M16 11h6"/></svg>
                    </a>
                @endif
            </div>
        </header>

        @php
            $wgContentClass = match (true) {
                request()->routeIs('gym.dashboard') => 'wg-dashboard-content',
                request()->routeIs('members.index') => 'wg-members-content',
                request()->routeIs('subscriptions.index') => 'wg-subscriptions-content',
                request()->routeIs('attendance.index') => 'wg-attendance-content',
                request()->routeIs('payments.*','expenses.*') => 'wg-finance-content',
                request()->routeIs('inventory.*') => 'wg-inventory-content',
                request()->routeIs('nutrition.*') => 'wg-nutrition-content',
                default => 'wg-system-content',
            };
        @endphp
        <div class="wg-content {{ $wgContentClass }}">
            {{ $slot }}
        </div>
    </main>
</div>

@if($u->role === 'owner' || $u->hasGymPermission('attendance.record'))
    <livewire:attendance.quick-record />
@endif

@persist('toast')
    <flux:toast.group><flux:toast /></flux:toast.group>
@endpersist
<script>
    (function () {
        const root = document.documentElement;
        window.wgApplyTheme = function (theme) {
            const next = theme === 'light' ? 'light' : 'dark';
            root.dataset.wgTheme = next;
            root.classList.toggle('dark', next === 'dark');
            root.classList.toggle('wg-light', next === 'light');
            root.style.colorScheme = next;
            if (document.body) document.body.dataset.wgTheme = next;
            let meta = document.querySelector('meta[name="theme-color"]');
            if (!meta) { meta = document.createElement('meta'); meta.name = 'theme-color'; document.head.appendChild(meta); }
            meta.content = next === 'light' ? '#ffffff' : '#030914';
            try { localStorage.setItem('wg-theme', next); } catch (e) {}
        };
        window.wgToggleTheme = function () {
            const current = root.dataset.wgTheme || (root.classList.contains('wg-light') ? 'light' : 'dark');
            window.wgApplyTheme(current === 'light' ? 'dark' : 'light');
        };
        window.wgToggleNavigation = function (force) {
            const open = typeof force === 'boolean' ? force : !document.body.classList.contains('wg-nav-open');
            document.body.classList.toggle('wg-nav-open', open);
            const toggle = document.querySelector('.wg-mobile-menu-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        document.addEventListener('livewire:navigated', function () {
            window.wgToggleNavigation(false);
        });
        window.wgOpenNotifications = function () {
            if (window.location.pathname === '/gym-dashboard') {
                window.dispatchEvent(new CustomEvent('wg-open-notifications'));
                return;
            }
            try { sessionStorage.setItem('wg-open-notifications-after-nav', '1'); } catch (e) {}
            window.location.href = @json(route('gym.dashboard'));
        };
        window.wgHandleTopSearch = function () {
            const path = window.location.pathname;
            const ids = path.startsWith('/members') ? ['member-search']
                : path.startsWith('/subscriptions') ? ['subscription-search']
                : path.startsWith('/attendance') ? ['attendance-identifier']
                : (path.startsWith('/payments') || path.startsWith('/expenses')) ? ['finance-search']
                : path.startsWith('/inventory') ? ['inventory-search','product-search']
                : path.startsWith('/nutrition') ? ['nutrition-search']
                : path.startsWith('/reports') ? ['reports-search','report-search']
                : [];
            for (const id of ids) {
                const el = document.getElementById(id);
                if (el) { el.focus(); el.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            }
            const candidate = document.querySelector('.wg-content input[type="search"], .wg-content input[placeholder*="ابحث"], .wg-content input[placeholder*="بحث"]');
            if (candidate) { candidate.focus(); candidate.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            window.dispatchEvent(new CustomEvent('wg-open-global-search'));
        };
        const sync = () => {
            let theme = 'dark';
            try { theme = localStorage.getItem('wg-theme') || 'dark'; } catch (e) {}
            window.wgApplyTheme(theme);
        };
        const openPendingNotifications = () => {
            if (window.location.pathname !== '/gym-dashboard') return;
            let pending = false;
            try { pending = sessionStorage.getItem('wg-open-notifications-after-nav') === '1'; } catch (e) {}
            if (!pending) return;
            try { sessionStorage.removeItem('wg-open-notifications-after-nav'); } catch (e) {}
            setTimeout(() => window.dispatchEvent(new CustomEvent('wg-open-notifications')), 120);
        };
        document.addEventListener('livewire:navigated', () => { sync(); openPendingNotifications(); });
        document.addEventListener('DOMContentLoaded', () => { sync(); openPendingNotifications(); }, { once: true });
    })();
</script>

<script>
(function(){
  const root=document.documentElement;
  const skipSelector='.wg-btn-primary,.fin-btn.primary,.wg-inv-btn-primary,.wg-inv-modal-save,.wg-inv-open-pos,.wg-nut-btn-primary,.is-active,[class*="badge"],[class*="pill"],[class*="status"],[class*="avatar"],[class*="donut"],[class*="progress"]';
  const rgb=s=>{const m=s&&s.match(/rgba?\((\d+)[, ]+\s*(\d+)[, ]+\s*(\d+)(?:[,/ ]+\s*([\d.]+))?/);return m?[+m[1],+m[2],+m[3],m[4]===undefined?1:+m[4]]:null};
  const lum=c=>c?(.2126*c[0]+.7152*c[1]+.0722*c[2]):255;
  function darkGradient(s){if(!s||s==='none')return false;const cs=[...s.matchAll(/rgba?\((\d+)[, ]+\s*(\d+)[, ]+\s*(\d+)/g)].map(m=>[+m[1],+m[2],+m[3],1]);return cs.length>1&&cs.every(c=>lum(c)<72)}
  window.wgHardenLightMode=function(){
    const light=root.classList.contains('wg-light')||root.dataset.wgTheme==='light';
    document.querySelectorAll('.wg-light-auto-surface').forEach(el=>el.classList.remove('wg-light-auto-surface'));
    if(!light)return;
    document.querySelectorAll('.wg-main *, .wg-sidebar *').forEach(el=>{
      if(el.matches('img,svg,path,circle,line,polyline,polygon,use,canvas,video')||el.matches(skipSelector)||el.closest('.wg-nav a.is-active'))return;
      const cs=getComputedStyle(el), c=rgb(cs.backgroundColor), rect=el.getBoundingClientRect();
      const surface=(c&&c[3]>.25&&lum(c)<68)||darkGradient(cs.backgroundImage);
      if(!surface||rect.width<24||rect.height<18)return;
      const interactive=/^(A|BUTTON|INPUT|SELECT|TEXTAREA|TD|TH)$/.test(el.tagName);
      const rounded=parseFloat(cs.borderRadius||'0')>0;
      if(interactive||rounded)el.classList.add('wg-light-auto-surface');
    });
  };
  const base=window.wgApplyTheme;
  if(base)window.wgApplyTheme=function(t){base(t);requestAnimationFrame(()=>window.wgHardenLightMode())};
  let timer; const schedule=()=>{clearTimeout(timer);timer=setTimeout(()=>window.wgHardenLightMode(),35)};
  document.addEventListener('DOMContentLoaded',schedule,{once:true});
  document.addEventListener('livewire:navigated',schedule);
  new MutationObserver(schedule).observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
@livewireScripts
@fluxScripts
</body>
</html>
