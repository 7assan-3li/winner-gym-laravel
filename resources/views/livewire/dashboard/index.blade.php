<div class="wg-dashboard-ref" dir="rtl" x-data="{ subStep: 1 }" x-on:wg-open-global-search.window="$wire.$set('modal', 'search', false)" x-on:wg-open-notifications.window="$wire.$set('modal', 'notifications', false)">
    @php
        $activePercent = $stats['total_members'] > 0
            ? round(($stats['active_members'] / $stats['total_members']) * 100, 1)
            : 0;
        $attendanceDelta = $stats['attendance_yesterday'] > 0
            ? round((($stats['attendance_today'] - $stats['attendance_yesterday']) / $stats['attendance_yesterday']) * 100, 1)
            : ($stats['attendance_today'] > 0 ? 100 : 0);

        $cards = [
            ['label'=>'الأعضاء النشطون','value'=>$stats['active_members'],'tone'=>'blue','note'=>number_format($activePercent,1).'% من إجمالي الأعضاء','route'=>'members.index','icon'=>'members'],
            ['label'=>'حضور اليوم','value'=>$stats['attendance_today'],'tone'=>'purple','note'=>($attendanceDelta >= 0 ? '+' : '').number_format($attendanceDelta,1).'% من أمس','route'=>'attendance.index','icon'=>'attendance'],
            ['label'=>'اشتراكات متأخرة','value'=>$stats['overdue_subscriptions'],'tone'=>'red','note'=>'تحتاج متابعة','route'=>'subscriptions.index','icon'=>'overdue'],
            ['label'=>'تنتهي قريبًا','value'=>$stats['expiring_soon'],'tone'=>'orange','note'=>'خلال 30 يوم','route'=>'subscriptions.index','icon'=>'expiring'],
            ['label'=>'مواعيد التغذية اليوم','value'=>$stats['appointments_today'],'tone'=>'green','note'=>'مواعيد','route'=>'nutrition.appointments','icon'=>'appointment'],
            ['label'=>'تنبيهات المخزون','value'=>$stats['stock_alerts'],'tone'=>'orange','note'=>'منتجات قليلة','route'=>'inventory.products','icon'=>'stock'],
        ];

        $dashboardCardUser = auth()->user();
        $canOpenDashboardCard = static function (string $route) use ($dashboardCardUser): bool {
            if ($dashboardCardUser->role === 'owner') return true;

            return match ($route) {
                'members.index' => $dashboardCardUser->hasGymPermission('members.view') || $dashboardCardUser->hasGymPermission('members.manage'),
                'attendance.index' => $dashboardCardUser->hasGymPermission('attendance.view') || $dashboardCardUser->hasGymPermission('attendance.record'),
                'subscriptions.index' => $dashboardCardUser->hasGymPermission('subscriptions.view') || $dashboardCardUser->hasGymPermission('subscriptions.create') || $dashboardCardUser->hasGymPermission('subscriptions.manage'),
                'nutrition.appointments' => $dashboardCardUser->hasGymPermission('appointments.view') || $dashboardCardUser->hasGymPermission('appointments.create') || $dashboardCardUser->hasGymPermission('appointments.manage') || $dashboardCardUser->hasGymPermission('nutrition.view'),
                'inventory.products' => $dashboardCardUser->hasGymPermission('products.view') || $dashboardCardUser->hasGymPermission('products.manage') || $dashboardCardUser->hasGymPermission('inventory.view') || $dashboardCardUser->hasGymPermission('inventory.manage'),
                default => false,
            };
        };
        $cards = array_values(array_filter($cards, fn (array $card) => $canOpenDashboardCard($card['route'])));

        $statusLabel = fn ($status) => match ($status) {
            'active' => 'نشط',
            'financial_overdue' => 'متأخر ماليًا',
            'expiring_soon' => 'ينتهي قريبًا',
            'upcoming' => 'قادم',
            'expired' => 'منتهي',
            'cancelled' => 'ملغي',
            'refunded' => 'مسترد',
            default => $status,
        };

        $chartValues = $revenueSeries->pluck('value')->all();
        $chartMax = max(1, max($chartValues ?: [0]));
        $chartW = 640;
        $chartH = 170;
        $chartTop = 12;
        $chartBottom = 12;
        $usableH = $chartH - $chartTop - $chartBottom;
        $stepX = count($chartValues) > 1 ? $chartW / (count($chartValues) - 1) : $chartW;
        $chartPoints = collect($chartValues)->map(function ($value, $index) use ($chartMax, $chartTop, $usableH, $stepX) {
            $x = round($index * $stepX, 2);
            $y = round($chartTop + ($usableH - (($value / $chartMax) * $usableH)), 2);
            return [$x, $y];
        });
        $polyline = $chartPoints->map(fn ($point) => implode(',', $point))->implode(' ');
        $areaPoints = '0,'.$chartH.' '.$polyline.' '.$chartW.','.$chartH;
        $focusIndex = collect($chartValues)->filter(fn ($value) => $value > 0)->keys()->last()
            ?? max(0, count($chartValues) - 1);
        $focusPoint = $chartPoints[$focusIndex] ?? [0, $chartH];
        $focusValue = $chartValues[$focusIndex] ?? 0;
        $chartStepDivisor = max(1, count($chartValues) - 1);
    @endphp

    <div class="wg-dash-stats" dir="ltr">
        @foreach($cards as $card)
            <a href="{{ route($card['route']) }}" wire:navigate class="wg-dash-stat wg-dash-stat--{{ $card['tone'] }}" dir="rtl">
                <div class="wg-dash-stat__icon" aria-hidden="true">
                    @switch($card['icon'])
                        @case('members')
                            <svg viewBox="0 0 24 24"><path d="M15.5 20v-1.5A4.5 4.5 0 0 0 11 14H6.5A4.5 4.5 0 0 0 2 18.5V20M8.75 10.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 8a3 3 0 1 1-1.4 5.65M18 14.5a4 4 0 0 1 4 4V20"/></svg>
                            @break
                        @case('attendance')
                            <svg viewBox="0 0 24 24"><path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M2.5 21v-2.1A5.9 5.9 0 0 1 8.4 13h1.2M18.5 9.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z"/><path d="M15 7.7v2l1.35.8"/></svg>
                            @break
                        @case('overdue')
                            <svg viewBox="0 0 24 24"><path d="M4 5.5h16V20H4zM8 2.5v5M16 2.5v5M4 10h16M9 14l6 6M15 14l-6 6"/></svg>
                            @break
                        @case('expiring')
                            <svg viewBox="0 0 24 24"><path d="M4 5.5h16V20H4zM8 2.5v5M16 2.5v5M4 10h16"/><circle cx="15.6" cy="15.5" r="3"/><path d="M15.6 13.8v1.9l1.2.75"/></svg>
                            @break
                        @case('appointment')
                            <svg viewBox="0 0 24 24"><path d="M4 5.5h16V20H4zM8 2.5v5M16 2.5v5M4 10h16M8.8 15l2 2 4.4-4.5"/></svg>
                            @break
                        @default
                            <svg viewBox="0 0 24 24"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/></svg>
                    @endswitch
                </div>
                <div class="wg-dash-stat__copy">
                    <span class="wg-dash-stat__label">{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['value']) }}</strong>
                    <small>{{ $card['note'] }}</small>
                </div>
            </a>
        @endforeach
    </div>

    <div class="wg-dash-main-grid" dir="ltr">
        <section class="wg-dash-panel wg-dash-revenue" dir="rtl">
            <div class="wg-dash-panel__head">
                <h2>نظرة عامة على الإيرادات</h2>
                <label class="wg-dash-period-control">
                    <span class="wg-visually-hidden">فترة عرض الإيرادات</span>
                    <select class="wg-dash-select" wire:model.live="dashboardPeriod" wire:loading.attr="disabled" wire:target="dashboardPeriod" aria-label="فترة عرض الإيرادات">
                        <option value="day">اليوم</option>
                        <option value="week">هذا الأسبوع</option>
                        <option value="month">هذا الشهر</option>
                    </select>
                    <i wire:loading wire:target="dashboardPeriod" aria-label="جارٍ تحديث الرسم"></i>
                </label>
            </div>
            <div class="wg-chart-shell" dir="ltr">
                <div class="wg-chart-ylabels" aria-hidden="true">
                    <span>{{ number_format($chartMax) }}</span>
                    <span>{{ number_format($chartMax * .75) }}</span>
                    <span>{{ number_format($chartMax * .5) }}</span>
                    <span>{{ number_format($chartMax * .25) }}</span>
                    <span>0</span>
                </div>
                <div class="wg-chart-stage">
                    <svg class="wg-chart-svg" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" aria-label="إيرادات {{ $dashboardPeriodLabel }}">
                        <defs>
                            <linearGradient id="wgRevenueAreaFinal" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#087cff" stop-opacity=".34"/>
                                <stop offset="100%" stop-color="#087cff" stop-opacity=".018"/>
                            </linearGradient>
                        </defs>
                        <g class="wg-chart-gridlines">
                            <line x1="0" y1="12" x2="640" y2="12"/>
                            <line x1="0" y1="48" x2="640" y2="48"/>
                            <line x1="0" y1="85" x2="640" y2="85"/>
                            <line x1="0" y1="121" x2="640" y2="121"/>
                            <line x1="0" y1="158" x2="640" y2="158"/>
                        </g>
                        <polygon points="{{ $areaPoints }}" fill="url(#wgRevenueAreaFinal)"/>
                        <polyline points="{{ $polyline }}" class="wg-chart-line"/>
                        <line x1="{{ $focusPoint[0] }}" y1="{{ $focusPoint[1] }}" x2="{{ $focusPoint[0] }}" y2="170" class="wg-chart-focus-line"/>
                        <circle cx="{{ $focusPoint[0] }}" cy="{{ $focusPoint[1] }}" r="5.5" class="wg-chart-focus-dot"/>
                    </svg>
                    <div class="wg-chart-tooltip" style="left:calc({{ $focusIndex }} * (100% / {{ $chartStepDivisor }}));top:30px">
                        <span>{{ $revenueSeries[$focusIndex]['label'] ?? '' }}</span>
                        <strong>{{ number_format($focusValue, 0) }} YER</strong>
                    </div>
                    <div class="wg-chart-xlabels" dir="ltr">
                        @foreach($revenueSeries as $point)
                            <span dir="rtl">{{ $point['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="wg-dash-panel wg-dash-finance" dir="rtl">
            <div class="wg-dash-panel__head">
                <h2>الملخص المالي</h2>
                <label class="wg-dash-period-control">
                    <span class="wg-visually-hidden">فترة عرض الملخص المالي</span>
                    <select class="wg-dash-select" wire:model.live="dashboardPeriod" wire:loading.attr="disabled" wire:target="dashboardPeriod" aria-label="فترة عرض الملخص المالي">
                        <option value="day">اليوم</option>
                        <option value="week">هذا الأسبوع</option>
                        <option value="month">هذا الشهر</option>
                    </select>
                    <i wire:loading wire:target="dashboardPeriod" aria-label="جارٍ تحديث الملخص"></i>
                </label>
            </div>
            <div class="wg-dash-finance-cards" dir="ltr">
                @foreach(['YER','SAR'] as $currency)
                    @php
                        $f = $finance[$currency];
                        $revenue = $f['subscription_revenue'] + $f['nutrition_revenue'] + $f['product_revenue'];
                    @endphp
                    <div class="wg-dash-money-card" dir="rtl">
                        <div class="wg-dash-money-title" dir="ltr">
                            <span class="wg-money-dot"></span><strong>{{ $currency }}</strong>
                            <span class="wg-money-symbol">{{ $currency === 'YER' ? '﷼' : 'ر.س' }}</span>
                        </div>
                        <div class="wg-dash-money-cols">
                            <div><span>الإيرادات</span><strong class="is-green">{{ number_format($revenue, 0) }}</strong></div>
                            <div><span>المصروفات</span><strong class="is-red">{{ number_format($f['expenses'], 0) }}</strong></div>
                        </div>
                        <div class="wg-dash-money-net">
                            <span>الصافي</span>
                            <strong>{{ number_format($f['net'], 0) }}</strong>
                            <svg viewBox="0 0 150 34" preserveAspectRatio="none" aria-hidden="true"><polyline points="0,26 12,25 22,27 34,20 45,23 57,16 70,22 82,15 94,20 106,11 118,18 130,10 140,15 150,4"/></svg>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @php
            $dashboardUser = auth()->user();
            $canQuickMember = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('members.create') || $dashboardUser->hasGymPermission('members.manage');
            $canQuickSubscription = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('subscriptions.create') || $dashboardUser->hasGymPermission('subscriptions.manage');
            $canQuickAttendance = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('attendance.record');
            $canQuickPayment = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('payments.create');
            $canQuickExpense = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('expenses.manage');
            $canQuickProduct = $dashboardUser->role === 'owner' || $dashboardUser->hasGymPermission('products.manage');
        @endphp

        <aside class="wg-dash-panel wg-dash-quick" dir="rtl">
            <div class="wg-dash-panel__head">
                <h2>إجراءات سريعة</h2>
                <svg class="wg-panel-head-icon" viewBox="0 0 24 24"><path d="M12 3v18M3 12h18"/></svg>
            </div>
            <div class="wg-dash-quick-list">
                @if($canQuickMember)<button type="button" x-on:click="$wire.$set('modal', 'member', false)"><span class="quick-purple"><svg viewBox="0 0 24 24"><path d="M15 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M8.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8v6M15 11h6"/></svg></span><b>إضافة عضو جديد</b></button>@endif
                @if($canQuickSubscription)<button type="button" x-on:click="subStep=1; $wire.$set('modal', 'subscription', false)"><span class="quick-blue"><svg viewBox="0 0 24 24"><path d="M4 5h16v15H4zM8 2.5v5M16 2.5v5M4 10h16"/></svg></span><b>اشتراك جديد</b></button>@endif
                @if($canQuickAttendance)<button type="button" x-on:click="$wire.$set('modal', 'attendance', false)"><span class="quick-blue"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><b>تسجيل حضور</b></button>@endif
                @if($canQuickPayment)<button type="button" x-on:click="$wire.$set('modal', 'payment', false)"><span class="quick-green"><svg viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 10h18M15 15h3"/></svg></span><b>استلام دفعة</b></button>@endif
                @if($canQuickExpense)<button type="button" x-on:click="$wire.$set('modal', 'expense', false)"><span class="quick-orange"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg></span><b>إضافة مصروف</b></button>@endif
                @if($canQuickProduct)<button type="button" x-on:click="$wire.$set('modal', 'product', false)"><span class="quick-orange"><svg viewBox="0 0 24 24"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/></svg></span><b>إضافة منتج</b></button>@endif
                <button type="button" x-on:click="$wire.$set('modal', 'search', false)"><span class="quick-blue"><svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg></span><b>البحث العام</b></button>
                <button type="button" x-on:click="$wire.$set('modal', 'notifications', false)"><span class="quick-purple"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><b>الإشعارات</b></button>
            </div>
        </aside>

        <section class="wg-dash-panel wg-dash-recent" dir="rtl">
            <div class="wg-dash-panel__head"><h2>الاشتراكات الأخيرة</h2><a href="{{ route('subscriptions.index') }}" wire:navigate>عرض الكل</a></div>
            <div class="wg-dash-list">
                @forelse($recentSubscriptions as $s)
                    <a href="{{ route('subscriptions.index') }}" wire:navigate class="wg-dash-list-row">
                        <div class="wg-list-avatar">{{ mb_substr($s->member?->full_name ?? 'ع', 0, 1) }}</div>
                        <div class="wg-list-main"><strong>{{ $s->member?->full_name ?? 'عضو' }}</strong><span>{{ $s->package_name_snapshot }} · {{ $s->currency }}</span></div>
                        <span class="wg-list-status {{ in_array($s->status,['active','expiring_soon']) ? 'is-green' : ($s->status === 'financial_overdue' ? 'is-red' : 'is-orange') }}">{{ $statusLabel($s->status) }}</span>
                        <time>{{ optional($s->start_date)->format('Y-m-d') }}</time>
                    </a>
                @empty
                    <div class="wg-dash-empty"><svg viewBox="0 0 24 24"><path d="M4 5h16v15H4zM8 2.5v5M16 2.5v5M4 10h16"/></svg><span>لا توجد اشتراكات بعد.</span></div>
                @endforelse
            </div>
        </section>

        <section class="wg-dash-panel wg-dash-appointments" dir="rtl">
            <div class="wg-dash-panel__head"><h2>مواعيد التغذية القادمة</h2><a href="{{ route('nutrition.appointments') }}" wire:navigate>عرض الكل</a></div>
            <div class="wg-dash-list">
                @forelse($todayAppointments as $a)
                    <a href="{{ route('nutrition.appointments') }}" wire:navigate class="wg-dash-list-row is-appointment">
                        <time dir="ltr">{{ substr((string) $a->start_time, 0, 5) }}</time>
                        <div class="wg-list-main"><strong>{{ $a->member?->full_name ?? $a->nutritionClient?->full_name ?? 'عميل' }}</strong><span>{{ $a->nutritionist?->name ?? 'اختصاصي التغذية' }}</span></div>
                    </a>
                @empty
                    <div class="wg-dash-empty"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>لا توجد مواعيد تغذية اليوم.</span></div>
                @endforelse
            </div>
        </section>

        <aside class="wg-dash-panel wg-dash-alerts" dir="rtl">
            <div class="wg-dash-panel__head"><h2>تنبيهات النظام</h2></div>
            <div class="wg-dash-alert-list">
                <a href="{{ route('subscriptions.index') }}" wire:navigate class="is-red">
                    <span class="wg-alert-icon"><svg viewBox="0 0 24 24"><path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 8v5M12 17h.01"/></svg></span>
                    <p>{{ number_format($stats['overdue_subscriptions']) }} اشتراك متأخر ماليًا</p><strong>تحتاج متابعة</strong><b>›</b>
                </a>
                <a href="{{ route('subscriptions.index') }}" wire:navigate class="is-orange">
                    <span class="wg-alert-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v15H4zM8 2.5v5M16 2.5v5M4 10h16"/></svg></span>
                    <p>{{ number_format($stats['expiring_soon']) }} اشتراك ينتهي خلال 30 يومًا</p><strong>قريبًا</strong><b>›</b>
                </a>
                <a href="{{ route('inventory.products') }}" wire:navigate class="is-orange">
                    <span class="wg-alert-icon"><svg viewBox="0 0 24 24"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/></svg></span>
                    <p>{{ number_format($stats['stock_alerts']) }} منتجات منخفضة المخزون</p><strong>مخزون</strong><b>›</b>
                </a>
            </div>
        </aside>
    </div>

    {{-- Dashboard quick-action modals --}}
        <div class="wg-action-backdrop" x-show="$wire.modal !== ''" x-cloak wire:key="dashboard-modal-layer">
                <section class="wg-action-modal wg-action-modal--md" data-wg-modal="member" x-show="$wire.modal === 'member'" x-cloak dir="rtl">
                    <header class="wg-action-head">
                        <div class="wg-action-title"><span class="wg-action-title-icon is-purple">＋</span><div><h2>إضافة عضو جديد</h2><p>أدخل بيانات العضو الأساسية</p></div></div>
                        <button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button>
                    </header>
                    <form wire:submit="createMember">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif
                            <div class="wg-action-grid wg-action-grid--2">
                                <label><span>الاسم الكامل <b>*</b></span><input wire:model="member_full_name" placeholder="أدخل الاسم الكامل"></label>
                                <label><span>رقم الهاتف <b>*</b></span><input wire:model="member_phone" dir="ltr" placeholder="777123456"></label>
                                <label><span>الجنس <b>*</b></span><select wire:model.live="member_gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></label>
                                <label><span>الفترة <b>*</b></span><select wire:model="member_assigned_period"><option value="men">فترة الرجال</option><option value="women">فترة النساء</option></select></label>
                                <label><span>تاريخ الميلاد</span><input type="date" wire:model="member_birth_date"></label>
                                <label><span>أو العمر</span><input type="number" min="5" max="100" wire:model="member_age" placeholder="العمر"></label>
                                <label><span>العنوان (اختياري)</span><input wire:model="member_address" placeholder="العنوان"></label>
                                <label><span>رقم الهوية (اختياري)</span><input wire:model="member_identity_number" placeholder="رقم الهوية"></label>
                            </div>
                            <label class="wg-action-field-full"><span>ملاحظات (اختياري)</span><textarea wire:model="member_notes" placeholder="أي ملاحظات إضافية..."></textarea></label>
                            <div class="wg-action-info">سيتم إنشاء كود العضوية والباركود وQR وتاريخ التسجيل تلقائيًا عند الحفظ.</div>
                        </div>
                        <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إلغاء</button><button class="wg-action-btn wg-action-btn--primary">حفظ العضو</button></footer>
                    </form>
                </section>

                <section class="wg-action-modal wg-action-modal--sm" data-wg-modal="attendance" x-show="$wire.modal === 'attendance'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-blue">✓</span><div><h2>تسجيل حضور</h2><p>ابحث عن العضو وسجّل دخوله</p></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <form wire:submit="recordAttendance">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif
                            @if($attendance_success)
                                <div class="wg-action-success-card"><span class="wg-action-success-icon">✓</span><div><strong>تم تسجيل الحضور بنجاح</strong><p>{{ $attendance_success['name'] }} · {{ $attendance_success['code'] }}</p><small>{{ $attendance_success['time'] }}</small></div></div>
                            @endif
                            <label><span>طريقة البحث</span><select wire:model="attendance_method"><option value="membership_code">كود العضوية</option><option value="phone">رقم الهاتف</option><option value="name">الاسم الكامل</option><option value="barcode">Barcode</option><option value="qr">QR</option></select></label>
                            <label><span>بيانات العضو <b>*</b></span><input wire:model="attendance_identifier" autofocus placeholder="أدخل الكود أو الهاتف أو الاسم..."></label>
                            <div class="wg-action-info">يتم التحقق تلقائيًا من حالة العضو، الاشتراك، الأقساط، الفترة وساعات العمل قبل تسجيل الحضور.</div>
                        </div>
                        <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إغلاق</button><button class="wg-action-btn wg-action-btn--primary">تسجيل الحضور</button></footer>
                    </form>
                </section>
                <section class="wg-action-modal wg-action-modal--xl wg-subscription-modal" data-wg-modal="subscription" x-show="$wire.modal === 'subscription'" x-cloak dir="rtl">
                    <header class="wg-action-head">
                        <div class="wg-action-title"><span class="wg-action-title-icon is-blue">▣</span><div><h2>اشتراك جديد</h2></div></div>
                        <button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button>
                    </header>
                    <div class="wg-sub-steps" aria-label="خطوات إنشاء الاشتراك">
                        <div :class="subStep === 1 ? 'is-active' : (subStep > 1 ? 'is-done' : '')"><i>1</i><span>اختيار العضو والبـاقة</span></div>
                        <div :class="subStep === 2 ? 'is-active' : (subStep > 2 ? 'is-done' : '')"><i>2</i><span>تفاصيل الاشتراك</span></div>
                        <div :class="subStep === 3 ? 'is-active' : ''"><i>3</i><span>الدفع والتأكيد</span></div>
                    </div>
                    <form wire:submit="createSubscription">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif

                            <div x-show="subStep===1" class="wg-sub-step-panel">
                                <div class="wg-action-grid wg-action-grid--2">
                                    <label><span>العضو <b>*</b></span><select wire:model.live="sub_member_id"><option value="">اختر العضو</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->membership_code }}</option>@endforeach</select></label>
                                    <label><span>الباقة <b>*</b></span><select wire:model.live="sub_package_id" @disabled($packages->isEmpty())><option value="">{{ $packages->isEmpty() ? 'لا توجد باقات مفعّلة' : 'اختر الباقة' }}</option>@foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></label>
                                    <label><span>تاريخ البداية المطلوب <b>*</b></span><input type="date" wire:model="sub_start_date"></label>
                                    <label><span>العملة <b>*</b></span><select wire:model.live="sub_currency"><option value="YER">🇾🇪 YER</option><option value="SAR">🇸🇦 SAR</option></select></label>
                                </div>
                                @if($packages->isEmpty())
                                    <div class="wg-action-info is-warning wg-no-packages-notice"><span>لا يمكن إنشاء اشتراك قبل إضافة باقة مفعّلة بسعر واحد على الأقل.</span><a href="{{ route('packages.index') }}" wire:navigate>الانتقال إلى إضافة باقة</a></div>
                                @else
                                    <div class="wg-action-info">إذا كان للعضو اشتراك حالي، يبدأ التجديد المبكر تلقائيًا بعد نهاية الاشتراك الحالي.</div>
                                @endif
                            </div>

                            <div x-show="subStep===2" x-cloak class="wg-sub-step-panel">
                                <div class="wg-sub-member-package">
                                    <div><span>العضو</span><strong>{{ $selectedSubMember?->full_name ?? '—' }}</strong><small>{{ $selectedSubMember?->membership_code }}</small></div>
                                    <div><span>الباقة المختارة</span><strong>{{ $selectedSubPackage?->name ?? '—' }}</strong><small>{{ $selectedSubPackage ? $selectedSubPackage->duration_value.' '.$selectedSubPackage->duration_unit : '' }}</small></div>
                                    <div><span>السعر الأصلي</span><strong dir="ltr">{{ number_format($subOriginalPrice, 0) }} {{ $sub_currency }}</strong></div>
                                </div>
                                <div class="wg-action-grid wg-action-grid--3">
                                    <label><span>الخصم</span><input type="text" inputmode="decimal" x-money wire:model.live.debounce.400ms="sub_discount_amount" placeholder="0"></label>
                                    <div class="wg-sub-price"><span>السعر بعد الخصم</span><strong>{{ number_format($subFinalPrice,0) }} {{ $sub_currency }}</strong></div>
                                    <div class="wg-sub-price"><span>العملة</span><strong>{{ $sub_currency }}</strong></div>
                                </div>
                                <div class="wg-payment-plan-grid">
                                    <label class="wg-payment-plan" :class="$wire.sub_payment_plan === 'full' ? 'is-selected':''"><input type="radio" value="full" wire:model.live="sub_payment_plan"><span><strong>دفع كامل</strong><small>دفع كامل قيمة الاشتراك</small></span></label>
                                    <label class="wg-payment-plan" :class="$wire.sub_payment_plan === 'installments' ? 'is-selected':''"><input type="radio" value="installments" wire:model.live="sub_payment_plan"><span><strong>أقساط</strong><small>دفع على دفعات</small></span></label>
                                </div>
                                @if($sub_payment_plan === 'full')
                                    <div class="wg-action-grid wg-action-grid--2 wg-full-payment-row">
                                        <label><span>المبلغ الكامل <b>*</b></span><input type="text" inputmode="decimal" x-money wire:model="sub_first_payment_amount" readonly aria-readonly="true"><small>يُحتسب تلقائيًا من سعر الباقة بعد الخصم ولا يمكن تخفيضه.</small></label>
                                        <div class="wg-sub-price is-payment-summary"><span>حالة السداد</span><strong class="is-green">سداد كامل</strong><small>{{ number_format($subFinalPrice,0) }} {{ $sub_currency }}</small></div>
                                    </div>
                                @else
                                    <div class="wg-action-grid wg-action-grid--3">
                                        <label><span>مبلغ الدفعة الأولى <b>*</b></span><input type="number" min="0" max="{{ $subFinalPrice }}" step="0.01" wire:model.live="sub_first_payment_amount"></label>
                                        <label><span>إجمالي عدد الأقساط</span><input type="number" min="2" max="24" wire:model.live="sub_installment_count"></label>
                                        <div class="wg-sub-price"><span>قيمة القسط التقريبية</span><strong>{{ number_format($subInstallmentAmount,0) }} {{ $sub_currency }}</strong></div>
                                    </div>
                                @endif
                                @if($sub_payment_plan === 'installments')
                                    <div class="wg-installments-row">
                                        @foreach($sub_installment_due_dates as $i => $due)
                                            <label><span>موعد القسط {{ $i + 2 }}</span><input type="date" wire:model="sub_installment_due_dates.{{ $i }}"></label>
                                        @endforeach
                                    </div>
                                    <small class="wg-sub-rule">الدفعة الأولى يجب ألا تقل عن 50% من قيمة الاشتراك، وآخر قسط يعالج فروقات التقريب تلقائيًا.</small>
                                @endif
                                <label class="wg-action-field-full"><span>ملاحظات (اختياري)</span><textarea wire:model="sub_notes" placeholder="اكتب ملاحظة إن وجدت..."></textarea></label>
                            </div>

                            <div x-show="subStep===3" x-cloak class="wg-sub-step-panel">
                                <div class="wg-sub-member-package">
                                    <div><span>العضو</span><strong>{{ $selectedSubMember?->full_name ?? '—' }}</strong><small>{{ $selectedSubMember?->membership_code }}</small></div>
                                    <div><span>الباقة المختارة</span><strong>{{ $selectedSubPackage?->name ?? '—' }}</strong></div>
                                    <div><span>المبلغ النهائي</span><strong class="is-green" dir="ltr">{{ number_format($subFinalPrice,0) }} {{ $sub_currency }}</strong></div>
                                </div>
                                <h3 class="wg-action-section-title">معلومات الدفع</h3>
                                <div class="wg-action-grid wg-action-grid--3">
                                    <div class="wg-sub-price"><span>إجمالي الاشتراك</span><strong>{{ number_format($subFinalPrice,0) }} {{ $sub_currency }}</strong></div>
                                    <div class="wg-sub-price"><span>المتبقي بعد الدفعة</span><strong>{{ number_format($subRemaining,0) }} {{ $sub_currency }}</strong></div>
                                    <div class="wg-sub-price"><span>المبلغ المدفوع الآن</span><strong class="is-green">{{ number_format((float)($sub_first_payment_amount ?: 0),0) }} {{ $sub_currency }}</strong></div>
                                </div>
                                <div class="wg-payment-plan-grid">
                                    <label class="wg-payment-plan {{ $sub_payment_method === 'cash' ? 'is-selected' : '' }}"><input type="radio" value="cash" wire:model.live="sub_payment_method"><span><strong>نقدي</strong><small>دفع نقدًا في النادي</small></span></label>
                                    <label class="wg-payment-plan {{ $sub_payment_method === 'transfer' ? 'is-selected' : '' }}"><input type="radio" value="transfer" wire:model.live="sub_payment_method"><span><strong>تحويل أو صرافة محلية</strong><small>خدمة أو صرافة معتمدة</small></span></label>
                                </div>
                                @if($sub_payment_method === 'transfer')
                                    <div class="wg-action-grid wg-action-grid--2"><label><span>خدمة التحويل/الصرافة <b>*</b></span><input wire:model.live.debounce.250ms="sub_transfer_service" placeholder="اسم الخدمة أو الصرافة"></label><label><span>رقم مرجع السند @if($requireTransferReference)<b>*</b>@else (اختياري)@endif</span><input wire:model.live.debounce.250ms="sub_transfer_reference" placeholder="أدخل رقم المرجع"></label></div>
                                @endif
                                <div class="wg-action-grid wg-action-grid--2">
                                    <label><span>تاريخ الاستلام</span><input type="date" value="{{ now('Asia/Aden')->toDateString() }}" disabled></label>
                                    <label class="wg-file-field {{ $sub_payment_method === 'transfer' && $requirePaymentProof ? 'is-required' : '' }}"><span>إرفاق سند الدفع @if($sub_payment_method === 'transfer' && $requirePaymentProof)<b>*</b>@else (اختياري)@endif</span><input type="file" wire:model="sub_payment_proof" accept=".jpg,.jpeg,.png,.pdf"><small wire:loading.remove wire:target="sub_payment_proof">JPG, PNG, PDF حتى 2MB</small><small wire:loading wire:target="sub_payment_proof">جارٍ رفع السند...</small>@if($sub_payment_proof)<small class="is-uploaded">✓ تم إرفاق السند</small>@endif</label>
                                </div>
                                @if($sub_payment_method === 'transfer' && (blank($sub_transfer_service) || ($requireTransferReference && blank($sub_transfer_reference)) || ($requirePaymentProof && !$sub_payment_proof)))
                                    <div class="wg-action-info is-warning">أكمل بيانات التحويل الإلزامية حسب سياسة الدفع لتفعيل زر التأكيد.</div>
                                @else
                                    <div class="wg-action-info is-blue">سيتم إنشاء الاشتراك وتسجيل {{ $sub_payment_plan === 'full' ? 'المبلغ الكامل' : 'الدفعة الأولى' }} في نفس العملية.</div>
                                @endif
                            </div>
                        </div>
                        <footer class="wg-action-foot wg-action-foot--spread">
                            <button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إلغاء</button>
                            <div class="wg-action-foot-group">
                                <button x-show="subStep>1" x-cloak type="button" class="wg-action-btn" x-on:click="subStep--">رجوع</button>
                                <button x-show="subStep===1" type="button" class="wg-action-btn wg-action-btn--primary" :disabled="!$wire.sub_member_id || !$wire.sub_package_id || !$wire.sub_start_date" x-on:click="subStep=2">التالي: تفاصيل الاشتراك</button>
                                <button x-show="subStep===2" x-cloak type="button" class="wg-action-btn wg-action-btn--primary" :disabled="Number($wire.sub_first_payment_amount || 0) <= 0" x-on:click="subStep=3">التالي: الدفع والتأكيد</button>
                                <button x-show="subStep===3" x-cloak type="submit" class="wg-action-btn wg-action-btn--primary" wire:loading.attr="disabled" wire:target="createSubscription,sub_payment_proof" @disabled($sub_payment_method === 'transfer' && (blank($sub_transfer_service) || ($requireTransferReference && blank($sub_transfer_reference)) || ($requirePaymentProof && !$sub_payment_proof)))><span wire:loading.remove wire:target="createSubscription">تأكيد الاشتراك واستلام الدفعة</span><span wire:loading wire:target="createSubscription">جارٍ إنشاء الاشتراك...</span></button>
                            </div>
                        </footer>
                    </form>
                </section>

            @if($modal === 'subscription-success' && $createdSubscription)
                @php($paidNow = (float)$createdSubscription->payments->where('status','completed')->sum('amount'))
                <section class="wg-action-modal wg-action-modal--md wg-success-modal" dir="rtl">
                    <button type="button" class="wg-action-close wg-success-close" wire:click="closeModal">×</button>
                    <div class="wg-success-hero"><div class="wg-success-check">✓</div><h2>تم إنشاء الاشتراك بنجاح!</h2><p>تم تسجيل اشتراك جديد للعضو بنجاح.</p></div>
                    <div class="wg-action-body">
                        <div class="wg-sub-member-package"><div><span>العضو</span><strong>{{ $createdSubscription->member?->full_name }}</strong><small>{{ $createdSubscription->member?->membership_code }}</small></div><div><span>الباقة</span><strong>{{ $createdSubscription->package_name_snapshot }}</strong><small>{{ $createdSubscription->duration_value_snapshot }} {{ $createdSubscription->duration_unit_snapshot }}</small></div></div>
                        <div class="wg-action-grid wg-action-grid--3"><div class="wg-sub-price"><span>تاريخ البداية</span><strong>{{ $createdSubscription->start_date->format('Y-m-d') }}</strong></div><div class="wg-sub-price"><span>تاريخ النهاية</span><strong>{{ $createdSubscription->end_date->format('Y-m-d') }}</strong></div><div class="wg-sub-price"><span>الحالة</span><strong class="is-green">{{ $createdSubscription->status === 'upcoming' ? 'قادم' : 'نشط' }}</strong></div></div>
                        <div class="wg-action-grid wg-action-grid--3"><div class="wg-sub-price"><span>المبلغ المستلم الآن</span><strong class="is-green">{{ number_format($paidNow,0) }} {{ $createdSubscription->currency }}</strong></div><div class="wg-sub-price"><span>المتبقي</span><strong class="is-orange">{{ number_format(max(0,(float)$createdSubscription->final_price-$paidNow),0) }} {{ $createdSubscription->currency }}</strong></div><div class="wg-sub-price"><span>عدد الأقساط</span><strong>{{ $createdSubscription->installment_count }}</strong></div></div>
                        <div class="wg-action-info">تم تطبيق الاشتراك بنجاح ويمكنك الآن رؤيته داخل صفحة الاشتراكات.</div>
                    </div>
                    <footer class="wg-action-foot"><button type="button" class="wg-action-btn" wire:click="finishSubscriptionSuccess">العودة للوحة التحكم</button><a class="wg-action-btn wg-action-btn--primary" href="{{ route('subscriptions.index') }}" wire:navigate>عرض الاشتراك</a></footer>
                </section>
            @endif

                <section class="wg-action-modal wg-action-modal--lg" data-wg-modal="payment" x-show="$wire.modal === 'payment'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-green">▣</span><div><h2>استلام دفعة</h2></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <form wire:submit="receivePayment">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif
                            <label><span>اختر العضو <b>*</b></span><select wire:model.live="payment_member_id"><option value="">ابحث واختر العضو...</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->membership_code }}</option>@endforeach</select></label>
                            @if($paymentSubscription && $paymentInstallment)
                                <div class="wg-current-sub"><h3>الاشتراك الحالي</h3><div class="wg-action-grid wg-action-grid--4"><div><span>الباقة</span><strong>{{ $paymentSubscription->package_name_snapshot }}</strong></div><div><span>الاستحقاق</span><strong>{{ $paymentInstallment->due_date->format('Y-m-d') }}</strong></div><div><span>المبلغ المستحق</span><strong class="is-orange">{{ number_format((float)$paymentInstallment->amount,0) }} {{ $paymentSubscription->currency }}</strong></div><div><span>العملة</span><strong>{{ $paymentSubscription->currency }}</strong></div></div></div>
                                <label><span>مبلغ الدفعة الحالية <b>*</b></span><div class="wg-input-with-unit"><input type="text" inputmode="decimal" x-money wire:model="payment_amount" placeholder="0"><i>{{ $payment_currency }}</i></div></label>
                                <h3 class="wg-action-section-title">طريقة الدفع</h3>
                                <div class="wg-payment-plan-grid"><label class="wg-payment-plan {{ $payment_method === 'cash' ? 'is-selected' : '' }}"><input type="radio" value="cash" wire:model.live="payment_method"><span><strong>نقدي</strong><small>دفع نقدًا في النادي</small></span></label><label class="wg-payment-plan {{ $payment_method === 'transfer' ? 'is-selected' : '' }}"><input type="radio" value="transfer" wire:model.live="payment_method"><span><strong>تحويل أو صرافة محلية</strong><small>تحويل عبر خدمة معتمدة</small></span></label></div>
                                @if($payment_method === 'transfer')<div class="wg-action-grid wg-action-grid--2"><label><span>خدمة التحويل/الصرافة <b>*</b></span><input wire:model="payment_transfer_service" placeholder="اختر خدمة"></label><label><span>رقم المرجع @if($requireTransferReference)<b>*</b>@else (اختياري)@endif</span><input wire:model="payment_transfer_reference" placeholder="أدخل رقم المرجع"></label></div>@endif
                                <div class="wg-action-grid wg-action-grid--2"><label class="wg-file-field {{ $payment_method === 'transfer' && $requirePaymentProof ? 'is-required' : '' }}"><span>إرفاق إثبات الدفع @if($payment_method === 'transfer' && $requirePaymentProof)<b>*</b>@else (اختياري)@endif</span><input type="file" wire:model="payment_proof" accept=".jpg,.jpeg,.png,.pdf"><small>JPG, PNG, PDF حتى 2MB</small></label><label><span>تاريخ الاستلام</span><input type="date" value="{{ now('Asia/Aden')->toDateString() }}" disabled></label></div>
                            @else
                                @if($payment_member_id)<div class="wg-action-empty">لا توجد دفعة مستحقة لهذا العضو حاليًا.</div>@endif
                            @endif
                        </div>
                        <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إلغاء</button><button class="wg-action-btn wg-action-btn--primary" @disabled(!$payment_installment_id)>تأكيد استلام الدفعة</button></footer>
                    </form>
                </section>
                <section class="wg-action-modal wg-action-modal--lg" data-wg-modal="expense" x-show="$wire.modal === 'expense'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-orange">−</span><div><h2>إضافة مصروف</h2></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <form wire:submit="createExpense">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif
                            <div class="wg-action-grid wg-action-grid--2"><label><span>اسم المصروف <b>*</b></span><input wire:model="expense_title" placeholder="أدخل اسم المصروف"></label><label><span>تصنيف المصروف <b>*</b></span><select wire:model="expense_category_id"><option value="">اختر التصنيف</option>@foreach($expenseCategories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label><label><span>تاريخ المصروف <b>*</b></span><input type="date" wire:model="expense_date"></label><label><span>المبلغ <b>*</b></span><div class="wg-input-with-unit"><input type="text" inputmode="decimal" x-money wire:model="expense_amount" placeholder="أدخل المبلغ"><select wire:model="expense_currency"><option>YER</option><option>SAR</option></select></div></label></div>
                            <h3 class="wg-action-section-title">طريقة الدفع</h3><div class="wg-payment-plan-grid"><label class="wg-payment-plan {{ $expense_payment_method === 'cash' ? 'is-selected':'' }}"><input type="radio" value="cash" wire:model.live="expense_payment_method"><span><strong>نقدي</strong><small>دفع نقدي</small></span></label><label class="wg-payment-plan {{ $expense_payment_method === 'transfer' ? 'is-selected':'' }}"><input type="radio" value="transfer" wire:model.live="expense_payment_method"><span><strong>تحويل أو صرافة محلية</strong><small>تحويل عبر خدمة معتمدة</small></span></label></div>
                            @if($expense_payment_method === 'transfer')<label><span>رقم المرجع <b>*</b></span><input wire:model="expense_transfer_reference" placeholder="مرجع التحويل"></label>@endif
                            <label class="wg-action-field-full"><span>ملاحظات (اختياري)</span><textarea wire:model="expense_notes" placeholder="اكتب أي ملاحظات إضافية..."></textarea></label>
                            <label class="wg-file-field"><span>إرفاق فاتورة أو إيصال <b>*</b></span><input type="file" wire:model="expense_receipt" accept=".jpg,.jpeg,.png,.pdf" required><small>إلزامي — JPG, PNG, PDF حتى 2MB</small></label>
                        </div>
                        <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إلغاء</button><button class="wg-action-btn wg-action-btn--primary">حفظ المصروف</button></footer>
                    </form>
                </section>
                <section class="wg-action-modal wg-action-modal--xl" data-wg-modal="product" x-show="$wire.modal === 'product'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-blue">◇</span><div><h2>إضافة منتج</h2></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <form wire:submit="createProduct">
                        <div class="wg-action-body">
                            @if($errors->any())<div class="wg-action-errors">@foreach($errors->all() as $e)<span>{{ $e }}</span>@endforeach</div>@endif
                            <h3 class="wg-action-section-title">معلومات المنتج</h3>
                            <div class="wg-action-grid wg-action-grid--2"><label><span>اسم المنتج <b>*</b></span><input wire:model="product_name" placeholder="أدخل اسم المنتج"></label><label><span>الباركود (اختياري)</span><input wire:model="product_barcode" placeholder="أدخل الباركود"></label><label><span>التصنيف <b>*</b></span><select wire:model="product_category_id"><option value="">اختر التصنيف</option>@foreach($productCategories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label><label><span>العملة <b>*</b></span><select wire:model="product_currency"><option value="YER">🇾🇪 YER</option><option value="SAR">🇸🇦 SAR</option></select></label></div>
                            <h3 class="wg-action-section-title">التسعير والمخزون</h3>
                            <div class="wg-action-grid wg-action-grid--3"><label><span>سعر البيع <b>*</b></span><input type="text" inputmode="decimal" x-money wire:model="product_selling_price" placeholder="0"></label><label><span>سعر التكلفة</span><input type="text" inputmode="decimal" x-money wire:model="product_purchase_cost" placeholder="0"></label><label><span>الحد الأدنى للتنبيه</span><input type="number" min="0" wire:model="product_minimum_quantity"></label></div>
                            <div class="wg-action-info">الكمية تبدأ من صفر وتزيد فقط عبر المشتريات المعتمدة حتى يبقى سجل حركة المخزون صحيحًا.</div>
                            <div class="wg-action-grid wg-action-grid--2"><label class="wg-action-field-full"><span>ملاحظات (اختياري)</span><textarea wire:model="product_notes" placeholder="اكتب ملاحظات..."></textarea></label><label class="wg-file-field"><span>صورة المنتج (اختياري)</span><input type="file" wire:model="product_image" accept="image/*"><small>PNG / JPG حتى 2MB</small></label></div>
                        </div>
                        <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إلغاء</button><button class="wg-action-btn wg-action-btn--primary">حفظ المنتج</button></footer>
                    </form>
                </section>
                <section class="wg-action-modal wg-action-modal--lg" data-wg-modal="search" x-show="$wire.modal === 'search'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-blue">⌕</span><div><h2>البحث العام</h2><p>ابحث في بيانات النظام</p></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <div class="wg-action-body"><div class="wg-global-search-box"><svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg><input type="search" wire:model.live.debounce.250ms="globalSearch" autofocus placeholder="اكتب كلمة البحث..."></div>
                        <div class="wg-search-tabs">
                            <button type="button" class="{{ $globalSearchType === 'all' ? 'is-active' : '' }}" wire:click="setGlobalSearchType('all')">الكل</button>
                            <button type="button" class="{{ $globalSearchType === 'member' ? 'is-active' : '' }}" wire:click="setGlobalSearchType('member')">الأعضاء</button>
                            <button type="button" class="{{ $globalSearchType === 'subscription' ? 'is-active' : '' }}" wire:click="setGlobalSearchType('subscription')">الاشتراكات</button>
                            <button type="button" class="{{ $globalSearchType === 'product' ? 'is-active' : '' }}" wire:click="setGlobalSearchType('product')">المنتجات</button>
                        </div>
                        @if(mb_strlen(trim($globalSearch)) < 2)<div class="wg-action-empty">ابدأ بكتابة حرفين على الأقل للبحث بالاسم، الهاتف، كود العضوية، الباقة أو المنتج.</div>@else<div class="wg-search-result-count">{{ $globalResults->count() }} نتيجة</div><div class="wg-global-results">@forelse($globalResults as $r)<div class="wg-global-result"><span class="wg-global-result-icon {{ $r['type'] }}">{{ $r['type']==='member'?'♙':($r['type']==='subscription'?'♛':'◇') }}</span><div><strong>{{ $r['title'] }}</strong><p>{{ $r['subtitle'] }}</p></div><em>{{ $r['meta'] }}</em><b>‹</b></div>@empty<div class="wg-action-empty">لا توجد نتائج مطابقة.</div>@endforelse</div>@endif
                    </div>
                    <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إغلاق</button></footer>
                </section>
                <section class="wg-action-modal wg-action-modal--lg" data-wg-modal="notifications" x-show="$wire.modal === 'notifications'" x-cloak dir="rtl">
                    <header class="wg-action-head"><div class="wg-action-title"><span class="wg-action-title-icon is-purple">♢</span><div><h2>الإشعارات</h2></div></div><button type="button" class="wg-action-close" x-on:click="$wire.$set('modal', '', false)">×</button></header>
                    <div class="wg-action-body"><div class="wg-notify-tabs">
                        <button type="button" class="{{ $notificationType === 'all' ? 'is-active' : '' }}" wire:click="setNotificationType('all')">الكل</button>
                        <button type="button" class="{{ $notificationType === 'subscriptions' ? 'is-active' : '' }}" wire:click="setNotificationType('subscriptions')">اشتراكات</button>
                        <button type="button" class="{{ $notificationType === 'payments' ? 'is-active' : '' }}" wire:click="setNotificationType('payments')">مدفوعات</button>
                        <button type="button" class="{{ $notificationType === 'inventory' ? 'is-active' : '' }}" wire:click="setNotificationType('inventory')">مخزون</button>
                        <button type="button" class="{{ $notificationType === 'system' ? 'is-active' : '' }}" wire:click="setNotificationType('system')">النظام</button>
                    </div><div class="wg-notify-section"><h3>اليوم</h3>
                        @if(in_array($notificationType, ['all', 'subscriptions'], true))
                            <div class="wg-notify-row is-red"><span>⚠</span><div><strong>اشتراكات متأخرة</strong><p>{{ number_format($stats['overdue_subscriptions']) }} اشتراك تحتاج متابعة وسداد.</p></div><small>الآن</small></div>
                            <div class="wg-notify-row is-orange"><span>▣</span><div><strong>اشتراكات تنتهي قريبًا</strong><p>{{ number_format($stats['expiring_soon']) }} اشتراك تنتهي خلال 30 يوم.</p></div><small>اليوم</small></div>
                        @endif
                        @if(in_array($notificationType, ['all', 'payments'], true) && $latestPayment)<div class="wg-notify-row is-green"><span>✓</span><div><strong>تم استلام دفعة</strong><p>{{ number_format((float)$latestPayment->amount,0) }} {{ $latestPayment->currency }} من {{ $latestPayment->subscription?->member?->full_name ?? 'عضو' }}.</p></div><small>{{ $latestPayment->paid_at?->diffForHumans() }}</small></div>@endif
                        @if(in_array($notificationType, ['all', 'system'], true) && $latestMember)<div class="wg-notify-row is-purple"><span>＋</span><div><strong>عضو جديد</strong><p>تمت إضافة {{ $latestMember->full_name }} إلى النظام.</p></div><small>{{ $latestMember->created_at?->diffForHumans() }}</small></div>@endif
                        @if(in_array($notificationType, ['all', 'inventory'], true) && $lowStockProduct)<div class="wg-notify-row is-orange"><span>◇</span><div><strong>منتج منخفض</strong><p>{{ $lowStockProduct->name }} — الكمية المتاحة {{ $lowStockProduct->current_quantity }}.</p></div><small>مخزون</small></div>@endif
                        @if(in_array($notificationType, ['all', 'system'], true) && $nextAppointment)<div class="wg-notify-row is-blue"><span>▣</span><div><strong>موعد تغذية</strong><p>{{ $nextAppointment->member?->full_name ?? $nextAppointment->nutritionClient?->full_name ?? 'عميل' }} · {{ $nextAppointment->appointment_date?->format('Y-m-d') }}</p></div><small>{{ substr((string)$nextAppointment->start_time,0,5) }}</small></div>@endif
                    </div></div>
                    <footer class="wg-action-foot"><button type="button" class="wg-action-btn" x-on:click="$wire.$set('modal', '', false)">إغلاق</button><a href="{{ route('subscriptions.index') }}" wire:navigate class="wg-action-btn wg-action-btn--primary">عرض التفاصيل</a></footer>
                </section>
        </div>

</div>
