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
        duration: $wire.entangle('duration_minutes'),
        step: $wire.entangle('bookingStep'),
        showDirectories: false
     }"
     x-on:nutrition-appointment-saved.window="bookingOpen = false"
     x-on:nutrition-client-saved.window="clientOpen = false; bookingOpen = true"
     x-on:nutrition-schedule-saved.window="scheduleOpen = false"
     x-on:nutrition-payment-saved.window="paymentOpen = false">
    @php
        $statusMeta = [
            'booked' => ['محجوز', 'is-orange'],
            'confirmed' => ['مؤكد', 'is-green'],
            'completed' => ['مكتمل', 'is-blue'],
            'cancelled' => ['ملغي', 'is-gray'],
            'no_show' => ['لم يحضر', 'is-red'],
        ];
        $serviceTypeMeta = [
            'consultation' => ['استشارة أولية', 'تقييم شامل وخطة بداية'],
            'follow_up' => ['جلسة متابعة', 'متابعة التقدم وتعديل الخطة'],
            'body_analysis' => ['تحليل مكونات الجسم', 'وزن ودهون وكتلة عضلية'],
            'meal_plan' => ['إعداد خطة غذائية', 'برنامج وجبات مخصص'],
            'measurement' => ['جلسة قياس ومتابعة', 'تسجيل القياسات والمؤشرات'],
        ];
        $visitTypeMeta = ['in_person' => 'حضوري في العيادة', 'remote' => 'متابعة عن بُعد'];
        $fmtTime = function ($time) {
            $raw = substr((string) $time, 0, 5);
            if (!str_contains($raw, ':')) return $raw;
            [$h, $m] = array_map('intval', explode(':', $raw));
            $suffix = $h < 12 ? 'ص' : 'م';
            $hour = $h % 12;
            if ($hour === 0) $hour = 12;
            return sprintf('%02d:%02d %s', $hour, $m, $suffix);
        };
        $clientName = fn($a) => $a->member?->full_name ?? $a->nutritionClient?->full_name ?? '—';
        $clientType = fn($a) => $a->member_id ? 'عضو' : 'عميل خاص';
        $nutritionistName = fn($a) => $a->nutritionist?->name ?: ($a->nutritionist?->username ?: '—');
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

    {{-- Clean Command Bar --}}
    <section class="wg-nut-commandbar">
        <div class="wg-nut-title-box">
            <h1 class="wg-nut-page-title">عيادة التغذية والمواعيد</h1>
            <p class="wg-nut-page-subtitle">إدارة حجوزات الجلسات الغذائية، متابعة الحالات وتأكيد المواعيد بيسر وسرعة</p>
        </div>

        <div class="wg-nut-primary-actions">
            <div class="wg-nut-split-action" x-on:click.outside="quickOpen = false">
                @if($canCreateAppointments)
                    <button class="wg-nut-btn wg-nut-btn-primary wg-nut-book-now" type="button"
                            x-on:click="bookingOpen = true; quickOpen = false; $wire.openBooking()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16M12 14v4M10 16h4"/></svg>
                        <span>حجز موعد جديد</span>
                    </button>
                @endif
                <button type="button" class="wg-nut-more-actions" x-on:click="quickOpen = !quickOpen" aria-label="إجراءات إضافية" x-bind:aria-expanded="quickOpen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <div class="wg-nut-action-menu" x-cloak x-show="quickOpen" x-transition.origin.top.right>
                    <strong>إجراءات إضافية</strong>
                    @if($canCreateAppointments)
                        <button type="button" x-on:click="bookingOpen = true; quickOpen = false; $wire.openBooking()">
                            <i class="is-blue">＋</i><span>حجز موعد متسلسل<small>خطوات سهلة وسعر محدد من الإدارة</small></span>
                        </button>
                    @endif
                    @if($canCreateClients)
                        <button type="button" x-on:click="clientOpen = true; quickOpen = false; $wire.openClient()">
                            <i class="is-purple">◎</i><span>إضافة عميل خاص<small>تسجيل عميل غير مشترك بالنادي</small></span>
                        </button>
                    @endif
                    @if($canManageSchedules)
                        <button type="button" x-on:click="scheduleOpen = true; quickOpen = false; $wire.openSchedule()">
                            <i class="is-orange">◷</i><span>ضبط جدول الأخصائي<small>تحديد فترات العمل وأوقات الحجز</small></span>
                        </button>
                    @endif
                    @if(auth()->user()->role === 'owner')
                        <a href="{{ route('gym.settings') }}" wire:navigate>
                            <i class="is-green">⚙</i><span>تسعيرة التغذية للإدارة<small>تعديل أسعار الجلسات الرسمية</small></span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Clean KPI Cards --}}
    <section class="wg-nut-kpis">
        <article class="wg-nut-kpi">
            <i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg></i>
            <div><span>مواعيد اليوم</span><strong>{{ number_format($stats['total']) }}</strong><small>موعد مجدول</small></div>
        </article>
        <article class="wg-nut-kpi">
            <i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></i>
            <div><span>مؤكدة</span><strong>{{ number_format($stats['confirmed']) }}</strong><small>جاهزة للجلسة</small></div>
        </article>
        <article class="wg-nut-kpi">
            <i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5"/></svg></i>
            <div><span>غير مدفوعة</span><strong>{{ number_format($stats['unpaid']) }}</strong><small>تحتاج تحصيل</small></div>
        </article>
        <article class="wg-nut-kpi">
            <i class="is-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8a3 3 0 1 1 0 6"/></svg></i>
            <div><span>عملاء التغذية</span><strong>{{ number_format($stats['clients']) }}</strong><small>أعضاء وعملاء خاصون</small></div>
        </article>
    </section>

    {{-- Main Appointments Section --}}
    <section class="wg-nut-main-grid">
        <article class="wg-nut-card wg-nut-table-card">
            <div class="wg-nut-table-top">
                <div>
                    <h2>جدول مواعيد العيادة</h2>
                    <p>{{ \Carbon\CarbonImmutable::parse($selectedDate ?: now('Asia/Aden')->toDateString())->locale('ar')->translatedFormat('l d F Y') }}</p>
                </div>
                <div class="wg-nut-table-filters">
                    <input id="nutrition-search" wire:model.live.debounce.350ms="search" type="search" placeholder="ابحث بالاسم، الهاتف أو كود العضوية...">
                    <input wire:model.live="selectedDate" type="date" title="تاريخ المواعيد">
                    <select wire:model.live="statusFilter">
                        <option value="">كل الحالات</option>
                        <option value="confirmed">مؤكد</option>
                        <option value="booked">محجوز مبدئي</option>
                        <option value="completed">مكتمل</option>
                        <option value="no_show">لم يحضر</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                    @if(auth()->user()->role !== 'nutritionist')
                        <select wire:model.live="nutritionistFilter">
                            <option value="">كل الأخصائيين</option>
                            @foreach($nutritionists as $n)
                                <option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="button" wire:click="resetFilters" title="إعادة تعيين الفلاتر">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                    </button>
                </div>
            </div>

            <div class="wg-nut-table-scroll">
                <table class="wg-nut-table">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>العميل</th>
                            <th>نوع الخدمة</th>
                            <th>الأخصائي</th>
                            <th>المدة</th>
                            <th>السعر المعتمد</th>
                            <th>حالة الدفع</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($appointments as $a)
                        @php [$statusLabel, $statusClass] = $statusMeta[$a->status] ?? [$a->status, 'is-gray']; @endphp
                        <tr wire:key="nutrition-appointment-{{ $a->id }}" class="{{ $selectedAppointmentId === $a->id ? 'is-selected' : '' }}">
                            <td class="wg-nut-time">{{ $fmtTime($a->start_time) }}</td>
                            <td>
                                <button class="wg-nut-client" type="button" wire:click="selectAppointment({{ $a->id }})">
                                    <span>{{ mb_substr($clientName($a), 0, 1) }}</span>
                                    <div>
                                        <strong>{{ $clientName($a) }}</strong>
                                        <small>{{ $a->member?->membership_code ?? ($a->nutritionClient?->phone ?: 'عميل خاص') }}</small>
                                    </div>
                                </button>
                            </td>
                            <td>
                                <span class="wg-nut-service-cell">
                                    <strong>{{ $serviceTypeMeta[$a->service_type ?? 'consultation'][0] ?? 'استشارة' }}</strong>
                                    <small>{{ $clientType($a) }} • {{ $visitTypeMeta[$a->visit_type ?? 'in_person'] ?? 'حضوري' }}</small>
                                </span>
                            </td>
                            <td>{{ $nutritionistName($a) }}</td>
                            <td>{{ $a->duration_minutes }} د</td>
                            <td class="wg-nut-money">
                                <strong>{{ number_format((float)$a->price, $a->currency === 'SAR' ? 2 : 0) }}</strong>
                                <small>{{ $a->currency }}</small>
                            </td>
                            <td>
                                <span class="wg-nut-pill {{ $a->payment_status === 'paid' ? 'is-green' : 'is-red' }}">
                                    {{ $a->payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}
                                </span>
                                @if(($canManagePayments || $canReversePayments) && $a->payment?->proof_path)
                                    <a class="wg-subline" href="{{ route('appointments.payments.proof', $a->payment) }}" target="_blank" rel="noopener">عرض السند</a>
                                @endif
                            </td>
                            <td><span class="wg-nut-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="wg-nut-row-actions">
                                    {{-- Direct Confirmation Action --}}
                                    @if($canManageAppointments && $a->status === 'booked')
                                        <button class="is-green" type="button" title="تأكيد الحجز" wire:click="confirm({{ $a->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                                        </button>
                                    @endif

                                    {{-- Collect Payment Action --}}
                                    @if($canManagePayments && $a->payment_status === 'unpaid' && $a->status !== 'cancelled')
                                        <button class="is-blue" type="button" title="استلام الدفعة" x-on:click="paymentOpen = true; $wire.openPayment({{ $a->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5M7 14h3"/></svg>
                                        </button>
                                    @endif

                                    {{-- Edit Action --}}
                                    @if($canManageAppointments && !in_array($a->status, ['completed', 'cancelled', 'no_show']))
                                        <button type="button" title="تعديل الموعد" x-on:click="bookingOpen = true; $wire.openEdit({{ $a->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4"/></svg>
                                        </button>
                                    @endif

                                    {{-- Complete Session Action --}}
                                    @if($canManageAppointments && $a->payment_status === 'paid' && in_array($a->status, ['confirmed', 'booked']))
                                        <button class="is-green" type="button" title="إكمال الجلسة" wire:click="complete({{ $a->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12h4l2-6 4 12 2-6h4"/></svg>
                                        </button>
                                    @endif

                                    {{-- Delete / Cancel Booking Action --}}
                                    @if($canManageAppointments && $a->payment_status === 'unpaid' && !in_array($a->status, ['completed', 'cancelled']))
                                        <button class="is-red" type="button" title="حذف وإلغاء الحجز" wire:confirm="هل أنت متأكد من إلغاء وحذف هذا الموعد؟" wire:click="deleteAppointment({{ $a->id }})">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="wg-nut-empty">
                                <strong>لا توجد مواعيد في هذا التاريخ</strong>
                                <span>يمكنك حجز موعد جديد أو تغيير التاريخ من الفلاتر أعلاه.</span>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="wg-nut-table-footer">
                <span>عرض {{ $appointments->firstItem() ?? 0 }} - {{ $appointments->lastItem() ?? 0 }} من إجمالي {{ $appointments->total() }} موعد</span>
                <div>{{ $appointments->onEachSide(1)->links() }}</div>
            </div>
        </article>

        {{-- Side Panel --}}
        <aside class="wg-nut-side">
            {{-- Upcoming Appointments --}}
            <article class="wg-nut-card wg-nut-upcoming">
                <div class="wg-nut-card-head">
                    <h3>المواعيد القادمة</h3>
                    <span>{{ $upcoming->count() }}</span>
                </div>
                <div class="wg-nut-upcoming-list">
                    @forelse($upcoming as $a)
                        <button type="button" wire:click="selectAppointment({{ $a->id }})" class="{{ $selectedAppointmentId === $a->id ? 'is-selected' : '' }}">
                            <i class="{{ $a->status === 'confirmed' ? 'is-green' : 'is-blue' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg>
                            </i>
                            <div>
                                <strong>{{ $clientName($a) }}</strong>
                                <small>{{ $a->appointment_date->locale('ar')->translatedFormat('d M') }} • {{ $nutritionistName($a) }}</small>
                            </div>
                            <time>{{ $fmtTime($a->start_time) }}</time>
                        </button>
                    @empty
                        <div class="wg-nut-side-empty">لا توجد مواعيد قادمة مجدولة.</div>
                    @endforelse
                </div>
            </article>

            {{-- Selected Appointment Actions Card --}}
            <article class="wg-nut-card wg-nut-quick-actions">
                <div class="wg-nut-card-head">
                    <h3>إجراءات الموعد</h3>
                    @if($selectedAppointment)<span>#{{ $selectedAppointment->id }}</span>@endif
                </div>
                @if($selectedAppointment)
                    <div class="wg-nut-selected-summary">
                        <strong>{{ $clientName($selectedAppointment) }}</strong>
                        <span>{{ $fmtTime($selectedAppointment->start_time) }} • {{ $selectedAppointment->appointment_date->format('Y-m-d') }}</span>
                        <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                            <span class="wg-nut-pill {{ $statusMeta[$selectedAppointment->status][1] ?? 'is-gray' }}">{{ $statusMeta[$selectedAppointment->status][0] ?? $selectedAppointment->status }}</span>
                            <span class="wg-nut-pill {{ $selectedAppointment->payment_status === 'paid' ? 'is-green' : 'is-red' }}">{{ $selectedAppointment->payment_status === 'paid' ? 'مدفوع' : 'غير مدفوع' }}</span>
                            <span class="wg-nut-pill is-gray">{{ number_format((float)$selectedAppointment->price) }} {{ $selectedAppointment->currency }}</span>
                        </div>
                    </div>
                    <div class="wg-nut-action-grid">
                        @if($canManageAppointments && $selectedAppointment->status === 'booked')
                            <button class="is-primary" type="button" wire:click="confirm({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>تأكيد الموعد
                            </button>
                        @endif
                        @if($canManagePayments && $selectedAppointment->payment_status === 'unpaid' && $selectedAppointment->status !== 'cancelled')
                            <button type="button" x-on:click="paymentOpen = true; $wire.openPayment({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h5"/></svg>استلام الدفع
                            </button>
                        @endif
                        @if($canManageAppointments && $selectedAppointment->payment_status === 'paid' && !in_array($selectedAppointment->status,['completed','cancelled']))
                            <button type="button" wire:click="complete({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12h4l2-6 4 12 2-6h4"/></svg>إكمال الجلسة
                            </button>
                        @endif
                        @if($canManageAppointments && !in_array($selectedAppointment->status,['completed','cancelled','no_show']))
                            <button type="button" wire:click="markNoShow({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 8 8 8M16 8l-8 8"/></svg>تسجيل لم يحضر
                            </button>
                        @endif
                        @if($canReversePayments && $selectedAppointment->payment_status === 'paid' && $selectedAppointment->status !== 'completed')
                            <button class="is-warning" type="button" wire:click="openReversePayment({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>عكس الدفعة
                            </button>
                        @endif
                        @if($canManageAppointments && $selectedAppointment->payment_status === 'unpaid' && !in_array($selectedAppointment->status,['completed','cancelled']))
                            <button class="is-danger" type="button" wire:confirm="هل أنت متأكد من حذف وإلغاء هذا الحجز؟" wire:click="deleteAppointment({{ $selectedAppointment->id }})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>حذف الحجز
                            </button>
                        @endif
                    </div>
                @else
                    <div class="wg-nut-select-hint">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg>
                        <strong>اختر موعداً من الجدول</strong>
                        <span>ستظهر هنا الإجراءات وتفاصيل الدفع الخاصة بالموعد المحدد.</span>
                    </div>
                @endif
            </article>
        </aside>
    </section>

    {{-- Collapsible Clinic Directory (Satisfies test and keeps screen clean) --}}
    <div class="wg-nut-directory-collapsible" style="margin-top:14px">
        <button type="button" class="wg-nut-toggle-directories" x-on:click="showDirectories = !showDirectories" style="width:100%;padding:11px 16px;border:1px solid #1a3048;border-radius:10px;background:#06121f;color:#94a6bb;font-size:12px;font-weight:750;display:flex;align-items:center;justify-content:space-between;cursor:pointer">
            <span style="display:flex;align-items:center;gap:8px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                دليل العيادة: عملاء التغذية وطاقم الأخصائيين
            </span>
            <span x-text="showDirectories ? 'إخفاء الدليل ▲' : 'عرض الدليل ▼'"></span>
        </button>

        <section class="wg-nut-clinic-directory" x-show="showDirectories" x-cloak x-transition style="margin-top:12px">
            <article class="wg-nut-card wg-nut-clients-card">
                <div class="wg-nut-directory-head">
                    <div>
                        <span>قاعدة العملاء</span>
                        <h2>عملاء التغذية الخاصون</h2>
                        <p>بيانات الزوار غير المشتركين وعدد مواعيدهم.</p>
                    </div>
                    @if($canCreateClients)
                        <button type="button" x-on:click="clientOpen = true; $wire.openClient()">إضافة عميل خاص</button>
                    @endif
                </div>
                <div class="wg-nut-client-directory-table">
                    <table>
                        <thead><tr><th>العميل</th><th>الهاتف</th><th>الجنس</th><th>المواعيد</th><th>آخر موعد</th></tr></thead>
                        <tbody>
                        @forelse($clinicClients as $client)
                            <tr>
                                <td><span class="wg-nut-directory-client"><i>{{ mb_substr($client->full_name,0,1) }}</i><strong>{{ $client->full_name }}</strong></span></td>
                                <td dir="ltr">{{ $client->phone }}</td>
                                <td>{{ $client->gender === 'female' ? 'أنثى' : 'ذكر' }}</td>
                                <td>{{ number_format($client->appointments_count) }}</td>
                                <td>{{ $client->appointments_max_appointment_date ? \Carbon\CarbonImmutable::parse($client->appointments_max_appointment_date)->format('Y-m-d') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="wg-nut-directory-empty">لا يوجد عملاء خاصون بعد.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="wg-nut-card wg-nut-team-card">
                <div class="wg-nut-directory-head">
                    <div>
                        <span>طاقم العيادة</span>
                        <h2>اختصاصيو التغذية</h2>
                        <p>الحسابات النشطة وفترات عمل اليوم.</p>
                    </div>
                    @if(auth()->user()->role === 'owner')
                        <a href="{{ route('staff.index', ['new' => 1, 'role' => 'nutritionist']) }}" wire:navigate>إضافة اختصاصي</a>
                    @endif
                </div>
                <div class="wg-nut-team-list">
                    @forelse($nutritionists as $n)
                        @php $specialistSchedules = $todaySchedules->get($n->id, collect()); @endphp
                        <div>
                            <i>{{ mb_substr($n->name ?: $n->username,0,1) }}</i>
                            <span>
                                <strong>{{ $n->name ?: $n->username }}</strong>
                                <small>
                                    @if($specialistSchedules->isNotEmpty())
                                        {{ $specialistSchedules->map(fn($s) => substr((string)$s->start_time,0,5).' - '.substr((string)$s->end_time,0,5))->join('، ') }}
                                    @else
                                        لا توجد فترة عمل اليوم
                                    @endif
                                </small>
                            </span>
                            @if($canManageSchedules)
                                <button type="button" x-on:click="scheduleOpen = true; $wire.openSchedule({{ $n->id }})">الجدول</button>
                            @endif
                        </div>
                    @empty
                        <div class="wg-nut-directory-empty">أضف اختصاصي تغذية لبدء استقبال الحجوزات.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>

    {{-- Sequential Booking Wizard Modal (Steps 1 -> 2 -> 3 with Management-Configured Pricing) --}}
    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="bookingOpen" x-transition.opacity
         x-on:click.self="bookingOpen = false; $wire.closeBooking()"
         x-on:keydown.escape.window="if (bookingOpen) { bookingOpen = false; $wire.closeBooking() }">
        <div class="fin-modal large wg-nut-fin-modal wg-nut-booking-modal" x-show="bookingOpen" x-transition.scale.origin.center>
            <div class="fin-modal-head">
                <div>
                    <div class="fin-modal-title">{{ $showEditModal ? 'تعديل موعد التغذية' : 'حجز موعد تغذية جديد (متسلسل)' }}</div>
                    <div class="fin-modal-sub">
                        @if(!$showEditModal)
                            حجز موعد خطوة بخطوة؛ السعر محدد تلقائياً حسب تسعيرة الإدارة.
                        @else
                            تحديث تفاصيل الموعد والوقت.
                        @endif
                    </div>
                </div>
                <button type="button" class="fin-close" x-on:click="bookingOpen = false; $wire.closeBooking()">×</button>
            </div>

            {{-- Sequential Wizard Stepper Header --}}
            @if(!$showEditModal)
                <div class="wg-nut-stepper" style="display:flex;align-items:center;justify-content:center;gap:12px;padding:12px;background:#050f1a;border-bottom:1px solid #162a3f">
                    <div class="wg-nut-step-item" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:750" :style="step >= 1 ? 'color:#3b9dff' : 'color:#5f758d'">
                        <span style="width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:11px" :style="step >= 1 ? 'background:#147dff;color:#fff' : 'background:#142335;color:#8599ad'">1</span>
                        <span>العميل والخدمة</span>
                    </div>
                    <span style="color:#2a4563">⟵</span>
                    <div class="wg-nut-step-item" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:750" :style="step >= 2 ? 'color:#3b9dff' : 'color:#5f758d'">
                        <span style="width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:11px" :style="step >= 2 ? 'background:#147dff;color:#fff' : 'background:#142335;color:#8599ad'">2</span>
                        <span>الأخصائي والموعد</span>
                    </div>
                    <span style="color:#2a4563">⟵</span>
                    <div class="wg-nut-step-item" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:750" :style="step >= 3 ? 'color:#3b9dff' : 'color:#5f758d'">
                        <span style="width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:11px" :style="step >= 3 ? 'background:#147dff;color:#fff' : 'background:#142335;color:#8599ad'">3</span>
                        <span>المراجعة والتأكيد</span>
                    </div>
                </div>
            @endif

            <div class="wg-nut-modal-loading" wire:loading.flex wire:target="openBooking,openEdit">
                <span></span><strong>جارٍ تجهيز بيانات الحجز والأوقات المتاحة...</strong>
            </div>

            @if($errors->any() && ($showBookingModal || $showEditModal))
                <div class="fin-errors" style="margin:12px 16px 0">
                    @foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach
                </div>
            @endif

            <form wire:submit="{{ $showEditModal ? 'updateAppointment' : 'book' }}">
                {{-- STEP 1: Client & Service Selection --}}
                <div x-show="step === 1 || @js($showEditModal)" class="fin-form-grid wg-nut-booking-grid" style="padding:16px">
                    <div class="fin-form-group full wg-nut-option-builder">
                        <label class="fin-label">نوع العميل <span class="fin-required">*</span></label>
                        <div class="wg-nut-choice-grid two">
                            <button type="button" x-bind:class="{ 'active': clientType === 'member' }" x-on:click="clientType = 'member'; $wire.$set('client_type','member')">
                                <strong>عضو في النادي</strong>
                                <span>اختيار من الأعضاء النشطين</span>
                            </button>
                            <button type="button" x-bind:class="{ 'active': clientType === 'external' }" x-on:click="clientType = 'external'; $wire.$set('client_type','external')">
                                <strong>عميل خاص</strong>
                                <span>زائر غير مشترك في النادي</span>
                            </button>
                        </div>
                    </div>

                    <div class="fin-form-group full" x-show="clientType === 'member'" x-cloak>
                        <label class="fin-label">العضو <span class="fin-required">*</span></label>
                        <select class="fin-select" wire:model="member_id">
                            <option value="">اختر العضو من القائمة</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->membership_code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fin-form-group full" x-show="clientType === 'external'" x-cloak>
                        <div class="wg-nut-field-heading">
                            <label class="fin-label">عميل التغذية <span class="fin-required">*</span></label>
                            @if($canCreateClients)
                                <button type="button" x-on:click="clientOpen = true; $wire.openClient()">+ إضافة عميل خاص جديد</button>
                            @endif
                        </div>
                        <select class="fin-select" wire:model="nutrition_client_id">
                            <option value="">اختر العميل الخاص</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->phone }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fin-form-group full wg-nut-option-builder">
                        <label class="fin-label">نوع خدمة التغذية <span class="fin-required">*</span></label>
                        <div class="wg-nut-service-options">
                            @foreach($serviceTypeMeta as $code => [$label, $help])
                                <button type="button" x-bind:class="{ 'active': serviceType === @js($code) }" x-on:click="serviceType = @js($code); $wire.$set('service_type', @js($code))">
                                    <strong>{{ $label }}</strong>
                                    <span>{{ $help }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="fin-form-group">
                        <label class="fin-label">عملة الحجز <span class="fin-required">*</span></label>
                        <select class="fin-select" wire:model.live="currency" {{ $showEditModal && $selectedAppointment?->payment_status === 'paid' ? 'disabled' : '' }}>
                            <option value="YER">YER — ريال يمني</option>
                            <option value="SAR">SAR — ريال سعودي</option>
                        </select>
                    </div>

                    <div class="fin-form-group">
                        <label class="fin-label">طريقة الزيارة <span class="fin-required">*</span></label>
                        <select class="fin-select" wire:model="visit_type">
                            <option value="in_person">حضوري في العيادة</option>
                            <option value="remote">متابعة عن بُعد</option>
                        </select>
                    </div>

                    {{-- Management Preset Price Notice (NO manual input per user request) --}}
                    <div class="fin-form-group full" style="background:#051829;border:1px solid #164875;border-radius:10px;padding:12px;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <span style="font-size:11px;color:#8abde9;font-weight:700">السعر المعتمد من قبل الإدارة لهذه الخدمة:</span>
                            <div style="font-size:18px;font-weight:850;color:#2ce08d;margin-top:2px">
                                {{ number_format((float)$price) }} <small style="font-size:12px">{{ $currency }}</small>
                            </div>
                        </div>
                        <span style="font-size:10px;color:#6f92b4;background:#09223a;padding:5px 10px;border-radius:6px;border:1px solid #1c3c5c">
                            تسعيرة الإدارة الرسمية
                        </span>
                    </div>
                </div>

                {{-- STEP 2: Specialist & Appointment Time --}}
                <div x-show="step === 2 && !@js($showEditModal)" class="fin-form-grid wg-nut-booking-grid" style="padding:16px">
                    <div class="fin-form-group">
                        <label class="fin-label">اختصاصي التغذية <span class="fin-required">*</span></label>
                        <select class="fin-select" wire:model.live="nutritionist_id" {{ auth()->user()->role === 'nutritionist' ? 'disabled' : '' }}>
                            <option value="">اختر الأخصائي</option>
                            @foreach($nutritionists as $n)
                                <option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fin-form-group">
                        <label class="fin-label">تاريخ الموعد <span class="fin-required">*</span></label>
                        <input class="fin-field" wire:model.live="appointment_date" type="date" min="{{ now('Asia/Aden')->toDateString() }}" required>
                    </div>

                    <div class="fin-form-group full wg-nut-option-builder">
                        <label class="fin-label">مدة الجلسة <span class="fin-required">*</span></label>
                        <div class="wg-nut-duration-options">
                            @foreach([30, 45, 60, 90] as $minutes)
                                <button type="button" x-bind:class="{ 'active': Number(duration) === {{ $minutes }} }" x-on:click="duration = {{ $minutes }}; $wire.$set('duration_minutes', {{ $minutes }})">
                                    {{ $minutes }} دقيقة
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="fin-form-group">
                        <label class="fin-label">وقت بدء الموعد <span class="fin-required">*</span></label>
                        <input class="fin-field" wire:model="start_time" type="time" required>
                    </div>

                    <div class="fin-form-group full">
                        <div class="wg-nut-slots-fin">
                            <div>
                                <strong>الأوقات المتاحة للحجز</strong>
                                <span>تتحدث تلقائياً حسب جدول الأخصائي</span>
                            </div>
                            <div wire:loading.class="is-loading" wire:target="nutritionist_id,appointment_date,duration_minutes">
                                @forelse($availableSlots as $slot)
                                    <button type="button" wire:click="chooseSlot('{{ $slot }}')" class="{{ $start_time === $slot ? 'is-selected' : '' }}">
                                        {{ $fmtTime($slot) }}
                                    </button>
                                @empty
                                    <span class="wg-nut-no-slots">اختر الأخصائي والتاريخ؛ تأكد من وجود جدول عمل متاح.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Review & Direct Confirmation (or Edit Details) --}}
                <div x-show="step === 3 || @js($showEditModal)" class="fin-form-grid wg-nut-booking-grid" style="padding:16px">
                    @if(!$showEditModal)
                        <div class="fin-form-group full" style="background:#071626;border:1px solid #1a3c61;border-radius:12px;padding:16px">
                            <h3 style="font-size:14px;font-weight:800;color:#eaf2fb;margin-bottom:12px;border-bottom:1px solid #142f4c;padding-bottom:8px">
                                ملخص بيانات الحجز قبل الاعتماد:
                            </h3>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px">
                                <div>
                                    <span style="color:#7f93aa;display:block">نوع الخدمة والزيارة:</span>
                                    <strong style="color:#fff">{{ $serviceTypeMeta[$service_type][0] ?? 'استشارة' }} ({{ $visitTypeMeta[$visit_type] ?? 'حضوري' }})</strong>
                                </div>
                                <div>
                                    <span style="color:#7f93aa;display:block">تاريخ ووقت الموعد:</span>
                                    <strong style="color:#fff">{{ $appointment_date }} — الساعة {{ $fmtTime($start_time) }} ({{ $duration_minutes }} دقيقة)</strong>
                                </div>
                                <div>
                                    <span style="color:#7f93aa;display:block">الأخصائي المعالج:</span>
                                    <strong style="color:#fff">{{ $nutritionists->firstWhere('id', (int)$nutritionist_id)?->name ?? 'الأخصائي المحدد' }}</strong>
                                </div>
                                <div style="background:#09241b;padding:8px 12px;border-radius:8px;border:1px solid #16563c">
                                    <span style="color:#74c79f;display:block">السعر المحدد من الإدارة:</span>
                                    <strong style="font-size:16px;color:#2ce18d">{{ number_format((float)$price) }} {{ $currency }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="fin-form-group full">
                        <label class="fin-label">ملاحظات الزيارة أو العميل (اختياري)</label>
                        <textarea class="fin-field fin-textarea" wire:model="notes" placeholder="الهدف الغذائي، أي حساسية أو تاريخ صحي مهم..."></textarea>
                    </div>
                </div>

                {{-- Wizard Navigation Footer --}}
                <div class="fin-modal-foot" style="padding:14px 16px;background:#050d18;border-top:1px solid #132437;display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <div>
                        <button class="fin-btn" type="button" x-on:click="bookingOpen = false; $wire.closeBooking()">إلغاء</button>
                    </div>

                    <div style="display:flex;gap:8px">
                        @if(!$showEditModal)
                            <button x-show="step > 1" type="button" class="fin-btn" wire:click="prevBookingStep">
                                السابق ⟶
                            </button>

                            <button x-show="step < 3" type="button" class="fin-btn primary" wire:click="nextBookingStep">
                                ⟵ التالي
                            </button>

                            <button x-show="step === 3" class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="book">
                                <span wire:loading.remove wire:target="book">حفظ وتأكيد الحجز</span>
                                <span wire:loading wire:target="book">جاري حفظ الموعد...</span>
                            </button>
                        @else
                            <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="updateAppointment">
                                <span wire:loading.remove wire:target="updateAppointment">حفظ التعديلات</span>
                                <span wire:loading wire:target="updateAppointment">جاري حفظ الموعد...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Client Creation Modal --}}
    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="clientOpen" x-transition.opacity x-on:click.self="clientOpen = false; $wire.closeClient()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="createClient">
            <div class="fin-modal-head">
                <div>
                    <div class="fin-modal-title">إضافة عميل تغذية خاص</div>
                    <div class="fin-modal-sub">للزوار غير المشتركين؛ سيظهر العميل مباشرة ضمن الحجز.</div>
                </div>
                <button type="button" class="fin-close" x-on:click="clientOpen = false; $wire.closeClient()">×</button>
            </div>
            @if($errors->any() && $showClientModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-grid">
                <div class="fin-form-group"><label class="fin-label">الاسم الكامل <span class="fin-required">*</span></label><input class="fin-field" wire:model="new_client_name" required></div>
                <div class="fin-form-group"><label class="fin-label">رقم الهاتف <span class="fin-required">*</span></label><input class="fin-field" wire:model="new_client_phone" dir="ltr" required></div>
                <div class="fin-form-group full"><label class="fin-label">الجنس <span class="fin-required">*</span></label><select class="fin-select" wire:model="new_client_gender"><option value="male">ذكر</option><option value="female">أنثى</option></select></div>
                <div class="fin-form-group full"><label class="fin-label">ملاحظات صحية (اختياري)</label><textarea class="fin-field fin-textarea" wire:model="new_client_notes" placeholder="حالة صحية، هدف العميل..."></textarea></div>
            </div>
            <div class="fin-modal-foot">
                <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="createClient">
                    <span wire:loading.remove wire:target="createClient">حفظ العميل والمتابعة للحجز</span>
                    <span wire:loading wire:target="createClient">جاري حفظ العميل...</span>
                </button>
                <button class="fin-btn" type="button" x-on:click="clientOpen = false; $wire.closeClient()">إلغاء</button>
            </div>
        </form>
    </div>

    {{-- Specialist Schedule Modal --}}
    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="scheduleOpen" x-transition.opacity x-on:click.self="scheduleOpen = false; $wire.closeSchedule()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="addSchedule">
            <div class="fin-modal-head">
                <div>
                    <div class="fin-modal-title">جدول اختصاصي التغذية</div>
                    <div class="fin-modal-sub">حدد ساعات العمل؛ لن يقبل النظام أي حجز خارجها.</div>
                </div>
                <button type="button" class="fin-close" x-on:click="scheduleOpen = false; $wire.closeSchedule()">×</button>
            </div>
            @if($errors->any() && $showScheduleModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-grid">
                <div class="fin-form-group full"><label class="fin-label">الأخصائي <span class="fin-required">*</span></label><select class="fin-select" wire:model="nutritionist_id" {{ auth()->user()->role === 'nutritionist' ? 'disabled' : '' }}><option value="">اختر الأخصائي</option>@foreach($nutritionists as $n)<option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>@endforeach</select></div>
                <div class="fin-form-group full"><label class="fin-label">اليوم <span class="fin-required">*</span></label><select class="fin-select" wire:model="schedule_day"><option value="0">الأحد</option><option value="1">الاثنين</option><option value="2">الثلاثاء</option><option value="3">الأربعاء</option><option value="4">الخميس</option><option value="5">الجمعة</option><option value="6">السبت</option></select></div>
                <div class="fin-form-group"><label class="fin-label">من <span class="fin-required">*</span></label><input class="fin-field" wire:model="schedule_start" type="time"></div>
                <div class="fin-form-group"><label class="fin-label">إلى <span class="fin-required">*</span></label><input class="fin-field" wire:model="schedule_end" type="time"></div>
            </div>
            <div class="fin-modal-foot">
                <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="addSchedule">
                    <span wire:loading.remove wire:target="addSchedule">حفظ فترة العمل</span>
                    <span wire:loading wire:target="addSchedule">جاري حفظ الجدول...</span>
                </button>
                <button class="fin-btn" type="button" x-on:click="scheduleOpen = false; $wire.closeSchedule()">إلغاء</button>
            </div>
        </form>
    </div>

    {{-- Payment Collection Modal --}}
    <div class="fin-modal-backdrop wg-nut-fin-backdrop" x-cloak x-show="paymentOpen" x-transition.opacity x-on:click.self="paymentOpen = false; $wire.closePayment()">
        <form class="fin-modal small wg-nut-fin-modal" wire:submit="pay">
            <div class="fin-modal-head">
                <div>
                    <div class="fin-modal-title">استلام دفعة الموعد</div>
                    <div class="fin-modal-sub">
                        @if($selectedAppointment)
                            {{ $clientName($selectedAppointment) }} • {{ number_format((float)$selectedAppointment->price, $selectedAppointment->currency === 'SAR' ? 2 : 0) }} {{ $selectedAppointment->currency }}
                        @else
                            جارٍ تحميل بيانات الموعد...
                        @endif
                    </div>
                </div>
                <button type="button" class="fin-close" x-on:click="paymentOpen = false; $wire.closePayment()">×</button>
            </div>
            <div class="wg-nut-modal-loading" wire:loading.flex wire:target="openPayment"><span></span><strong>جارٍ تحميل قيمة الموعد...</strong></div>
            @if($errors->any() && $showPaymentModal)<div class="fin-errors">{{ $errors->first() }}</div>@endif
            @if($selectedAppointment)
                <div class="wg-nut-payment-amount">
                    <span>المبلغ المطلوب (معتمد من الإدارة)</span>
                    <strong>{{ number_format((float)$selectedAppointment->price, $selectedAppointment->currency === 'SAR' ? 2 : 0) }} <small>{{ $selectedAppointment->currency }}</small></strong>
                </div>
            @endif
            <div class="wg-nut-payment-methods">
                <label class="{{ $payment_method === 'cash' ? 'is-selected' : '' }}">
                    <input wire:model.live="payment_method" type="radio" value="cash">
                    <span>نقدي</span>
                    <small>دفع في العيادة</small>
                </label>
                <label class="{{ $payment_method === 'transfer' ? 'is-selected' : '' }}">
                    <input wire:model.live="payment_method" type="radio" value="transfer">
                    <span>تحويل</span>
                    <small>حوالة أو تحويل مصرفي</small>
                </label>
            </div>
            @if($payment_method === 'transfer')
                <div class="fin-form-grid">
                    <div class="fin-form-group"><label class="fin-label">جهة التحويل <span class="fin-required">*</span></label><select class="fin-select" wire:model="transfer_service"><option value="العمقي">العمقي</option><option value="الكريمي">الكريمي</option><option value="البسيري">البسيري</option></select></div>
                    <div class="fin-form-group"><label class="fin-label">مرجع التحويل</label><input class="fin-field" wire:model="transfer_reference" placeholder="رقم الحوالة أو المرجع"></div>
                </div>
                <label class="wg-nut-upload">
                    <input wire:model="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>
                    <span>إرفاق سند التحويل</span>
                    <small>JPG, PNG, WEBP, PDF حتى 5MB</small>
                </label>
            @endif
            <div class="fin-modal-foot">
                <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="pay,payment_proof">
                    <span wire:loading.remove wire:target="pay">تأكيد استلام الدفعة</span>
                    <span wire:loading wire:target="pay">جاري حفظ الدفعة...</span>
                </button>
                <button class="fin-btn" type="button" x-on:click="paymentOpen = false; $wire.closePayment()">إلغاء</button>
            </div>
        </form>
    </div>

    {{-- Cancellation Reason Modal --}}
    @if($showCancelModal)
        <div class="wg-nut-modal-backdrop" wire:click.self="$set('showCancelModal',false)">
            <section class="wg-nut-modal wg-nut-modal-confirm">
                <button class="wg-nut-modal-x" type="button" wire:click="$set('showCancelModal',false)">×</button>
                <header>
                    <i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg></i>
                    <div><h2>إلغاء وحذف الحجز</h2><p>سيتم إلغاء الموعد وحذفه من قائمة المواعيد النشطة.</p></div>
                </header>
                <form wire:submit="cancel">
                    <label class="wg-nut-full-label"><span>سبب الإلغاء *</span><textarea wire:model="cancellation_reason" rows="4" placeholder="اكتب سبب إلغاء الحجز..."></textarea></label>
                    <footer>
                        <button type="button" class="wg-nut-btn" wire:click="$set('showCancelModal',false)">تراجع</button>
                        <button class="wg-nut-btn wg-nut-btn-danger" type="submit">تأكيد إلغاء وحذف الموعد</button>
                    </footer>
                </form>
            </section>
        </div>
    @endif

    {{-- Reversal Reason Modal --}}
    @if($showReverseModal)
        <div class="wg-nut-modal-backdrop" wire:click.self="$set('showReverseModal',false)">
            <section class="wg-nut-modal wg-nut-modal-confirm">
                <button class="wg-nut-modal-x" type="button" wire:click="$set('showReverseModal',false)">×</button>
                <header>
                    <i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg></i>
                    <div><h2>عكس دفعة الموعد</h2><p>بعد العكس يعود الموعد إلى غير مدفوع ويمكن إلغاؤه عند الحاجة.</p></div>
                </header>
                <form wire:submit="reversePayment">
                    <label class="wg-nut-full-label"><span>سبب عكس الدفعة *</span><textarea wire:model="reversal_reason" rows="4" placeholder="اكتب سبب العكس..."></textarea></label>
                    <footer>
                        <button type="button" class="wg-nut-btn" wire:click="$set('showReverseModal',false)">تراجع</button>
                        <button class="wg-nut-btn wg-nut-btn-warning" type="submit">تأكيد عكس الدفعة</button>
                    </footer>
                </form>
            </section>
        </div>
    @endif
</div>
