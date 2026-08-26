<div class="wg-nut-page wg-nut-finance-style" dir="rtl"
     x-data="{
        quickOpen: false,
        bookingOpen: $wire.entangle('showBookingModal'),
        clientOpen: $wire.entangle('showClientModal'),
        scheduleOpen: $wire.entangle('showScheduleModal'),
        paymentOpen: $wire.entangle('showPaymentModal'),
        clientType: $wire.entangle('client_type'),
        serviceType: $wire.entangle('service_type'),
        visitType: $wire.entangle('visit_type'),
        duration: $wire.entangle('duration_minutes')
     }"
     x-on:nutrition-appointment-saved.window="bookingOpen = false"
     x-on:nutrition-client-saved.window="clientOpen = false; bookingOpen = true"
     x-on:nutrition-schedule-saved.window="scheduleOpen = false"
     x-on:nutrition-payment-saved.window="paymentOpen = false">
    @php
        $statusMeta = [
            'booked' => ['محجوز','is-orange'],
            'confirmed' => ['مؤكد','is-green'],
            'completed' => ['مكتمل','is-blue'],
            'cancelled' => ['ملغي','is-gray'],
            'no_show' => ['لم يحضر','is-red'],
        ];
        $serviceTypeMeta = [
            'consultation' => ['استشارة أولية', 'تقييم شامل وخطة بداية'],
            'follow_up' => ['جلسة متابعة', 'متابعة التقدم وتعديل الخطة'],
            'body_analysis' => ['تحليل مكونات الجسم', 'وزن ودهون وكتلة عضلية'],
            'meal_plan' => ['إعداد خطة غذائية', 'برنامج وجبات مخصص'],
            'measurement' => ['قياسات فقط', 'تسجيل القياسات والمؤشرات'],
        ];
        $visitTypeMeta = ['in_person' => 'حضوري في العيادة', 'remote' => 'متابعة عن بُعد'];
        $fmtTime = function ($time) {
            $raw = substr((string) $time, 0, 5);
            if (!str_contains($raw, ':')) return $raw;
            [$h,$m] = array_map('intval', explode(':',$raw));
            $suffix = $h < 12 ? 'ص' : 'م';
            $hour = $h % 12; if ($hour === 0) $hour = 12;
            return sprintf('%02d:%02d %s',$hour,$m,$suffix);
        };
        $clientName = fn($a) => $a->member?->full_name ?? $a->nutritionClient?->full_name ?? '—';
        $clientType = fn($a) => $a->member_id ? 'عضو' : 'عميل خاص';
        $nutritionistName = fn($a) => $a->nutritionist?->name ?: ($a->nutritionist?->username ?: '—');
        $measurementCardMeta = [
            'weight' => ['الوزن','kg','⚖'], 'height' => ['الطول','cm','↕'],
            'body_fat' => ['دهون الجسم','%','◉'], 'muscle' => ['نسبة العضلات','%','♧'],
            'water' => ['نسبة الماء','%','◌'], 'visceral_fat' => ['الدهون الحشوية','','◎'],
            'muscle_mass' => ['كتلة العضلات','kg','♧'], 'bone_mass' => ['كتلة العظام','kg','◇'], 'chest' => ['محيط الصدر','cm','▱'],
            'waist' => ['محيط الخصر','cm','⌁'], 'arm' => ['محيط الذراع','cm','⌇'], 'hip' => ['محيط الورك','cm','◫'],
            'thigh' => ['محيط الفخذ','cm','◫'],
        ];
    @endphp

    @if (session('success'))
        <div class="wg-nut-flash is-success" role="status" x-data="{ show: true }" x-show="show" x-transition>
            <div class="wg-nut-flash-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
            </div>
            <div class="wg-nut-flash-content">
                <strong>عملية ناجحة</strong>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="wg-nut-flash-close" x-on:click="show = false" aria-label="إغلاق">×</button>
        </div>
    @endif

    @if ($errors->any() && !$showBookingModal && !$showEditModal && !$showClientModal && !$showScheduleModal && !$showPaymentModal && !$showCancelModal && !$showReverseModal)
        <div class="wg-nut-flash is-error" role="alert" x-data="{ show: true }" x-show="show" x-transition>
            <div class="wg-nut-flash-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
            </div>
            <div class="wg-nut-flash-content">
                <strong>تنبيه</strong>
                <div>@foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach</div>
            </div>
            <button type="button" class="wg-nut-flash-close" x-on:click="show = false" aria-label="إغلاق">×</button>
        </div>
    @endif

    <section class="wg-nut-commandbar">
        <nav class="wg-nut-tabs" aria-label="أقسام التغذية">
            <a href="{{ route('nutrition.appointments') }}" wire:navigate class="is-active">المواعيد والعيادة</a>
            <a href="{{ route('nutrition.measurements') }}" wire:navigate>القياسات</a>
        </nav>

        <div class="wg-nut-primary-actions">
            <div class="wg-nut-split-action" x-on:click.outside="quickOpen = false">
                @if($canCreateAppointments)
                    <button class="wg-nut-btn wg-nut-btn-primary wg-nut-book-now" type="button"
                            x-on:click="bookingOpen = true; quickOpen = false; $wire.openBooking()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16M12 14v4M10 16h4"/></svg>
                        حجز موعد تغذية
                    </button>
                @endif
                <button type="button" class="wg-nut-more-actions" x-on:click="quickOpen = !quickOpen" aria-label="إجراءات تغذية إضافية" x-bind:aria-expanded="quickOpen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div class="wg-nut-action-menu" x-cloak x-show="quickOpen" x-transition.origin.top.right>
                    <strong>إضافة جديدة</strong>
                    @if($canCreateAppointments)<button type="button" x-on:click="bookingOpen = true; quickOpen = false; $wire.openBooking()"><i class="is-blue">＋</i><span>حجز موعد جديد<small>اختيار العميل والخدمة والوقت</small></span></button>@endif
                    @if($canCreateClients)<button type="button" x-on:click="clientOpen = true; quickOpen = false; $wire.openClient()"><i class="is-purple">◎</i><span>إضافة عميل خاص<small>لغير المشتركين في النادي</small></span></button>@endif
                    @if($canRecordMeasurements)<button type="button" x-on:click="quickOpen = false" wire:click="gotoMeasurements()"><i class="is-green">↗</i><span>تسجيل قياسات<small>فتح سجل قياسات الجسم</small></span></button>@endif
                    @if($canManageSchedules)<button type="button" x-on:click="scheduleOpen = true; quickOpen = false; $wire.openSchedule()"><i class="is-orange">◷</i><span>إضافة فترة عمل<small>ضبط جدول الأخصائي</small></span></button>@endif
                    @if(auth()->user()->role === 'owner')<a href="{{ route('staff.index', ['new' => 1, 'role' => 'nutritionist']) }}" wire:navigate><i class="is-blue">♙</i><span>إضافة اختصاصي<small>إنشاء حساب اختصاصي جديد</small></span></a>@endif
                </div>
            </div>
        </div>
    </section>

    <section class="wg-nut-kpis">
        <article class="wg-nut-kpi"><i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg></i><div><span>مواعيد اليوم</span><strong>{{ number_format($stats['total']) }}</strong><small>موعد مسجل</small></div></article>
        <article class="wg-nut-kpi"><i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></i><div><span>مؤكدة</span><strong>{{ number_format($stats['confirmed']) }}</strong><small>جاهزة للجلسة</small></div></article>
        <article class="wg-nut-kpi"><i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5"/></svg></i><div><span>غير مدفوعة</span><strong>{{ number_format($stats['unpaid']) }}</strong><small>تحتاج تحصيل</small></div></article>
        <article class="wg-nut-kpi"><i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8M16 8l-8 8"/></svg></i><div><span>لم يحضر</span><strong>{{ number_format($stats['no_show']) }}</strong><small>في اليوم المحدد</small></div></article>
        <article class="wg-nut-kpi"><i class="is-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8a3 3 0 1 1 0 6"/></svg></i><div><span>عملاء التغذية</span><strong>{{ number_format($stats['clients']) }}</strong><small>أعضاء وعملاء خاصون</small></div></article>
    </section>

    <section class="wg-nut-main-grid">
        <article class="wg-nut-card wg-nut-table-card">
            <div class="wg-nut-table-top">
                <div><h2>جدول مواعيد العيادة</h2><p>{{ \Carbon\CarbonImmutable::parse($selectedDate ?: now('Asia/Aden')->toDateString())->locale('ar')->translatedFormat('l d F Y') }}</p></div>
                <div class="wg-nut-table-filters">
                    <input id="nutrition-search" wire:model.live.debounce.350ms="search" type="search" placeholder="ابحث عن العميل أو رقم الهاتف...">
                    <input wire:model.live="selectedDate" type="date" title="تاريخ المواعيد">
                    <select wire:model.live="statusFilter"><option value="">كل الحالات</option><option value="booked">محجوز</option><option value="confirmed">مؤكد</option><option value="completed">مكتمل</option><option value="no_show">لم يحضر</option><option value="cancelled">ملغي</option></select>
                    @if(auth()->user()->role !== 'nutritionist')<select wire:model.live="nutritionistFilter"><option value="">كل الأخصائيين</option>@foreach($nutritionists as $n)<option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>@endforeach</select>@endif
                    <button type="button" wire:click="resetFilters" title="إعادة تعيين الفلاتر"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg></button>
                </div>
            </div>

            <div class="wg-nut-table-scroll">
                <table class="wg-nut-table">
                    <thead><tr><th>الوقت</th><th>العميل</th><th>الخدمة / النوع</th><th>الأخصائي</th><th>المدة</th><th>السعر</th><th>الدفع</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                    @forelse($appointments as $a)
                        @php [$statusLabel,$statusClass] = $statusMeta[$a->status] ?? [$a->status,'is-gray']; @endphp
                        <tr wire:key="nutrition-appointment-{{ $a->id }}" class="{{ $selectedAppointmentId === $a->id ? 'is-selected' : '' }}">
                            <td class="wg-nut-time">{{ $fmtTime($a->start_time) }}</td>
                            <td><button class="wg-nut-client" type="button" wire:click="selectAppointment({{ $a->id }})"><span>{{ mb_substr($clientName($a),0,1) }}</span><div><strong>{{ $clientName($a) }}</strong><small>{{ $a->member?->membership_code ?? ($a->nutritionClient?->phone ?: 'عميل تغذية') }}</small></div></button></td>
                            <td><span class="wg-nut-service-cell"><strong>{{ $serviceTypeMeta[$a->service_type ?? 'consultation'][0] ?? 'استشارة' }}</strong><small>{{ $clientType($a) }} • {{ $visitTypeMeta[$a->visit_type ?? 'in_person'] ?? 'حضوري' }}</small></span></td>
                            <td>{{ $nutritionistName($a) }}</td>
                            <td>{{ $a->duration_minutes }} د</td>
                            <td class="wg-nut-money">{{ number_format((float)$a->price, $a->currency === 'SAR' ? 2 : 0) }} <small>{{ $a->currency }}</small></td>
                            <td><span class="wg-nut-pill {{ $a->payment_status === 'paid' ? 'is-green' : 'is-red' }}">{{ $a->payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}</span>@if(($canManagePayments || $canReversePayments) && $a->payment?->proof_path)<a class="wg-subline" href="{{ route('appointments.payments.proof', $a->payment) }}" target="_blank" rel="noopener">عرض السند</a>@endif</td>
                            <td><span class="wg-nut-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td><div class="wg-nut-row-actions">
                                <button type="button" title="تحديد الموعد" wire:click="selectAppointment({{ $a->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg></button>
                                @if($canManageAppointments && !in_array($a->status,['completed','cancelled','no_show']))<button class="is-blue" type="button" title="تعديل الموعد" x-on:click="bookingOpen = true; $wire.openEdit({{ $a->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/></svg></button>@endif
                                @if($canManagePayments && $a->payment_status === 'unpaid' && $a->status !== 'cancelled')<button class="is-green" type="button" title="استلام الدفعة" x-on:click="paymentOpen = true; $wire.openPayment({{ $a->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5M7 14h3"/></svg></button>@endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="wg-nut-empty"><strong>لا توجد مواعيد مطابقة</strong><span>غيّر التاريخ أو الفلاتر، أو احجز موعداً جديداً.</span></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="wg-nut-table-footer"><span>عرض {{ $appointments->firstItem() ?? 0 }} - {{ $appointments->lastItem() ?? 0 }} من {{ $appointments->total() }} موعد</span><div>{{ $appointments->onEachSide(1)->links() }}</div></div>
        </article>

        <aside class="wg-nut-side">
            <article class="wg-nut-card wg-nut-upcoming">
                <div class="wg-nut-card-head"><h3>المواعيد القادمة</h3><span>{{ $upcoming->count() }}</span></div>
                <div class="wg-nut-upcoming-list">
                    @forelse($upcoming as $a)
                        <button type="button" wire:click="selectAppointment({{ $a->id }})" class="{{ $selectedAppointmentId === $a->id ? 'is-selected' : '' }}"><i class="{{ $a->status === 'confirmed' ? 'is-green' : 'is-blue' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg></i><div><strong>{{ $clientName($a) }}</strong><small>{{ $a->appointment_date->locale('ar')->translatedFormat('d M') }} • {{ $nutritionistName($a) }}</small></div><time>{{ $fmtTime($a->start_time) }}</time></button>
                    @empty<div class="wg-nut-side-empty">لا توجد مواعيد قادمة.</div>@endforelse
                </div>
            </article>

            <article class="wg-nut-card wg-nut-quick-actions">
                <div class="wg-nut-card-head"><h3>إجراءات الموعد</h3>@if($selectedAppointment)<span>#{{ $selectedAppointment->id }}</span>@endif</div>
                @if($selectedAppointment)
                    <div class="wg-nut-selected-summary"><strong>{{ $clientName($selectedAppointment) }}</strong><span>{{ $fmtTime($selectedAppointment->start_time) }} • {{ $selectedAppointment->appointment_date->format('Y-m-d') }}</span></div>
                    <div class="wg-nut-action-grid">
                        @if($canManageAppointments && $selectedAppointment->status === 'booked')<button class="is-primary" type="button" wire:click="confirm({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>تأكيد الموعد</button>@endif
                        @if($canManagePayments && $selectedAppointment->payment_status === 'unpaid' && $selectedAppointment->status !== 'cancelled')<button type="button" x-on:click="paymentOpen = true; $wire.openPayment({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5"/></svg>استلام الدفع</button>@endif
                        @if($canManageAppointments && $selectedAppointment->payment_status === 'paid' && !in_array($selectedAppointment->status,['completed','cancelled']))<button type="button" wire:click="complete({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12h4l2-6 4 12 2-6h4"/></svg>إكمال الجلسة</button>@endif
                        @if($canManageAppointments && !in_array($selectedAppointment->status,['completed','cancelled','no_show']))<button type="button" wire:click="markNoShow({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 8l5 5M22 8l-5 5"/></svg>تسجيل لم يحضر</button>@endif
                        @if($canRecordMeasurements && in_array($selectedAppointment->status,['confirmed','completed']))<button type="button" wire:click="gotoMeasurements({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h16M7 16l3-3 3 2 4-6"/></svg>تسجيل القياسات</button>@endif
                        @if($canReversePayments && $selectedAppointment->payment_status === 'paid' && $selectedAppointment->status !== 'completed')<button class="is-warning" type="button" wire:click="openReversePayment({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>عكس الدفعة</button>@endif
                        @if($canManageAppointments && $selectedAppointment->payment_status === 'unpaid' && !in_array($selectedAppointment->status,['completed','cancelled','no_show']))<button class="is-danger" type="button" wire:click="openCancel({{ $selectedAppointment->id }})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>إلغاء الموعد</button>@endif
                    </div>
                @else
                    <div class="wg-nut-select-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg><strong>اختر موعداً من الجدول</strong><span>ستظهر هنا الإجراءات المتاحة حسب حالة الموعد والدفع.</span></div>
                @endif
            </article>
        </aside>
    </section>

    <section class="wg-nut-clinic-directory">
        <article class="wg-nut-card wg-nut-clients-card">
            <div class="wg-nut-directory-head"><div><span>قاعدة العملاء</span><h2>عملاء التغذية الخاصون</h2><p>بيانات الزوار غير المشتركين وعدد مواعيدهم محفوظة في مكان واحد.</p></div>@if($canCreateClients)<button type="button" x-on:click="clientOpen = true; $wire.openClient()">إضافة عميل خاص</button>@endif</div>
            <div class="wg-nut-client-directory-table">
                <table><thead><tr><th>العميل</th><th>الهاتف</th><th>الجنس</th><th>المواعيد</th><th>آخر موعد</th></tr></thead><tbody>
                @forelse($clinicClients as $client)
                    <tr><td><span class="wg-nut-directory-client"><i>{{ mb_substr($client->full_name,0,1) }}</i><strong>{{ $client->full_name }}</strong></span></td><td dir="ltr">{{ $client->phone }}</td><td>{{ $client->gender === 'female' ? 'أنثى' : 'ذكر' }}</td><td>{{ number_format($client->appointments_count) }}</td><td>{{ $client->appointments_max_appointment_date ? \Carbon\CarbonImmutable::parse($client->appointments_max_appointment_date)->format('Y-m-d') : '—' }}</td></tr>
                @empty<tr><td colspan="5" class="wg-nut-directory-empty">لا يوجد عملاء خاصون بعد.</td></tr>@endforelse
                </tbody></table>
            </div>
        </article>

        <article class="wg-nut-card wg-nut-team-card">
            <div class="wg-nut-directory-head"><div><span>طاقم العيادة</span><h2>اختصاصيو التغذية</h2><p>الحسابات النشطة وفترات عمل اليوم.</p></div>@if(auth()->user()->role === 'owner')<a href="{{ route('staff.index', ['new' => 1, 'role' => 'nutritionist']) }}" wire:navigate>إضافة اختصاصي</a>@endif</div>
            <div class="wg-nut-team-list">
                @forelse($nutritionists as $n)
                    @php $specialistSchedules = $todaySchedules->get($n->id, collect()); @endphp
                    <div><i>{{ mb_substr($n->name ?: $n->username,0,1) }}</i><span><strong>{{ $n->name ?: $n->username }}</strong><small>@if($specialistSchedules->isNotEmpty()){{ $specialistSchedules->map(fn($s) => substr((string)$s->start_time,0,5).' - '.substr((string)$s->end_time,0,5))->join('، ') }}@elseلا توجد فترة عمل اليوم@endif</small></span>@if($canManageSchedules)<button type="button" x-on:click="scheduleOpen = true; $wire.openSchedule({{ $n->id }})">الجدول</button>@endif</div>
                @empty<div class="wg-nut-directory-empty">أضف اختصاصي تغذية لبدء استقبال الحجوزات.</div>@endforelse
            </div>
        </article>
    </section>

    <section class="wg-nut-measurements-preview wg-nut-card">
        <div class="wg-nut-measurement-head"><div><span>متابعة الجسم</span><h2>آخر قياسات مسجلة</h2>@if($latestMeasurement)<p>{{ $latestMeasurement->member?->full_name ?? $latestMeasurement->nutritionClient?->full_name }} • {{ $latestMeasurement->measured_at->timezone('Asia/Aden')->format('Y-m-d h:i A') }}</p>@else<p>لم يتم تسجيل قياسات حتى الآن.</p>@endif</div><div class="wg-nut-measurement-actions"><a href="{{ route('nutrition.measurements') }}" wire:navigate>عرض سجل القياسات</a>@if($canRecordMeasurements)<button type="button" wire:click="gotoMeasurements()">تسجيل قياس جديد</button>@endif</div></div>
        <div class="wg-nut-measure-grid">@foreach($measurementCardMeta as $code => [$label,$unit,$icon])@php $val = $latestMeasurementValues[$code] ?? null; @endphp<article><i>{{ $icon }}</i><div><span>{{ $label }}</span><strong>{{ $val ? rtrim(rtrim(number_format((float)$val->value,3,'.',''),'0'),'.') : '—' }} @if($val)<small>{{ $val->type->unit }}</small>@endif</strong></div></article>@endforeach<article><i>％</i><div><span>مؤشر كتلة الجسم</span><strong>{{ $latestMeasurement?->bmi ?? '—' }}</strong></div></article></div>
    </section>

    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="bookingOpen" x-transition.opacity x-on:click.self="bookingOpen = false; $wire.closeBooking()" x-on:keydown.escape.window="if (bookingOpen) { bookingOpen = false; $wire.closeBooking() }">
        <form class="fin-modal large wg-nut-fin-modal wg-nut-booking-modal" wire:submit="{{ $showEditModal ? 'updateAppointment' : 'book' }}" x-show="bookingOpen" x-transition.scale.origin.center>
            <div class="fin-modal-head"><div><div class="fin-modal-title">{{ $showEditModal ? 'تعديل موعد التغذية' : 'حجز موعد تغذية جديد' }}</div><div class="fin-modal-sub">اختر العميل ونوع الخدمة والوقت؛ النظام يمنع الحجز المكرر والتعارض تلقائياً.</div></div><button type="button" class="fin-close" x-on:click="bookingOpen = false; $wire.closeBooking()">×</button></div>
            <div class="wg-nut-modal-loading" wire:loading.flex wire:target="openBooking,openEdit"><span></span><strong>جارٍ تجهيز بيانات الحجز والأوقات المتاحة...</strong></div>
            @if($errors->any() && ($showBookingModal || $showEditModal))<div class="fin-errors">@foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach</div>@endif
            <div class="fin-form-grid wg-nut-booking-grid">
                <div class="fin-form-group full wg-nut-option-builder"><label class="fin-label">نوع العميل <span class="fin-required">*</span></label><div class="wg-nut-choice-grid two"><button type="button" x-bind:class="{ 'active': clientType === 'member' }" x-on:click="clientType = 'member'; $wire.$set('client_type','member')"><strong>عضو في النادي</strong><span>اختيار من الأعضاء النشطين</span></button><button type="button" x-bind:class="{ 'active': clientType === 'external' }" x-on:click="clientType = 'external'; $wire.$set('client_type','external')"><strong>عميل خاص</strong><span>زائر غير مشترك في النادي</span></button></div></div>
                <div class="fin-form-group full" x-show="clientType === 'member'" x-cloak><label class="fin-label">العضو <span class="fin-required">*</span></label><select class="fin-select" wire:model="member_id"><option value="">اختر العضو</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->membership_code }}</option>@endforeach</select></div>
                <div class="fin-form-group full" x-show="clientType === 'external'" x-cloak><div class="wg-nut-field-heading"><label class="fin-label">عميل التغذية <span class="fin-required">*</span></label>@if($canCreateClients)<button type="button" x-on:click="clientOpen = true; $wire.openClient()">+ عميل جديد</button>@endif</div><select class="fin-select" wire:model="nutrition_client_id"><option value="">اختر العميل</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->phone }}</option>@endforeach</select></div>
                <div class="fin-form-group full wg-nut-option-builder"><label class="fin-label">نوع خدمة التغذية <span class="fin-required">*</span></label><div class="wg-nut-service-options">@foreach($serviceTypeMeta as $code => [$label,$help])<button type="button" x-bind:class="{ 'active': serviceType === @js($code) }" x-on:click="serviceType = @js($code); $wire.$set('service_type', @js($code), false)"><strong>{{ $label }}</strong><span>{{ $help }}</span></button>@endforeach</div></div>
                <div class="fin-form-group"><label class="fin-label">اختصاصي التغذية <span class="fin-required">*</span></label><select class="fin-select" wire:model.live="nutritionist_id" {{ auth()->user()->role === 'nutritionist' ? 'disabled' : '' }}><option value="">اختر الأخصائي</option>@foreach($nutritionists as $n)<option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>@endforeach</select></div>
                <div class="fin-form-group"><label class="fin-label">تاريخ الموعد <span class="fin-required">*</span></label><input class="fin-field" wire:model.live="appointment_date" type="date" min="{{ now('Asia/Aden')->toDateString() }}" required></div>
                <div class="fin-form-group full wg-nut-option-builder"><label class="fin-label">طريقة الزيارة <span class="fin-required">*</span></label><div class="wg-nut-choice-grid two"><button type="button" x-bind:class="{ 'active': visitType === 'in_person' }" x-on:click="visitType = 'in_person'; $wire.$set('visit_type','in_person',false)"><strong>حضوري في العيادة</strong><span>الجلسة داخل النادي</span></button><button type="button" x-bind:class="{ 'active': visitType === 'remote' }" x-on:click="visitType = 'remote'; $wire.$set('visit_type','remote',false)"><strong>متابعة عن بُعد</strong><span>هاتف أو اتصال مرئي</span></button></div></div>
                <div class="fin-form-group full wg-nut-option-builder"><label class="fin-label">مدة الجلسة <span class="fin-required">*</span></label><div class="wg-nut-duration-options">@foreach([30,45,60,90] as $minutes)<button type="button" x-bind:class="{ 'active': Number(duration) === {{ $minutes }} }" x-on:click="duration = {{ $minutes }}; $wire.$set('duration_minutes', {{ $minutes }})">{{ $minutes }} دقيقة</button>@endforeach</div></div>
                <div class="fin-form-group"><label class="fin-label">وقت البداية <span class="fin-required">*</span></label><input class="fin-field" wire:model="start_time" type="time" required></div>
                <div class="fin-form-group"><label class="fin-label">السعر <span class="fin-required">*</span></label><input class="fin-field" wire:model="price" type="number" min="0" step="0.01" placeholder="0" required></div>
                <div class="fin-form-group"><label class="fin-label">العملة <span class="fin-required">*</span></label><select class="fin-select" wire:model="currency" {{ $showEditModal && $selectedAppointment?->payment_status === 'paid' ? 'disabled' : '' }}><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select></div>
                <div class="fin-form-group full"><div class="wg-nut-slots-fin"><div><strong>الأوقات المتاحة</strong><span>تتحدث حسب الأخصائي والتاريخ والمدة</span></div><div wire:loading.class="is-loading" wire:target="nutritionist_id,appointment_date,duration_minutes">@forelse($availableSlots as $slot)<button type="button" wire:click="chooseSlot('{{ $slot }}')" class="{{ $start_time === $slot ? 'is-selected' : '' }}">{{ $fmtTime($slot) }}</button>@empty<span class="wg-nut-no-slots">اختر الأخصائي والتاريخ، وتأكد من وجود جدول عمل.</span>@endforelse</div></div></div>
                <div class="fin-form-group full"><label class="fin-label">ملاحظات الزيارة (اختياري)</label><textarea class="fin-field fin-textarea" wire:model="notes" placeholder="هدف العميل، الحساسية، الحالة الصحية أو أي ملاحظة مهمة..."></textarea></div>
            </div>
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="book,updateAppointment"><span wire:loading.remove wire:target="book,updateAppointment">{{ $showEditModal ? 'حفظ التعديلات' : 'حفظ وتأكيد الحجز' }}</span><span wire:loading wire:target="book,updateAppointment">جاري حفظ الموعد...</span></button><button class="fin-btn" type="button" x-on:click="bookingOpen = false; $wire.closeBooking()">إلغاء</button></div>
        </form>
    </div>

    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="clientOpen" x-transition.opacity x-on:click.self="clientOpen = false; $wire.closeClient()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="createClient">
            <div class="fin-modal-head"><div><div class="fin-modal-title">إضافة عميل تغذية خاص</div><div class="fin-modal-sub">للزوار غير المشتركين؛ سيظهر العميل مباشرة ضمن الحجز ودليل العيادة.</div></div><button type="button" class="fin-close" x-on:click="clientOpen = false; $wire.closeClient()">×</button></div>
            @if($errors->any() && $showClientModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-grid"><div class="fin-form-group"><label class="fin-label">الاسم الكامل <span class="fin-required">*</span></label><input class="fin-field" wire:model="new_client_name" required></div><div class="fin-form-group"><label class="fin-label">رقم الهاتف <span class="fin-required">*</span></label><input class="fin-field" wire:model="new_client_phone" dir="ltr" required></div><div class="fin-form-group full"><label class="fin-label">الجنس <span class="fin-required">*</span></label><select class="fin-select" wire:model="new_client_gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></div><div class="fin-form-group full"><label class="fin-label">ملاحظات صحية أو تعريفية (اختياري)</label><textarea class="fin-field fin-textarea" wire:model="new_client_notes" placeholder="حساسية، حالة صحية، مصدر التعرف على العيادة..."></textarea></div></div>
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="createClient"><span wire:loading.remove wire:target="createClient">حفظ العميل والمتابعة للحجز</span><span wire:loading wire:target="createClient">جاري حفظ العميل...</span></button><button class="fin-btn" type="button" x-on:click="clientOpen = false; $wire.closeClient()">إلغاء</button></div>
        </form>
    </div>

    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="scheduleOpen" x-transition.opacity x-on:click.self="scheduleOpen = false; $wire.closeSchedule()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="addSchedule">
            <div class="fin-modal-head"><div><div class="fin-modal-title">جدول اختصاصي التغذية</div><div class="fin-modal-sub">حدد ساعات العمل؛ لن يقبل النظام أي حجز خارجها.</div></div><button type="button" class="fin-close" x-on:click="scheduleOpen = false; $wire.closeSchedule()">×</button></div>
            @if($errors->any() && $showScheduleModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-grid"><div class="fin-form-group full"><label class="fin-label">الأخصائي <span class="fin-required">*</span></label><select class="fin-select" wire:model="nutritionist_id" {{ auth()->user()->role === 'nutritionist' ? 'disabled' : '' }}><option value="">اختر الأخصائي</option>@foreach($nutritionists as $n)<option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>@endforeach</select></div><div class="fin-form-group full"><label class="fin-label">اليوم <span class="fin-required">*</span></label><select class="fin-select" wire:model="schedule_day"><option value="0">الأحد</option><option value="1">الاثنين</option><option value="2">الثلاثاء</option><option value="3">الأربعاء</option><option value="4">الخميس</option><option value="5">الجمعة</option><option value="6">السبت</option></select></div><div class="fin-form-group"><label class="fin-label">من <span class="fin-required">*</span></label><input class="fin-field" wire:model="schedule_start" type="time"></div><div class="fin-form-group"><label class="fin-label">إلى <span class="fin-required">*</span></label><input class="fin-field" wire:model="schedule_end" type="time"></div></div>
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="addSchedule"><span wire:loading.remove wire:target="addSchedule">حفظ فترة العمل</span><span wire:loading wire:target="addSchedule">جاري حفظ الجدول...</span></button><button class="fin-btn" type="button" x-on:click="scheduleOpen = false; $wire.closeSchedule()">إلغاء</button></div>
        </form>
    </div>

    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="paymentOpen" x-transition.opacity x-on:click.self="paymentOpen = false; $wire.closePayment()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="pay">
            <div class="fin-modal-head"><div><div class="fin-modal-title">استلام دفعة الموعد</div><div class="fin-modal-sub">@if($selectedAppointment){{ $clientName($selectedAppointment) }} • {{ number_format((float)$selectedAppointment->price, $selectedAppointment->currency === 'SAR' ? 2 : 0) }} {{ $selectedAppointment->currency }}@elseجارٍ تحميل بيانات الموعد...@endif</div></div><button type="button" class="fin-close" x-on:click="paymentOpen = false; $wire.closePayment()">×</button></div>
            <div class="wg-nut-modal-loading" wire:loading.flex wire:target="openPayment"><span></span><strong>جارٍ تحميل قيمة الموعد...</strong></div>
            @if($errors->any() && $showPaymentModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            @if($selectedAppointment)<div class="wg-nut-payment-amount"><span>المبلغ المطلوب</span><strong>{{ number_format((float)$selectedAppointment->price, $selectedAppointment->currency === 'SAR' ? 2 : 0) }} <small>{{ $selectedAppointment->currency }}</small></strong></div>@endif
            <div class="wg-nut-payment-methods"><label class="{{ $payment_method === 'cash' ? 'is-selected' : '' }}"><input wire:model.live="payment_method" type="radio" value="cash"><span>نقدي</span><small>دفع في العيادة</small></label><label class="{{ $payment_method === 'transfer' ? 'is-selected' : '' }}"><input wire:model.live="payment_method" type="radio" value="transfer"><span>تحويل</span><small>حوالة أو تحويل مصرفي</small></label></div>
            @if($payment_method === 'transfer')
                <div class="fin-form-grid">
                    <div class="fin-form-group"><label class="fin-label">جهة التحويل <span class="fin-required">*</span></label><select class="fin-select" wire:model="transfer_service"><option value="العمقي">العمقي</option><option value="الكريمي">الكريمي</option><option value="البسيري">البسيري</option></select></div>
                    <div class="fin-form-group"><label class="fin-label">مرجع التحويل</label><input class="fin-field" wire:model="transfer_reference" placeholder="رقم الحوالة أو المرجع"></div>
                </div>
                <label class="wg-nut-upload"><input wire:model="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg><span>إرفاق سند التحويل</span><small>JPG, PNG, WEBP, PDF حتى 5MB — يُطلب حسب سياسة الدفع</small></label>
            @endif
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="pay,payment_proof"><span wire:loading.remove wire:target="pay">تأكيد استلام الدفعة</span><span wire:loading wire:target="pay">جاري حفظ الدفعة...</span></button><button class="fin-btn" type="button" x-on:click="paymentOpen = false; $wire.closePayment()">إلغاء</button></div>
        </form>
    </div>

    @if($showCancelModal)
        <div class="wg-nut-modal-backdrop" wire:click.self="$set('showCancelModal',false)"><section class="wg-nut-modal wg-nut-modal-confirm"><button class="wg-nut-modal-x" type="button" wire:click="$set('showCancelModal',false)">×</button><header><i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg></i><div><h2>إلغاء الموعد</h2><p>سيبقى الموعد محفوظاً في السجل مع سبب الإلغاء.</p></div></header><form wire:submit="cancel"><label class="wg-nut-full-label"><span>سبب الإلغاء *</span><textarea wire:model="cancellation_reason" rows="4" placeholder="اكتب سبب الإلغاء..."></textarea></label><footer><button type="button" class="wg-nut-btn" wire:click="$set('showCancelModal',false)">رجوع</button><button class="wg-nut-btn wg-nut-btn-danger" type="submit">تأكيد إلغاء الموعد</button></footer></form></section></div>
    @endif

    @if($showReverseModal)
        <div class="wg-nut-modal-backdrop" wire:click.self="$set('showReverseModal',false)"><section class="wg-nut-modal wg-nut-modal-confirm"><button class="wg-nut-modal-x" type="button" wire:click="$set('showReverseModal',false)">×</button><header><i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg></i><div><h2>عكس دفعة الموعد</h2><p>بعد العكس يعود الموعد إلى غير مدفوع ويمكن إلغاؤه عند الحاجة.</p></div></header><form wire:submit="reversePayment"><label class="wg-nut-full-label"><span>سبب عكس الدفعة *</span><textarea wire:model="reversal_reason" rows="4" placeholder="سبب العكس..."></textarea></label><footer><button type="button" class="wg-nut-btn" wire:click="$set('showReverseModal',false)">رجوع</button><button class="wg-nut-btn wg-nut-btn-warning" type="submit">تأكيد عكس الدفعة</button></footer></form></section></div>
    @endif
</div>
