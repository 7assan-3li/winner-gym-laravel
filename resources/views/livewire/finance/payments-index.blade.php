<div dir="rtl" class="wg-finance-page wg-payments-page"
    x-data="{
        selectorOpen: @js($showInstallmentSelector),
        selectorLoading: false,
        payOpen: @js($showPayModal),
        selectingPayment: false,
        paymentMethod: @js($payment_method),
        payAmount: @js($amount),
        payCurrency: @js($currency),
        payMember: @js($selectedMemberName),
        reverseOpen: @js($showReverseModal),
        reverseMember: '',
        reverseReceipt: '',
        reverseAmount: '',
        refundOpen: @js($showRefundModal),
        refundMethod: @js($refund_method),
        refundMember: '',
        refundAmount: ''
    }"
    x-on:installment-selector-ready.window="selectorLoading = false; selectorOpen = true"
    x-on:payment-selected.window="selectingPayment = false; selectorOpen = false; payOpen = true; payAmount = $wire.amount; payCurrency = $wire.currency; payMember = $wire.selectedMemberName"
    x-on:payment-received.window="payOpen = false; selectingPayment = false"
    x-on:payment-reversed.window="reverseOpen = false"
    x-on:payment-refunded.window="refundOpen = false">
    @php
        $chartMax = max(0, ...$months->pluck('income')->all());
        $incomePoints = [];
        foreach ($months->values() as $i => $month) {
            $x = $months->count() > 1 ? ($i * 100 / ($months->count() - 1)) : 50;
            $incomeY = $chartMax > 0 ? 92 - (($month['income'] / $chartMax) * 76) : 84;
            $incomePoints[] = round($x, 2).','.round($incomeY, 2);
        }

        $methodTotal = max(1, $metrics['cashTotal'] + $metrics['transferTotal']);
        $cashPct = round($metrics['cashTotal'] / $methodTotal * 100, 1);
        $transferPct = round($metrics['transferTotal'] / $methodTotal * 100, 1);
        $methodGradient = $metrics['totalReceived'] > 0
            ? "conic-gradient(#10d978 0% {$cashPct}%, #147dff {$cashPct}% 100%)"
            : '#102237';
    @endphp

    <div class="fin-top-row">
        <div class="fin-tabs">
            <a href="{{ route('expenses.index') }}" wire:navigate class="fin-tab">المصروفات</a>
            <a href="{{ route('payments.index') }}" wire:navigate class="fin-tab active">المدفوعات</a>
        </div>
        <div class="fin-actions">
            @if($canPay)
                <button type="button" class="fin-btn primary" x-on:click="selectorOpen = true; selectorLoading = true; $wire.openPaySelector()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M18 4v4"/></svg>
                    استلام دفعة
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="fin-flash">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="fin-kpis fin-kpis-payments">
        <section class="fin-card fin-kpi payment-primary">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">إجمالي دفعات الاشتراكات</div><div class="fin-kpi-value">{{ number_format($metrics['totalReceived'],0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub green">{{ number_format($metrics['completedPayments']) }} دفعة مكتملة</div></div>
            <div class="fin-iconbox green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M17 9h2v2"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">المستلم هذا الشهر</div><div class="fin-kpi-value">{{ number_format($metrics['receivedThisMonth'],0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub blue">من بداية الشهر</div></div>
            <div class="fin-iconbox"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">المستلم اليوم</div><div class="fin-kpi-value">{{ number_format($metrics['receivedToday'],0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub green">تحصيل اليوم فقط</div></div>
            <div class="fin-iconbox green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">مستحق غير محصل</div><div class="fin-kpi-value">{{ number_format($metrics['pendingAmount'],0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub orange">{{ number_format($metrics['pendingCount']) }} قسط معلق</div></div>
            <div class="fin-iconbox orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16v12H4zM7 11h10M8 15h4"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">أقساط متأخرة</div><div class="fin-kpi-value">{{ number_format($metrics['overdueAmount'],0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub red">{{ number_format($metrics['overdueCount']) }} قسط يحتاج متابعة</div></div>
            <div class="fin-iconbox red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">دفعات معكوسة</div><div class="fin-kpi-value">{{ number_format($metrics['reversedPayments']) }}</div><div class="fin-kpi-sub red">محفوظة مع سبب العكس</div></div>
            <div class="fin-iconbox red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4v6h6M20 12a8 8 0 0 0-14-5M20 20v-6h-6M4 12a8 8 0 0 0 14 5"/></svg></div>
        </section>
    </div>

    <section class="fin-card fin-toolbar payments-toolbar">
        <div class="fin-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="finance-search" class="fin-field" type="search" wire:model.live.debounce.350ms="search" placeholder="ابحث بالعضو، كود العضوية، الإيصال أو مرجع التحويل...">
        </div>
        <select class="fin-select" wire:model.live="filterStatus"><option value="all">كل الحالات</option><option value="completed">مكتملة</option><option value="reversed">معكوسة</option></select>
        <select class="fin-select" wire:model.live="filterMethod"><option value="all">كل طرق الدفع</option><option value="cash">نقدي</option><option value="transfer">تحويل / صرافة</option></select>
        <select class="fin-select" wire:model.live="filterCurrency"><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select>
        <input class="fin-field fin-date-start" type="date" wire:model.live="fromDate" title="من تاريخ">
        <input class="fin-field fin-date-end" type="date" wire:model.live="toDate" title="إلى تاريخ">
        <button class="fin-reset" type="button" wire:click="resetFilters" title="إعادة تعيين الفلاتر"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M6 16a7 7 0 0 0 12-5M18 8A7 7 0 0 0 6 13"/></svg></button>
    </section>

    <div class="fin-layout">
        <div class="fin-main-col">
            <div class="fin-visual-grid payments-visual-grid">
                <section class="fin-card fin-section payment-trend-card">
                    <div class="fin-section-head"><div><div class="fin-section-title">اتجاه تحصيل الاشتراكات خلال 6 أشهر</div><div class="fin-section-sub">الدفعات المكتملة فقط حسب تاريخ الاستلام</div></div><span class="fin-currency-pill">{{ $filterCurrency }}</span></div>
                    @if($chartMax > 0)
                        <div class="fin-chart payment-chart">
                            <div class="fin-chart-grid"><span></span><span></span><span></span><span></span></div>
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="اتجاه التحصيل"><polyline points="{{ implode(' ', $incomePoints) }}" fill="none" stroke="#1b83ff" stroke-width="1.75" vector-effect="non-scaling-stroke"/></svg>
                            <div class="fin-chart-labels">@foreach($months as $month)<span>{{ $month['label'] }}</span>@endforeach</div>
                        </div>
                    @else
                        <div class="fin-empty-chart">لا توجد دفعات مكتملة كافية لعرض الاتجاه حتى الآن.</div>
                    @endif
                    <div class="fin-chart-legend"><span><b style="color:#1b83ff">●</b> التحصيل المكتمل</span></div>
                </section>

                <section class="fin-card fin-section payment-method-card">
                    <div class="fin-section-head"><div><div class="fin-section-title">التحصيل حسب طريقة الدفع</div><div class="fin-section-sub">توزيع الدفعات المكتملة بين النقد والتحويل</div></div></div>
                    <div class="fin-donut-wrap fin-payment-method-donut">
                        <div class="fin-donut" style="background:{{ $methodGradient }}"><div class="fin-donut-center">{{ number_format($metrics['totalReceived'],0) }}<small>{{ $filterCurrency }}</small></div></div>
                        <div class="fin-legend">
                            <div class="fin-legend-row"><span class="fin-dot" style="background:#10d978"></span><span>نقدي</span><strong>{{ $cashPct }}%</strong></div>
                            <div class="fin-legend-row"><span class="fin-dot" style="background:#147dff"></span><span>تحويل / صرافة</span><strong>{{ $transferPct }}%</strong></div>
                            <div class="fin-method-total"><span>النقدي</span><strong>{{ number_format($metrics['cashTotal'],0) }}</strong></div>
                            <div class="fin-method-total"><span>التحويل</span><strong>{{ number_format($metrics['transferTotal'],0) }}</strong></div>
                        </div>
                    </div>
                </section>
            </div>

            <section class="fin-card fin-table-card">
                <div class="fin-table-head"><div><div class="fin-section-title">سجل دفعات الاشتراكات</div><div class="fin-section-sub">تفاصيل القسط والإيصال والمرجع والمستلم والحالة</div></div></div>
                <div class="fin-table-scroll">
                    <table class="fin-table payments-table">
                        <thead><tr><th>#</th><th>التاريخ</th><th>العضو</th><th>القسط</th><th>رقم الإيصال</th><th>طريقة الدفع</th><th>المرجع / سبب العكس</th><th>المبلغ</th><th>استلمها</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                        <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->paid_at?->timezone('Asia/Aden')->format('Y-m-d H:i') }}</td>
                                <td><strong>{{ $payment->subscription?->member?->full_name ?? '—' }}</strong><div class="fin-muted">{{ $payment->subscription?->member?->membership_code ?? '' }}</div></td>
                                <td>{{ $payment->installment?->installment_number ? 'القسط '.$payment->installment->installment_number : '—' }}</td>
                                <td><span class="fin-receipt-number">{{ $payment->receipt_number ?: '—' }}</span></td>
                                <td>{{ $payment->payment_method === 'cash' ? 'نقدي' : 'تحويل / صرافة' }} @if($payment->payment_method === 'transfer' && $payment->transfer_service)<div class="fin-muted">{{ $payment->transfer_service }}</div>@endif</td>
                                <td>@if($payment->status === 'reversed')<span class="fin-reversal-reason">{{ $payment->reversal_reason ?: 'لم يسجل سبب' }}</span>@else<span dir="ltr">{{ $payment->transfer_reference ?: '—' }}</span>@if($payment->proof_path)<div><a class="fin-receipt-number" href="{{ route('payments.proof', $payment) }}" target="_blank" rel="noopener">عرض السند</a></div>@endif @endif</td>
                                <td><strong class="fin-payment-amount">{{ number_format((float)$payment->amount,0) }} {{ $payment->currency }}</strong></td>
                                <td>{{ $payment->creator?->name ?? '—' }}</td>
                                <td><span class="fin-badge {{ $payment->status === 'completed' ? 'green' : 'red' }}">{{ $payment->status === 'completed' ? 'مكتملة' : 'معكوسة' }}</span></td>
                                <td><div class="fin-table-actions">@if($canReverse && $payment->status === 'completed')<button type="button" class="fin-icon-btn danger" x-on:click="reverseOpen = true; reverseMember = @js($payment->subscription?->member?->full_name ?? ''); reverseReceipt = @js($payment->receipt_number ?? ''); reverseAmount = @js(number_format((float)$payment->amount,0).' '.$payment->currency); $wire.confirmReverse({{ $payment->id }})" title="عكس الدفعة"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4v6h6M20 12a8 8 0 0 0-14-5M20 20v-6h-6M4 12a8 8 0 0 0 14 5"/></svg></button>@else<span class="fin-muted">—</span>@endif</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="11"><div class="fin-empty">لا توجد دفعات مطابقة للفلاتر الحالية.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="fin-table-foot"><span>عرض {{ $payments->firstItem() ?? 0 }} - {{ $payments->lastItem() ?? 0 }} من {{ $payments->total() }} دفعة</span><div>{{ $payments->onEachSide(1)->links() }}</div></div>
            </section>
        </div>

        <aside class="fin-side">
            <section class="fin-card fin-side-card payment-summary-card">
                <div class="fin-section-head"><div><div class="fin-section-title">ملخص التحصيل</div><div class="fin-section-sub">{{ $filterCurrency }} • دفعات الاشتراكات</div></div></div>
                <div class="fin-summary">
                    <div class="fin-summary-row"><span>إجمالي المستلم</span><strong class="green">{{ number_format($metrics['totalReceived'],0) }}</strong></div>
                    <div class="fin-summary-row"><span>هذا الشهر</span><strong class="blue">{{ number_format($metrics['receivedThisMonth'],0) }}</strong></div>
                    <div class="fin-summary-row"><span>اليوم</span><strong class="green">{{ number_format($metrics['receivedToday'],0) }}</strong></div>
                    <div class="fin-summary-row"><span>نقدي</span><strong>{{ number_format($metrics['cashTotal'],0) }}</strong></div>
                    <div class="fin-summary-row"><span>تحويل / صرافة</span><strong class="blue">{{ number_format($metrics['transferTotal'],0) }}</strong></div>
                    <div class="fin-summary-row"><span>مكتملة / معكوسة</span><strong>{{ number_format($metrics['completedPayments']) }} / <span class="red">{{ number_format($metrics['reversedPayments']) }}</span></strong></div>
                </div>
            </section>

            <section id="due-installments" class="fin-card fin-side-card">
                <div class="fin-section-head"><div><div class="fin-section-title">الأقساط المطلوب تحصيلها</div><div class="fin-section-sub">المتأخرة أولًا ثم الأقرب استحقاقًا</div></div></div>
                <div class="fin-due-list">
                    @forelse($installments as $row)
                        @php($isOverdue = $row->status === 'overdue' || $row->due_date?->lt(now('Asia/Aden')->startOfDay()))
                        <div class="fin-due-item {{ $isOverdue ? 'is-overdue' : '' }}">
                            <div class="fin-due-top"><span class="fin-due-name">{{ $row->subscription?->member?->full_name }}</span><strong style="color:{{ $isOverdue ? '#ff5a6d' : '#ffad29' }}">{{ number_format((float)$row->amount,0) }}</strong></div>
                            <div class="fin-due-meta">القسط {{ $row->installment_number }} • استحقاق {{ $row->due_date?->format('Y-m-d') }} • {{ $row->subscription?->currency }}</div>
                            <span class="fin-due-status {{ $isOverdue ? 'red' : 'orange' }}">{{ $isOverdue ? 'متأخر' : 'مستحق قادم' }}</span>
                            @if($canPay)<button type="button" class="fin-due-action" x-on:click="selectorOpen = false; payOpen = true; selectingPayment = true; paymentMethod = 'cash'; payAmount = @js((string)$row->amount); payCurrency = @js($row->subscription?->currency ?? $filterCurrency); payMember = @js($row->subscription?->member?->full_name ?? ''); $wire.selectInstallment({{ $row->id }})">استلام الدفعة</button>@endif
                        </div>
                    @empty
                        <div class="fin-section-sub">لا توجد أقساط مستحقة بهذه العملة.</div>
                    @endforelse
                </div>
            </section>

            @if($canRefund)
                <section class="fin-card fin-side-card">
                    <div class="fin-section-head"><div><div class="fin-section-title">مبالغ قابلة للاسترداد</div><div class="fin-section-sub">وفق قاعدة نصف قيمة الاشتراك وبحد أقصى المدفوع</div></div></div>
                    <div class="fin-recent">
                        @forelse($refundableSubscriptions->take(4) as $subscription)
                            @php($availableRefund = min((float)$subscription->final_price / 2, (float)($subscription->completed_payments_sum ?? 0)))
                            <div class="fin-recent-item">
                                <div class="fin-recent-top"><span class="fin-recent-title">{{ $subscription->member?->full_name }}</span><strong class="fin-refund-amount">{{ number_format($availableRefund,0) }} {{ $subscription->currency }}</strong></div>
                                <div class="fin-recent-meta">المدفوع: {{ number_format((float)($subscription->completed_payments_sum ?? 0),0) }} • المتاح للاسترداد: {{ number_format($availableRefund,0) }}</div>
                                <button type="button" class="fin-due-action" x-on:click="refundOpen = true; refundMethod = 'cash'; refundMember = @js($subscription->member?->full_name ?? ''); refundAmount = @js(number_format($availableRefund,0).' '.$subscription->currency); $wire.openRefund({{ $subscription->id }})">فتح الاسترداد</button>
                            </div>
                        @empty
                            <div class="fin-section-sub">لا توجد مبالغ قابلة للاسترداد الآن.</div>
                        @endforelse
                    </div>
                </section>
            @endif
        </aside>
    </div>

    <div class="fin-modal-backdrop" x-show="selectorOpen" x-cloak x-on:click.self="selectorOpen = false; selectorLoading = false; $wire.$set('showInstallmentSelector', false, false)" x-on:keydown.escape.window="selectorOpen = false; selectorLoading = false; $wire.$set('showInstallmentSelector', false, false)">
        <div class="fin-modal large">
            <div class="fin-modal-head"><div><div class="fin-modal-title">استلام دفعة</div><div class="fin-modal-sub">اختر قسطًا مستحقًا؛ المتأخر يظهر أولًا</div></div><button type="button" class="fin-close" x-on:click="selectorOpen = false; selectorLoading = false; $wire.$set('showInstallmentSelector', false, false)">×</button></div>
            <div class="fin-modal-loading" x-show="selectorLoading"><span></span><strong>جاري تحميل الأقساط المستحقة...</strong></div>
            <div x-show="!selectorLoading" x-cloak>
                <div class="fin-search-wrap" style="margin-bottom:12px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input class="fin-field" wire:model.live.debounce.300ms="installmentSearch" placeholder="ابحث باسم العضو، الهاتف أو كود العضوية..."></div>
                <div class="fin-selector-list">
                    @forelse($selectorInstallments as $row)
                        @php($isOverdue = $row->status === 'overdue' || $row->due_date?->lt(now('Asia/Aden')->startOfDay()))
                        <div class="fin-selector-item {{ $isOverdue ? 'is-overdue' : '' }}">
                            <div><div class="fin-selector-member">{{ $row->subscription?->member?->full_name ?? '—' }}</div><div class="fin-selector-meta">{{ $row->subscription?->member?->membership_code ?? '' }} • القسط {{ $row->installment_number }} • الاستحقاق {{ $row->due_date?->format('Y-m-d') }} • {{ $isOverdue ? 'متأخر' : 'معلق' }}</div><div class="fin-selector-amount">{{ number_format((float)$row->amount,0) }} {{ $row->subscription?->currency }}</div></div>
                            <button type="button" class="fin-btn primary" x-on:click="selectorOpen = false; payOpen = true; selectingPayment = true; paymentMethod = 'cash'; payAmount = @js((string)$row->amount); payCurrency = @js($row->subscription?->currency ?? $filterCurrency); payMember = @js($row->subscription?->member?->full_name ?? ''); $wire.selectInstallment({{ $row->id }})">اختيار واستلام</button>
                        </div>
                    @empty
                        <div class="fin-empty">لا توجد أقساط مستحقة مطابقة للبحث الحالي.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="fin-modal-backdrop" x-show="payOpen" x-cloak x-on:click.self="payOpen = false; selectingPayment = false; $wire.$set('showPayModal', false, false)" x-on:keydown.escape.window="payOpen = false; selectingPayment = false; $wire.$set('showPayModal', false, false)">
        <form class="fin-modal" wire:submit="pay">
            <div class="fin-modal-head"><div><div class="fin-modal-title">تأكيد استلام الدفعة</div><div class="fin-modal-sub">راجع العضو والمبلغ ثم اختر طريقة الدفع</div></div><button type="button" class="fin-close" x-on:click="payOpen = false; selectingPayment = false; $wire.$set('showPayModal', false, false)">×</button></div>
            @if($errors->any())<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-payment-context"><div><span>العضو</span><strong x-text="payMember || '—'"></strong></div><div><span>المبلغ المستحق</span><strong><b x-text="Number(payAmount || 0).toLocaleString('en-US')"></b> <small x-text="payCurrency"></small></strong></div></div>
            <div class="fin-form-grid">
                <div class="fin-form-group full"><label class="fin-label">طريقة الدفع <span class="fin-required">*</span></label><div class="fin-payment-methods"><button type="button" class="fin-method" x-bind:class="{ 'active': paymentMethod === 'cash' }" x-on:click="paymentMethod = 'cash'; $wire.$set('payment_method', 'cash', false)"><span class="fin-radio-dot"></span><span class="fin-method-copy"><strong>نقدي</strong><span>استلام نقدي في النادي</span></span><span class="fin-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></span></button><button type="button" class="fin-method" x-bind:class="{ 'active': paymentMethod === 'transfer' }" x-on:click="paymentMethod = 'transfer'; $wire.$set('payment_method', 'transfer', false)"><span class="fin-radio-dot"></span><span class="fin-method-copy"><strong>تحويل / صرافة</strong><span>يتطلب رقم مرجع</span></span><span class="fin-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10h18M5 10v8M9 10v8M15 10v8M19 10v8M2 20h20M12 3l9 5H3z"/></svg></span></button></div></div>
                <div class="fin-form-group" x-show="paymentMethod === 'transfer'" x-cloak><label class="fin-label">خدمة التحويل <span class="fin-required">*</span></label><input class="fin-field" wire:model="transfer_service" placeholder="اسم الصرافة أو الخدمة"></div>
                <div class="fin-form-group" x-show="paymentMethod === 'transfer'" x-cloak><label class="fin-label">مرجع التحويل @if($requireTransferReference)<span class="fin-required">*</span>@else<span class="fin-muted">(اختياري)</span>@endif</label><input class="fin-field" wire:model="transfer_reference" placeholder="رقم الحوالة"></div>
                <div class="fin-form-group full" x-show="paymentMethod === 'transfer'" x-cloak><label class="fin-label">سند التحويل @if($requirePaymentProof)<span class="fin-required">*</span>@else<span class="fin-muted">(اختياري)</span>@endif</label><input class="fin-field" type="file" wire:model="payment_proof" accept=".jpg,.jpeg,.png,.pdf"><div class="fin-muted" wire:loading wire:target="payment_proof">جارٍ رفع السند...</div></div>
            </div>
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" x-bind:disabled="selectingPayment" wire:loading.attr="disabled" wire:target="pay,payment_proof"><span x-show="selectingPayment">جاري تحضير القسط...</span><span x-show="!selectingPayment" wire:loading.remove wire:target="pay">تأكيد استلام الدفعة</span><span wire:loading wire:target="pay">جاري الحفظ...</span></button><button type="button" class="fin-btn" x-on:click="payOpen = false; selectingPayment = false; $wire.$set('showPayModal', false, false)">إلغاء</button></div>
        </form>
    </div>

    <div class="fin-modal-backdrop" x-show="reverseOpen" x-cloak x-on:click.self="reverseOpen = false; $wire.$set('showReverseModal', false, false)" x-on:keydown.escape.window="reverseOpen = false; $wire.$set('showReverseModal', false, false)">
        <form class="fin-modal small" wire:submit="reverse">
            <div class="fin-modal-head"><div><div class="fin-modal-title">عكس دفعة</div><div class="fin-modal-sub">سيبقى السجل محفوظًا مع سبب العكس</div></div><button type="button" class="fin-close" x-on:click="reverseOpen = false; $wire.$set('showReverseModal', false, false)">×</button></div>
            @if($errors->any())<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-payment-context compact"><div><span>العضو</span><strong x-text="reverseMember || '—'"></strong></div><div><span>الإيصال والمبلغ</span><strong><b x-text="reverseReceipt || '—'"></b> • <b x-text="reverseAmount"></b></strong></div></div>
            <div class="fin-form-group"><label class="fin-label">سبب العكس <span class="fin-required">*</span></label><textarea class="fin-field fin-textarea" wire:model="reversal_reason" placeholder="اكتب سبب العكس بوضوح..." required></textarea></div>
            <div class="fin-modal-foot"><button class="fin-btn danger" type="submit" wire:loading.attr="disabled" wire:target="reverse"><span wire:loading.remove wire:target="reverse">تأكيد العكس</span><span wire:loading wire:target="reverse">جاري العكس...</span></button><button type="button" class="fin-btn" x-on:click="reverseOpen = false; $wire.$set('showReverseModal', false, false)">رجوع</button></div>
        </form>
    </div>

    <div class="fin-modal-backdrop" x-show="refundOpen" x-cloak x-on:click.self="refundOpen = false; $wire.$set('showRefundModal', false, false)" x-on:keydown.escape.window="refundOpen = false; $wire.$set('showRefundModal', false, false)">
        <form class="fin-modal" wire:submit="refund">
            <div class="fin-modal-head"><div><div class="fin-modal-title">استرداد اشتراك</div><div class="fin-modal-sub">يطبق النظام قاعدة الاسترداد المعتمدة تلقائيًا</div></div><button type="button" class="fin-close" x-on:click="refundOpen = false; $wire.$set('showRefundModal', false, false)">×</button></div>
            @if($errors->any())<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-payment-context"><div><span>العضو</span><strong x-text="refundMember || '—'"></strong></div><div><span>المبلغ المتوقع</span><strong x-text="refundAmount || '—'"></strong></div></div>
            <div class="fin-form-grid">
                <div class="fin-form-group full"><label class="fin-label">طريقة الاسترداد <span class="fin-required">*</span></label><select class="fin-select" x-model="refundMethod" x-on:change="$wire.$set('refund_method', refundMethod, false)"><option value="cash">نقدي</option><option value="transfer">تحويل أو صرافة محلية</option></select></div>
                <div class="fin-form-group" x-show="refundMethod === 'transfer'" x-cloak><label class="fin-label">خدمة التحويل <span class="fin-required">*</span></label><input class="fin-field" wire:model="refund_transfer_service"></div>
                <div class="fin-form-group" x-show="refundMethod === 'transfer'" x-cloak><label class="fin-label">مرجع التحويل @if($requireTransferReference)<span class="fin-required">*</span>@endif</label><input class="fin-field" wire:model="refund_transfer_reference"></div>
                <div class="fin-form-group full" x-show="refundMethod === 'transfer'" x-cloak><label class="fin-label">سند الاسترداد @if($requirePaymentProof)<span class="fin-required">*</span>@else<span class="fin-muted">(اختياري)</span>@endif</label><input class="fin-field" type="file" wire:model="refund_proof" accept=".jpg,.jpeg,.png,.pdf"></div>
                <div class="fin-form-group full"><label class="fin-label">سبب الاسترداد <span class="fin-required">*</span></label><textarea class="fin-field fin-textarea" wire:model="refund_reason" placeholder="اكتب سبب الاسترداد بوضوح..." required></textarea></div>
            </div>
            <div class="fin-modal-foot"><button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="refund"><span wire:loading.remove wire:target="refund">تنفيذ الاسترداد</span><span wire:loading wire:target="refund">جاري التنفيذ...</span></button><button type="button" class="fin-btn" x-on:click="refundOpen = false; $wire.$set('showRefundModal', false, false)">إلغاء</button></div>
        </form>
    </div>
</div>
