<div class="wg-subscriptions-page" dir="rtl"
    x-data="{
        createOpen:@js(request()->boolean('create') && (bool) $member_id), createStep:1,
        detailsOpen:false, selectedSubscription:null,
        memberReady:@js((bool) $member_id), packageReady:@js((bool) $package_id),
        memberId:@js($member_id), packageId:@js($package_id), period:@js($period),
        currency:@js($currency), discount:Number(@js($discount_amount)) || 0,
        paymentPlan:@js($payment_plan), paymentMethod:@js($payment_method),
        installmentCount:Number(@js($installment_count)) || 1, dueDates:@js($installment_due_dates),
        firstPayment:@js($first_payment_amount), startDate:@js($start_date),
        members:@js($members->mapWithKeys(fn($member) => [(string) $member->id => ['name' => $member->full_name, 'code' => $member->membership_code, 'period' => $member->assigned_period]])),
        packages:@js($packages->mapWithKeys(fn($package) => [(string) $package->id => ['name' => $package->name, 'duration' => $package->duration_value.' '.$package->duration_unit, 'price_yer' => $package->price_yer, 'price_sar' => $package->price_sar]])),
        openDetails(subscription){ this.selectedSubscription=subscription; this.detailsOpen=true },
        selectedMember(){ return this.members[String(this.memberId)] || null },
        selectedPackage(){ return this.packages[String(this.packageId)] || null },
        basePrice(){ const item=this.selectedPackage(); return Number(this.currency==='SAR' ? item?.price_sar : item?.price_yer) || 0 },
        finalPrice(){ return Math.max(0, this.basePrice() - Math.max(0, Number(this.discount) || 0)) },
        minimumPayment(){ return Math.round(this.finalPrice() * 50) / 100 },
        money(value){ return Number(value || 0).toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:2}) + ' ' + this.currency },
        setMember(value){
            this.memberId=value ? Number(value) : null; this.memberReady=!!value;
            this.period=this.selectedMember()?.period || 'men';
            this.$wire.$set('member_id',this.memberId,false); this.$wire.$set('period',this.period,false);
        },
        setPackage(value){
            this.packageId=value ? Number(value) : null; this.packageReady=!!value;
            this.$wire.$set('package_id',this.packageId,false); this.syncSuggestedPayment();
        },
        setCurrency(value){ this.currency=value; this.$wire.$set('currency',value,false); this.syncSuggestedPayment() },
        setDiscount(value){
            const clean = window.wgCleanMoney ? window.wgCleanMoney(value) : String(value).replace(/,/g, '');
            this.discount = Math.max(0, Number(clean) || 0);
            this.$wire.$set('discount_amount', String(this.discount), false);
            this.syncSuggestedPayment();
        },
        setPlan(value){
            this.paymentPlan=value; this.$wire.$set('payment_plan',value,false);
            if(value==='full'){ this.installmentCount=1; this.dueDates=[]; this.$wire.$set('installment_count',1,false); this.$wire.$set('installment_due_dates',[],false) }
            else if(this.installmentCount<2){ this.installmentCount=2; this.resizeDueDates() }
            this.syncSuggestedPayment();
        },
        resizeDueDates(){
            this.installmentCount=Math.min(24,Math.max(2,Number(this.installmentCount)||2));
            const length=this.installmentCount-1; this.dueDates=this.dueDates.slice(0,length);
            while(this.dueDates.length<length) this.dueDates.push('');
            this.$wire.$set('installment_count',this.installmentCount,false); this.$wire.$set('installment_due_dates',[...this.dueDates],false);
        },
        syncSuggestedPayment(){
            const value=this.paymentPlan==='full' ? this.finalPrice() : this.minimumPayment();
            this.firstPayment=window.wgFormatMoney ? window.wgFormatMoney(value.toFixed(2)) : value.toFixed(2);
            this.$wire.$set('first_payment_amount', value.toFixed(2), false);
        },
        setFirstPayment(value){
            const clean = window.wgCleanMoney ? window.wgCleanMoney(value) : String(value).replace(/,/g, '');
            this.firstPayment = clean;
            this.$wire.$set('first_payment_amount', clean, false);
        },
        setPaymentMethod(value){ this.paymentMethod=value; this.$wire.$set('payment_method',value,false) },
        choosePackage(value){ this.setPackage(value); this.createOpen=true; this.createStep=1 }
    }"
    x-on:subscription-created.window="createOpen=false;createStep=1">

    @php
        $selectedPackage = $package_id ? $packages->firstWhere('id', $package_id) : null;
        $selectedMember = $member_id ? $members->firstWhere('id', $member_id) : null;
        $basePrice = $selectedPackage
            ? (float) ($currency === 'YER' ? ($selectedPackage->price_yer ?? 0) : ($selectedPackage->price_sar ?? 0))
            : 0;
        $discountValue = max(0, (float) ($discount_amount ?: 0));
        $finalPrice = max(0, $basePrice - $discountValue);
        $minimumFirstPayment = round($finalPrice * 0.5, 2);

        $chartMax = max(1, collect($revenueSeries)->max('value') ?: 1);
        $chartPoints = collect($revenueSeries)->map(function ($item, $index) use ($chartMax) {
            $x = 12 + ($index * 55);
            $y = 102 - (($item['value'] / $chartMax) * 72);
            return round($x, 2).','.round($y, 2);
        })->implode(' ');
        $firstPoint = '12,108';
        $lastX = 12 + ((max(0, count($revenueSeries) - 1)) * 55);
        $areaPoints = $firstPoint.' '.$chartPoints.' '.$lastX.',108';

        $palette = ['#1478ff','#7d61ff','#ff9f00','#ff4a57'];
        $stops = [];
        $cursor = 0;
        foreach($packageDistribution as $i => $item) {
            $next = min(100, $cursor + $item['percent']);
            $stops[] = $palette[$i % count($palette)].' '.$cursor.'% '.$next.'%';
            $cursor = $next;
        }
        if ($cursor < 100) $stops[] = '#12243a '.$cursor.'% 100%';
        $donutGradient = implode(',', $stops) ?: '#12243a 0% 100%';
        $featuredPackageName = collect($packageDistribution)->sortByDesc('percent')->first()['name'] ?? $packages->first()?->name;
    @endphp

    @if(session('success'))<div class="wg-flash wg-sub-flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="wg-errors wg-sub-errors">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="wg-sub-shell">
        <section class="wg-sub-maincol">
            <div class="wg-sub-summary-row">
                @php
                    $summaryCards = [
                        ['إجمالي الاشتراكات',$counts['total'],'blue','users'],
                        ['الاشتراكات النشطة',$counts['active'],'green','calendar-check'],
                        ['المنتهية هذا الشهر',$counts['expired_this_month'],'orange','calendar-end'],
                        ['الجديدة هذا الشهر',$counts['new_this_month'],'purple','user-plus'],
                    ];
                @endphp
                @foreach($summaryCards as [$label,$value,$tone,$icon])
                    <div class="wg-sub-summary-card tone-{{ $tone }}">
                        <div>
                            <span>{{ $label }}</span>
                            <strong>{{ number_format($value) }}</strong>
                            <small>{{ $value > 0 ? 'بيانات مباشرة من النظام' : 'لا توجد بيانات بعد' }}</small>
                        </div>
                        <div class="wg-sub-summary-icon">
                            @if($icon === 'users')
                                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 11a3 3 0 1 0 0-6M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                            @elseif($icon === 'calendar-check')
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14M9 14l2 2 4-4"/></svg>
                            @elseif($icon === 'calendar-end')
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14M10 14h4M12 12v4"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M16 11h6"/></svg>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <section class="wg-sub-packages-section" aria-labelledby="available-packages-title">
                <div class="wg-sub-packages-head">
                    <div>
                        <span class="wg-sub-kicker">خطط العضوية</span>
                        <h2 id="available-packages-title">الباقات المتاحة للاشتراك</h2>
                        <p>اختر الباقة المناسبة وابدأ تسجيل الاشتراك مباشرة.</p>
                    </div>
                    @if(auth()->user()->role === 'owner')
                        <div class="wg-sub-package-admin-actions">
                            <a href="{{ route('packages.index') }}" wire:navigate class="wg-sub-package-link">إدارة الباقات</a>
                            <a href="{{ route('packages.index', ['create' => 1]) }}" wire:navigate class="wg-sub-package-add">＋ إضافة باقة</a>
                        </div>
                    @endif
                </div>
                <div class="wg-sub-packages-grid">
                    @forelse($packages as $package)
                        @php
                            $durationUnit = match($package->duration_unit) {
                                'day' => 'يوم', 'week' => 'أسبوع', 'month' => 'شهر', 'year' => 'سنة', default => $package->duration_unit,
                            };
                            $packageFeatures = collect(preg_split('/\r\n|\r|\n|[؛•]/u', trim((string) $package->description)))
                                ->map(fn ($feature) => trim($feature, " -–—،,"))
                                ->filter()->take(3);
                            if ($packageFeatures->isEmpty()) {
                                $packageFeatures = collect([
                                    'دخول النادي طوال مدة الباقة',
                                    'متاحة للاشتراكات الجديدة',
                                    'إمكانية التجديد من صفحة الاشتراكات',
                                ]);
                            }
                            $isFeatured = $package->name === $featuredPackageName;
                        @endphp
                        <article class="wg-sub-package-card {{ $isFeatured ? 'is-featured' : '' }}">
                            <div class="wg-sub-package-card-top">
                                <div><h3>{{ $package->name }}</h3><span>مدة الباقة: {{ $package->duration_value }} {{ $durationUnit }}</span></div>
                                @if($isFeatured)<b class="wg-sub-package-popular">الأكثر اختيارًا</b>@endif
                            </div>
                            <div class="wg-sub-package-prices">
                                @if($package->price_sar !== null)<div><small>ريال سعودي</small><strong dir="ltr">{{ number_format((float) $package->price_sar, 0) }} <em>SAR</em></strong></div>@endif
                                @if($package->price_yer !== null)<div><small>ريال يمني</small><strong dir="ltr">{{ number_format((float) $package->price_yer, 0) }} <em>YER</em></strong></div>@endif
                            </div>
                            <ul class="wg-sub-package-features">
                                @foreach($packageFeatures as $feature)<li><i>✓</i><span>{{ $feature }}</span></li>@endforeach
                            </ul>
                            @if(auth()->user()->hasGymPermission('subscriptions.create') || auth()->user()->hasGymPermission('subscriptions.manage') || auth()->user()->role === 'owner')
                                <button type="button" class="wg-sub-package-choose" x-on:click="choosePackage({{ $package->id }})">اختيار الباقة وبدء اشتراك</button>
                            @endif
                        </article>
                    @empty
                        <div class="wg-sub-packages-empty"><strong>لا توجد باقات نشطة حاليًا.</strong>@if(auth()->user()->role === 'owner')<a href="{{ route('packages.index', ['create' => 1]) }}" wire:navigate>إضافة أول باقة</a>@endif</div>
                    @endforelse
                </div>
            </section>

            <div class="wg-sub-filter-panel">
                <div class="wg-sub-filter-search">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="subscription-search" wire:model.live.debounce.300ms="search" placeholder="ابحث عن عضو أو باقة أو كود اشتراك..." autocomplete="off">
                </div>
                <label class="wg-sub-date-field" for="subscription-from-date">
                    <span>من تاريخ</span>
                    <div class="wg-sub-date-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14"/></svg><input id="subscription-from-date" wire:model.live="from_date" type="date" max="{{ $to_date ?: null }}" title="اختر تاريخ البداية" onclick="this.showPicker && this.showPicker()" onfocus="this.showPicker && this.showPicker()"></div>
                </label>
                <label class="wg-sub-date-field" for="subscription-to-date">
                    <span>إلى تاريخ</span>
                    <div class="wg-sub-date-input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 2v4M16 2v4M5 9h14"/></svg><input id="subscription-to-date" wire:model.live="to_date" type="date" min="{{ $from_date ?: null }}" title="اختر تاريخ النهاية" onclick="this.showPicker && this.showPicker()" onfocus="this.showPicker && this.showPicker()"></div>
                </label>
                <select wire:model.live="payment_method_filter">
                    <option value="all">كل طرق الدفع</option>
                    <option value="cash">نقدي</option>
                    <option value="transfer">تحويل / صرافة</option>
                </select>
                <select wire:model.live="package_filter">
                    <option value="all">كل الباقات</option>
                    @foreach($allPackages as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <select wire:model.live="status_filter">
                    <option value="all">كل الحالات</option>
                    <option value="upcoming">قادم</option>
                    <option value="active">نشط</option>
                    <option value="financial_overdue">متأخر ماليًا</option>
                    <option value="expiring_soon">ينتهي قريبًا</option>
                    <option value="expired">منتهي</option>
                    <option value="cancelled">ملغي</option>
                    <option value="refunded">مسترد</option>
                </select>
                <button type="button" wire:click="resetFilters" class="wg-sub-reset">
                    <svg viewBox="0 0 24 24"><path d="M20 6v6h-6M4 18v-6h6M18.5 9A7 7 0 0 0 6.2 6.2L4 9M5.5 15A7 7 0 0 0 17.8 17.8L20 15"/></svg>
                    إعادة تعيين
                </button>
            </div>

            <div class="wg-sub-table-card">
                <div class="wg-sub-table-scroll">
                    <table class="wg-sub-table">
                        <thead>
                            <tr>
                                <th>#</th><th>اسم العضو</th><th>الباقة</th><th>حالة الاشتراك</th><th>تاريخ البداية</th><th>تاريخ الانتهاء</th><th>المبلغ</th><th>طريقة الدفع</th><th>المتبقي</th><th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($subscriptions as $s)
                            @php
                                $status = match($s->status){
                                    'active'=>['نشط','green'],
                                    'financial_overdue'=>['متأخر ماليًا','red'],
                                    'expiring_soon'=>['ينتهي قريبًا','orange'],
                                    'upcoming'=>['قادم','blue'],
                                    'expired'=>['منتهي','red'],
                                    'cancelled'=>['ملغي','muted'],
                                    'refunded'=>['مسترد','purple'],
                                    default=>[$s->status,'blue']
                                };
                                $paid = (float) ($s->paid_amount ?? 0);
                                $remaining = max(0, (float)$s->final_price - $paid);
                                $payableInstallment = $s->payment_plan === 'installments'
                                    ? $s->installments->first(fn ($installment) => in_array($installment->status, ['pending', 'overdue'], true) && (float) $installment->amount > 0)
                                    : null;
                                $canReceivePayment = $remaining > 0.009 && (bool) $payableInstallment;
                                $latestPayment = $s->payments->first();
                                $durationLabel = match($s->duration_unit_snapshot){'day'=>'يوم','week'=>'أسبوع','month'=>'شهر','year'=>'سنة',default=>$s->duration_unit_snapshot};
                                $subscriptionClient = [
                                    'id' => $s->id,
                                    'member_name' => $s->member?->full_name ?? '—',
                                    'member_code' => $s->member?->membership_code ?? '—',
                                    'package' => $s->package_name_snapshot,
                                    'duration' => $s->duration_value_snapshot.' '.$durationLabel,
                                    'status_label' => $status[0],
                                    'status_badge' => match($status[1]) {
                                        'green' => 'wg-badge-green',
                                        'red' => 'wg-badge-red',
                                        'orange' => 'wg-badge-orange',
                                        'purple' => 'wg-badge-purple',
                                        default => 'wg-badge-blue',
                                    },
                                    'start_date' => $s->start_date->format('Y-m-d'),
                                    'end_date' => $s->end_date->format('Y-m-d'),
                                    'currency' => $s->currency,
                                    'original_price' => \App\Support\NumberFormatter::money($s->price_snapshot).' '.$s->currency,
                                    'discount' => \App\Support\NumberFormatter::money($s->discount_amount).' '.$s->currency,
                                    'final_price' => \App\Support\NumberFormatter::money($s->final_price).' '.$s->currency,
                                    'paid' => \App\Support\NumberFormatter::money($paid).' '.$s->currency,
                                    'remaining' => \App\Support\NumberFormatter::money($remaining).' '.$s->currency,
                                    'remaining_tone' => $remaining > 0 ? 'wg-orange' : 'wg-green',
                                    'payment_plan' => $s->payment_plan === 'installments' ? 'أقساط - '.$s->installment_count : 'دفع كامل',
                                    'can_receive_payment' => $canReceivePayment,
                                    'notes' => $s->notes,
                                    'installments' => $s->installments->map(fn ($installment) => [
                                        'number' => $installment->installment_number,
                                        'due_date' => $installment->due_date->format('Y-m-d'),
                                        'amount' => \App\Support\NumberFormatter::money($installment->amount).' '.$s->currency,
                                        'status_label' => $installment->status === 'paid' ? 'مدفوع' : ($installment->due_date->isPast() ? 'مستحق' : 'قادم'),
                                        'status_badge' => $installment->status === 'paid' ? 'wg-badge-green' : ($installment->due_date->isPast() ? 'wg-badge-red' : 'wg-badge-blue'),
                                        'paid_at' => $installment->paid_at?->timezone('Asia/Aden')->format('Y-m-d H:i') ?? '—',
                                    ])->values()->all(),
                                    'payments' => $s->payments->map(fn ($payment) => [
                                        'paid_at' => $payment->paid_at?->timezone('Asia/Aden')->format('Y-m-d H:i') ?? '—',
                                        'amount' => \App\Support\NumberFormatter::money($payment->amount).' '.$payment->currency,
                                        'method' => $payment->payment_method === 'cash' ? 'نقدي' : 'تحويل / صرافة',
                                        'receipt' => $payment->receipt_number,
                                        'transfer_service' => $payment->transfer_service ?: '—',
                                        'transfer_reference' => $payment->transfer_reference ?: '—',
                                        'proof_url' => $payment->proof_path ? route('payments.proof', $payment) : null,
                                    ])->values()->all(),

                                ];
                            @endphp
                            <tr>
                                <td class="wg-sub-rownum">{{ $s->id }}</td>
                                <td>
                                    <div class="wg-sub-member-cell">
                                        <span class="wg-sub-mini-avatar">{{ mb_substr($s->member?->full_name ?? 'ع',0,1) }}</span>
                                        <div><strong>{{ $s->member?->full_name }}</strong><small dir="ltr">{{ $s->member?->membership_code }}</small></div>
                                    </div>
                                </td>
                                <td><strong>{{ $s->package_name_snapshot }}</strong><small>{{ $s->duration_value_snapshot }} {{ $durationLabel }}</small></td>
                                <td><span class="wg-sub-status tone-{{ $status[1] }}">{{ $status[0] }}</span></td>
                                <td dir="ltr">{{ $s->start_date->format('Y-m-d') }}</td>
                                <td dir="ltr">{{ $s->end_date->format('Y-m-d') }}</td>
                                <td class="wg-sub-money" dir="ltr">{{ number_format((float)$s->final_price,0) }} <small>{{ $s->currency }}</small></td>
                                <td>{{ !$latestPayment ? '—' : ($latestPayment->payment_method === 'cash' ? 'نقدي' : 'تحويل / صرافة') }}</td>
                                <td class="{{ $remaining > 0 ? 'wg-sub-remaining' : 'wg-sub-paid' }}" dir="ltr">{{ number_format($remaining,0) }} <small>{{ $s->currency }}</small></td>
                                <td>
                                    <div class="wg-sub-actions">
                                        <button type="button" title="عرض التفاصيل" x-on:click="openDetails(@js($subscriptionClient))">
                                            <svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        </button>
                                        @if((auth()->user()->hasGymPermission('payments.create') || auth()->user()->role === 'owner') && $canReceivePayment)
                                            <button type="button" wire:click="openCollection({{ $s->id }})" wire:loading.attr="disabled" wire:target="openCollection({{ $s->id }})" title="استلام القسط المتبقي">
                                                <svg viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 10h18M7 15h3"/></svg>
                                            </button>
                                        @endif
                                        <button type="button" title="إجراءات الاشتراك" x-on:click="openDetails(@js($subscriptionClient))">
                                            <svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="wg-sub-empty">لا توجد اشتراكات مطابقة للفلاتر الحالية.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="wg-sub-table-footer">
                    <div class="wg-sub-page-size"><span>10</span><small>لكل صفحة</small></div>
                    <div>عرض {{ $subscriptions->firstItem() ?? 0 }} - {{ $subscriptions->lastItem() ?? 0 }} من {{ number_format($subscriptions->total()) }} اشتراك</div>
                    <div class="wg-sub-pagination">{{ $subscriptions->links() }}</div>
                </div>
            </div>
        </section>

        <aside class="wg-sub-aside">
            <div class="wg-sub-aside-actions">
                @if(auth()->user()->hasGymPermission('subscriptions.create') || auth()->user()->hasGymPermission('subscriptions.manage') || auth()->user()->role === 'owner')
                    <button type="button" class="wg-sub-new-btn" x-on:click="createOpen=true;createStep=1">
                        <span>اشتراك جديد</span><b>＋</b>
                    </button>
                @endif
                @if(auth()->user()->hasGymPermission('reports.operational') || auth()->user()->hasGymPermission('reports.finance') || auth()->user()->role === 'owner')
                    <a class="wg-sub-export-btn" href="{{ route('reports.pdf', ['from'=>$monthReportFrom,'to'=>$monthReportTo,'gender'=>'all','currency'=>$summaryCurrency]) }}" target="_blank">
                        <span>تصدير التقرير</span>
                        <svg viewBox="0 0 24 24"><path d="M12 3v12M8 11l4 4 4-4M5 20h14"/></svg>
                    </a>
                @endif
            </div>

            <div class="wg-sub-aside-card">
                <div class="wg-sub-aside-head"><h3>ملخص الاشتراكات هذا الشهر</h3><span>＋</span></div>
                <div class="wg-sub-mini-summary">
                    <div class="tone-green"><span>النشطة</span><strong>{{ number_format($counts['active']) }}</strong></div>
                    <div class="tone-purple"><span>الجديدة</span><strong>{{ number_format($counts['new_this_month']) }}</strong></div>
                    <div class="tone-orange"><span>تنتهي قريبًا</span><strong>{{ number_format($counts['expiring']) }}</strong></div>
                    <div class="tone-red"><span>متأخرة ماليًا</span><strong>{{ number_format($counts['overdue']) }}</strong></div>
                </div>
            </div>

            <div class="wg-sub-aside-card wg-sub-revenue-card">
                <div class="wg-sub-aside-head">
                    <div><h3>إيرادات الاشتراكات</h3><small>هذا الشهر</small></div>
                    <select wire:model.live="summary_currency"><option value="YER">YER</option><option value="SAR">SAR</option></select>
                </div>
                <strong class="wg-sub-revenue-number" dir="ltr">{{ number_format($currentRevenue,0) }} {{ $summaryCurrency }}</strong>
                <span class="wg-sub-revenue-change {{ $revenueChange >= 0 ? 'is-up' : 'is-down' }}">{{ $revenueChange >= 0 ? '+' : '' }}{{ $revenueChange }}% عن الشهر الماضي</span>
                <svg class="wg-sub-revenue-chart" viewBox="0 0 300 115" preserveAspectRatio="none">
                    <defs><linearGradient id="subArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1478ff" stop-opacity=".45"/><stop offset="1" stop-color="#1478ff" stop-opacity="0"/></linearGradient></defs>
                    <polygon points="{{ $areaPoints }}" fill="url(#subArea)"/>
                    <polyline points="{{ $chartPoints }}" fill="none" stroke="#1482ff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    @foreach($revenueSeries as $i => $point)
                        @php $cx=12+($i*55); $cy=102-(($point['value']/$chartMax)*72); @endphp
                        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3.5" fill="#1786ff"/>
                    @endforeach
                </svg>
                <div class="wg-sub-chart-labels">@foreach($revenueSeries as $point)<span>{{ $point['label'] }}</span>@endforeach</div>
            </div>

            <div class="wg-sub-aside-card wg-sub-distribution-card">
                <h3>توزيع الاشتراكات حسب الباقة</h3>
                <div class="wg-sub-donut-wrap">
                    <div class="wg-sub-donut" style="background:conic-gradient({{ $donutGradient }})"><div><small>إجمالي</small><strong>{{ number_format($counts['active'] + $counts['overdue'] + $counts['expiring']) }}</strong><span>اشتراك</span></div></div>
                    <div class="wg-sub-legend">
                        @forelse($packageDistribution as $i => $item)
                            <div><i style="background:{{ $palette[$i % count($palette)] }}"></i><span>{{ $item['name'] }}</span><b>{{ $item['percent'] }}%</b></div>
                        @empty
                            <div><i style="background:#334155"></i><span>لا توجد بيانات</span><b>0%</b></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>
    </div>

    {{-- New subscription wizard --}}
    <div class="wg-modal-backdrop" x-cloak x-show="createOpen" x-transition.opacity>
        <div class="wg-modal wg-modal-lg" x-on:click.outside="createOpen=false">
            <form wire:submit="create">
                <div class="wg-modal-head">
                    <div>
                        <h3 class="wg-section-title" style="font-size:18px">اشتراك جديد</h3>
                        <div class="wg-subtitle">اختيار العضو والبـاقة ← تفاصيل الاشتراك ← الدفع والتأكيد</div>
                    </div>
                    <button type="button" class="wg-modal-x" x-on:click="createOpen=false">×</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:14px 18px;border-bottom:1px solid var(--wg-border)">
                    <div :class="createStep===1 ? 'wg-badge wg-badge-blue' : 'wg-badge'" style="justify-content:center">1 · العضو والبـاقة</div>
                    <div :class="createStep===2 ? 'wg-badge wg-badge-blue' : 'wg-badge'" style="justify-content:center">2 · تفاصيل الاشتراك</div>
                    <div :class="createStep===3 ? 'wg-badge wg-badge-blue' : 'wg-badge'" style="justify-content:center">3 · الدفع والتأكيد</div>
                </div>

                <div class="wg-modal-body">
                    @if($errors->any())
                        <div class="wg-errors wg-sub-modal-errors" style="margin-bottom:12px">
                            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <div x-show="createStep===1">
                        <div class="wg-two">
                            <div>
                                <label class="wg-label">العضو *</label>
                                <select x-model="memberId" x-on:change="setMember($event.target.value)" class="wg-select">
                                    <option value="">اختر العضو</option>
                                    @foreach($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} - {{ $m->membership_code }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="wg-label">الباقة *</label>
                                <select x-model="packageId" x-on:change="setPackage($event.target.value)" class="wg-select">
                                    <option value="">اختر الباقة</option>
                                    @foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                </select>
                            </div>
                            <div><label class="wg-label">الفترة *</label><select x-model="period" x-on:change="$wire.$set('period',period,false)" class="wg-select"><option value="men">رجال</option><option value="women">نساء</option></select></div>
                            <div><label class="wg-label">تاريخ البداية *</label><input x-model="startDate" x-on:change="$wire.$set('start_date',startDate,false)" type="date" class="wg-field"></div>
                            <div><label class="wg-label">العملة *</label><select x-model="currency" x-on:change="setCurrency($event.target.value)" class="wg-select"><option value="YER">YER - ريال يمني</option><option value="SAR">SAR - ريال سعودي</option></select></div>
                            <div class="wg-card wg-card-pad">
                                <div class="wg-label">السعر من الباقة</div>
                                <div class="wg-kpi-money" :class="basePrice()>0 ? 'wg-blue' : 'wg-muted'" x-text="money(basePrice())"></div>
                            </div>
                        </div>
                        <div class="wg-card" style="padding:11px 12px;margin-top:12px;font-size:10px;color:#8ca0b9">إذا كان للعضو اشتراك حالي، التجديد المبكر يبدأ تلقائيًا بعد نهاية الاشتراك الحالي.</div>
                    </div>

                    <div x-show="createStep===2" x-cloak>
                        <div class="wg-two">
                            <div class="wg-card wg-card-pad">
                                <div class="wg-label">العضو المختار</div>
                                <strong x-text="selectedMember()?.name || 'لم يتم الاختيار'"></strong>
                                <div class="wg-muted" style="font-size:10px;margin-top:4px" x-text="selectedMember()?.code || ''"></div>
                            </div>
                            <div class="wg-card wg-card-pad">
                                <div class="wg-label">الباقة المختارة</div>
                                <strong x-text="selectedPackage()?.name || 'لم يتم الاختيار'"></strong>
                                <div class="wg-muted" style="font-size:10px;margin-top:4px" x-text="selectedPackage()?.duration || ''"></div>
                            </div>
                        </div>

                        <div class="wg-three" style="margin-top:12px">
                            <div><label class="wg-label">السعر الأصلي</label><div class="wg-field" style="display:flex;align-items:center" dir="ltr" x-text="money(basePrice())"></div></div>
                            <div><label class="wg-label">الخصم</label><input x-model="discount" x-on:input.debounce.150ms="setDiscount($event.target.value)" type="text" inputmode="decimal" x-money class="wg-field wg-money-input" placeholder="0"></div>
                            <div><label class="wg-label">السعر النهائي</label><div class="wg-field wg-green" style="display:flex;align-items:center;font-weight:800" dir="ltr" x-text="money(finalPrice())"></div></div>
                        </div>

                        <div class="wg-two" style="margin-top:12px">
                            <label class="wg-card wg-card-pad" style="cursor:pointer">
                                <input x-model="paymentPlan" x-on:change="setPlan('full')" type="radio" value="full"> <strong style="margin-right:6px">دفع كامل</strong>
                                <div class="wg-muted" style="font-size:10px;margin-top:5px">سداد قيمة الاشتراك كاملة.</div>
                            </label>
                            <label class="wg-card wg-card-pad" style="cursor:pointer">
                                <input x-model="paymentPlan" x-on:change="setPlan('installments')" type="radio" value="installments"> <strong style="margin-right:6px">أقساط</strong>
                                <div class="wg-muted" style="font-size:10px;margin-top:5px">الدفعة الأولى لا تقل عن 50%.</div>
                            </label>
                        </div>

                        <div class="wg-three" style="margin-top:12px">
                            <div>
                                <label class="wg-label">مبلغ الدفعة الأولى *</label>
                                <input x-model="firstPayment" x-on:input.debounce.150ms="setFirstPayment($event.target.value)" type="text" inputmode="decimal" x-money class="wg-field wg-money-input" placeholder="0">
                                <button type="button" class="wg-btn wg-btn-sm" style="margin-top:6px" x-on:click="syncSuggestedPayment()"><span x-text="paymentPlan==='full' ? 'استخدام السعر النهائي' : 'استخدام الحد الأدنى 50%'"></span></button>
                            </div>
                            <div x-show="paymentPlan==='installments'" x-cloak><label class="wg-label">عدد الأقساط *</label><input x-model="installmentCount" x-on:change="resizeDueDates()" x-bind:disabled="paymentPlan!=='installments'" type="number" min="2" max="24" class="wg-field"></div>
                            <div x-show="paymentPlan==='installments'" x-cloak class="wg-card wg-card-pad"><div class="wg-label">الحد الأدنى للدفعة الأولى</div><div class="wg-kpi-money wg-green" x-text="money(minimumPayment())"></div></div>
                            <div x-show="paymentPlan==='full'" class="wg-card wg-card-pad" style="grid-column:span 2"><div class="wg-label">المبلغ المطلوب للدفع الكامل</div><div class="wg-kpi-money wg-green" x-text="money(finalPrice())"></div></div>
                        </div>

                        <div x-show="paymentPlan==='installments' && dueDates.length" x-cloak class="wg-card wg-card-pad" style="margin-top:12px">
                            <h4 class="wg-section-title" style="margin-bottom:10px">مواعيد الأقساط المتبقية</h4>
                            <div class="wg-three">
                                <template x-for="(dueDate,index) in dueDates" :key="index">
                                    <div><label class="wg-label" x-text="'موعد القسط ' + (index + 2)"></label><input x-model="dueDates[index]" x-on:change="$wire.$set('installment_due_dates',[...dueDates],false)" type="date" class="wg-field"></div>
                                </template>
                            </div>
                        </div>

                        <div style="margin-top:12px"><label class="wg-label">ملاحظات (اختياري)</label><textarea wire:model="notes" class="wg-textarea" placeholder="ملاحظات الاشتراك..."></textarea></div>
                    </div>

                    <div x-show="createStep===3" x-cloak>
                        <div class="wg-two">
                            <div>
                                <label class="wg-label">طريقة الدفع *</label>
                                <select x-model="paymentMethod" x-on:change="setPaymentMethod($event.target.value)" class="wg-select"><option value="cash">نقدي</option><option value="transfer">تحويل / صرافة محلية</option></select>
                            </div>
                            <div class="wg-card wg-card-pad">
                                <div class="wg-label">العملة</div>
                                <div class="wg-kpi-money wg-blue" x-text="currency"></div>
                            </div>
                            <div x-show="paymentMethod==='transfer'" x-cloak><label class="wg-label">خدمة التحويل / الصرافة *</label><input wire:model="transfer_service" class="wg-field" placeholder="اسم الخدمة"></div>
                            <div x-show="paymentMethod==='transfer'" x-cloak><label class="wg-label">رقم المرجع @if($requireTransferReference)*@else (اختياري)@endif</label><input wire:model="transfer_reference" class="wg-field" placeholder="مرجع التحويل"></div>
                            <div x-show="paymentMethod==='transfer'" x-cloak style="grid-column:1/-1">
                                <label class="wg-label">صورة أو ملف سند التحويل @if($requirePaymentProof)*@else (اختياري)@endif</label>
                                <input wire:model="payment_proof" type="file" accept=".jpg,.jpeg,.png,.pdf" class="wg-field wg-sub-proof-field">
                                <small class="wg-muted" style="display:block;margin-top:6px">JPG أو PNG أو PDF، بحد أقصى 2MB.</small>
                                <small wire:loading wire:target="payment_proof" class="wg-blue" style="display:block;margin-top:5px">جارٍ رفع السند...</small>
                            </div>
                        </div>

                        <div class="wg-card wg-card-pad" style="margin-top:12px">
                            <h4 class="wg-section-title" style="margin-bottom:12px">ملخص الاشتراك</h4>
                            <div class="wg-three">
                                <div><div class="wg-label">العضو</div><strong x-text="selectedMember()?.name || '—'"></strong></div>
                                <div><div class="wg-label">الباقة</div><strong x-text="selectedPackage()?.name || '—'"></strong></div>
                                <div><div class="wg-label">السعر النهائي</div><strong class="wg-green" dir="ltr" x-text="money(finalPrice())"></strong></div>
                                <div><div class="wg-label">خطة الدفع</div><strong x-text="paymentPlan==='full' ? 'دفع كامل' : 'أقساط'"></strong></div>
                                <div><div class="wg-label">الدفعة الأولى</div><strong dir="ltr" x-text="money(firstPayment)"></strong></div>
                                <div><div class="wg-label">تاريخ البداية المطلوب</div><strong x-text="startDate"></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wg-modal-foot" style="justify-content:space-between">
                    <div><button type="button" class="wg-btn" x-on:click="createOpen=false">إلغاء</button></div>
                    <div style="display:flex;gap:8px">
                        <button x-show="createStep>1" type="button" class="wg-btn" x-on:click="createStep--">رجوع</button>
                        <button x-show="createStep<3" type="button" class="wg-btn wg-btn-primary" x-bind:disabled="createStep===1 && (!memberReady || !packageReady)" x-bind:title="createStep===1 && (!memberReady || !packageReady) ? 'اختر العضو والباقة أولًا' : ''" x-on:click="createStep++">التالي</button>
                        <button x-show="createStep===3" x-cloak type="submit" class="wg-btn wg-btn-primary" wire:loading.attr="disabled" wire:target="create,payment_proof"><span wire:loading.remove wire:target="create">تأكيد الاشتراك واستلام الدفعة</span><span wire:loading wire:target="create">جارٍ الحفظ...</span></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Compact installment collection modal --}}
    @if($collectionOpen)
        <div class="wg-modal-backdrop wg-sub-collection-backdrop" wire:key="subscription-collection-{{ $collectionInstallmentId }}" x-on:keydown.escape.window="$wire.closeCollection()" x-on:click.self="$wire.closeCollection()">
            <form wire:submit="receiveCollection" class="wg-modal wg-sub-collection-modal">
                <div class="wg-modal-head">
                    <div>
                        <h3 class="wg-section-title">استلام القسط</h3>
                        <div class="wg-subtitle">تحصيل القسط داخل صفحة الاشتراكات دون الانتقال إلى المالية</div>
                    </div>
                    <button type="button" class="wg-modal-x" wire:click="closeCollection">×</button>
                </div>

                <div class="wg-modal-body">
                    <div class="wg-sub-collection-summary">
                        <div>
                            <span>العضو</span>
                            <strong>{{ $collectionContext['member_name'] ?? '—' }}</strong>
                            <small dir="ltr">{{ $collectionContext['member_code'] ?? '—' }}</small>
                        </div>
                        <div>
                            <span>الباقة والقسط</span>
                            <strong>{{ $collectionContext['package'] ?? '—' }}</strong>
                            <small>القسط رقم {{ $collectionContext['installment_number'] ?? '—' }} • استحقاق {{ $collectionContext['due_date'] ?? '—' }}</small>
                        </div>
                        <div>
                            <span>إجمالي المتبقي</span>
                            <strong class="wg-orange" dir="ltr">{{ $collectionContext['remaining'] ?? '—' }}</strong>
                        </div>
                    </div>

                    <div class="wg-sub-collection-grid">
                        <label>
                            <span>مبلغ القسط *</span>
                            <input type="text" inputmode="decimal" x-money wire:model="collectionAmount" class="wg-field wg-money-input" placeholder="0">
                            <small>يجب أن يطابق قيمة القسط المستحق.</small>
                            @error('collectionAmount')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                            @error('collectionInstallmentId')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                        </label>
                        <label>
                            <span>العملة</span>
                            <input wire:model="collectionCurrency" class="wg-field" readonly>
                            @error('collectionCurrency')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                        </label>
                        <label class="wg-sub-collection-full">
                            <span>طريقة الدفع *</span>
                            <select wire:model.live="collectionMethod" class="wg-select">
                                <option value="cash">نقدي</option>
                                <option value="transfer">تحويل / صرافة</option>
                            </select>
                            @error('collectionMethod')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                        </label>

                        @if($collectionMethod === 'transfer')
                            <label>
                                <span>خدمة التحويل *</span>
                                <input wire:model="collectionTransferService" class="wg-field" placeholder="اسم الصرافة أو الخدمة">
                                @error('collectionTransferService')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                            </label>
                            <label>
                                <span>رقم المرجع @if($requireTransferReference)*@else (اختياري)@endif</span>
                                <input wire:model="collectionTransferReference" class="wg-field" placeholder="رقم الحوالة أو المرجع">
                                @error('collectionTransferReference')<b class="wg-sub-field-error">{{ $message }}</b>@enderror
                            </label>
                                <label class="wg-sub-collection-full"><span>سند التحويل @if($requirePaymentProof)*@else (اختياري)@endif</span><input wire:model="collectionPaymentProof" type="file" accept=".jpg,.jpeg,.png,.pdf" class="wg-field"><small wire:loading wire:target="collectionPaymentProof">جارٍ رفع السند...</small>@error('collectionPaymentProof')<b class="wg-sub-field-error">{{ $message }}</b>@enderror</label>
                        @endif
                    </div>
                </div>

                <div class="wg-modal-foot">
                    <button type="submit" class="wg-btn wg-btn-primary" wire:loading.attr="disabled" wire:target="receiveCollection">
                        <span wire:loading.remove wire:target="receiveCollection">تأكيد استلام القسط</span>
                        <span wire:loading wire:target="receiveCollection">جارٍ التحصيل...</span>
                    </button>
                    <button type="button" class="wg-btn" wire:click="closeCollection">إلغاء</button>
                </div>
            </form>
        </div>
    @endif
    {{-- Existing subscription details --}}
    <div class="wg-modal-backdrop" x-cloak x-show="detailsOpen" x-transition.opacity x-on:keydown.escape.window="detailsOpen=false">
        <div class="wg-modal wg-modal-lg" x-on:click.outside="detailsOpen=false">
            <div class="wg-modal-head">
                <div><h3 class="wg-section-title" style="font-size:18px">تفاصيل الاشتراك</h3><div class="wg-subtitle" x-text="'اشتراك #' + (selectedSubscription?.id || '—')"></div></div>
                <button type="button" class="wg-modal-x" x-on:click="detailsOpen=false">×</button>
            </div>
            <div class="wg-modal-body">
                <div class="wg-three">
                    <div class="wg-card wg-card-pad"><div class="wg-label">العضو</div><strong x-text="selectedSubscription?.member_name || '—'"></strong><div class="wg-muted" style="font-size:10px;margin-top:4px" x-text="selectedSubscription?.member_code || '—'"></div></div>
                    <div class="wg-card wg-card-pad"><div class="wg-label">الباقة</div><strong x-text="selectedSubscription?.package || '—'"></strong><div class="wg-muted" style="font-size:10px;margin-top:4px" x-text="selectedSubscription?.duration || '—'"></div></div>
                    <div class="wg-card wg-card-pad"><div class="wg-label">الحالة</div><span class="wg-badge" :class="selectedSubscription?.status_badge" x-text="selectedSubscription?.status_label || '—'"></span></div>
                </div>

                <div class="wg-card wg-card-pad" style="margin-top:12px">
                    <div class="wg-three">
                        <div><div class="wg-label">تاريخ البداية</div><strong x-text="selectedSubscription?.start_date || '—'"></strong></div>
                        <div><div class="wg-label">تاريخ النهاية</div><strong x-text="selectedSubscription?.end_date || '—'"></strong></div>
                        <div><div class="wg-label">العملة</div><strong x-text="selectedSubscription?.currency || '—'"></strong></div>
                        <div><div class="wg-label">السعر الأصلي</div><strong dir="ltr" x-text="selectedSubscription?.original_price || '—'"></strong></div>
                        <div><div class="wg-label">الخصم</div><strong dir="ltr" x-text="selectedSubscription?.discount || '—'"></strong></div>
                        <div><div class="wg-label">السعر النهائي</div><strong class="wg-green" dir="ltr" x-text="selectedSubscription?.final_price || '—'"></strong></div>
                    </div>
                </div>

                <div class="wg-three" style="margin-top:12px">
                    <div class="wg-card wg-card-pad"><div class="wg-label">المدفوع</div><div class="wg-kpi-money wg-green" x-text="selectedSubscription?.paid || '—'"></div></div>
                    <div class="wg-card wg-card-pad"><div class="wg-label">المتبقي</div><div class="wg-kpi-money" :class="selectedSubscription?.remaining_tone" x-text="selectedSubscription?.remaining || '—'"></div></div>
                    <div class="wg-card wg-card-pad"><div class="wg-label">خطة الدفع</div><strong x-text="selectedSubscription?.payment_plan || '—'"></strong></div>
                </div>
                <div class="wg-card wg-card-pad" style="margin-top:12px" x-show="selectedSubscription?.notes"><div class="wg-label">ملاحظات</div><p style="margin:6px 0 0;white-space:pre-wrap" x-text="selectedSubscription?.notes"></p></div>

                <h4 class="wg-section-title" style="margin:16px 0 8px">الأقساط</h4>
                <div class="wg-table-wrap">
                    <table class="wg-table" style="min-width:640px">
                        <thead><tr><th>#</th><th>الاستحقاق</th><th>القيمة</th><th>الحالة</th><th>تاريخ السداد</th></tr></thead>
                        <tbody>
                            <template x-for="installment in (selectedSubscription?.installments || [])" :key="installment.number">
                                <tr><td x-text="installment.number"></td><td x-text="installment.due_date"></td><td dir="ltr" x-text="installment.amount"></td><td><span class="wg-badge" :class="installment.status_badge" x-text="installment.status_label"></span></td><td x-text="installment.paid_at"></td></tr>
                            </template>
                            <tr x-show="!(selectedSubscription?.installments?.length)"><td colspan="5" style="text-align:center;color:#7f8ea3">لا توجد أقساط.</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="wg-section-title" style="margin:16px 0 8px">سجل المدفوعات</h4>
                <div class="wg-table-wrap">
                    <table class="wg-table" style="min-width:640px">
                        <thead><tr><th>التاريخ</th><th>المبلغ</th><th>الطريقة</th><th>خدمة التحويل</th><th>المرجع</th><th>رقم الإيصال</th><th>السند</th></tr></thead>
                        <tbody>
                            <template x-for="(payment, index) in (selectedSubscription?.payments || [])" :key="payment.receipt || index">
                                <tr><td x-text="payment.paid_at"></td><td class="wg-green" dir="ltr" x-text="payment.amount"></td><td x-text="payment.method"></td><td x-text="payment.transfer_service"></td><td dir="ltr" x-text="payment.transfer_reference"></td><td dir="ltr" x-text="payment.receipt"></td><td><a x-show="payment.proof_url" :href="payment.proof_url" target="_blank" rel="noopener">عرض السند</a><span x-show="!payment.proof_url">—</span></td></tr>
                            </template>
                            <tr x-show="!(selectedSubscription?.payments?.length)"><td colspan="7" style="text-align:center;color:#7f8ea3">لا توجد دفعات.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="wg-modal-foot">
                @if(auth()->user()->hasGymPermission('payments.create') || auth()->user()->role === 'owner')
                    <button type="button" x-show="selectedSubscription?.can_receive_payment" class="wg-btn wg-btn-primary" x-on:click="detailsOpen=false; $wire.openCollection(selectedSubscription.id)">استلام القسط</button>
                @endif
                <button type="button" class="wg-btn" x-on:click="detailsOpen=false">إغلاق</button>
            </div>
        </div>
    </div>
</div>
