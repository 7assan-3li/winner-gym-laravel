<div dir="rtl" class="wg-finance-page wg-expenses-page" x-data="{ expenseModalOpen: @js($showCreateModal), cancelModalOpen: @js($showCancelModal), paymentMethod: @js($payment_method), newCategory: @js($createNewCategory) }" x-on:expense-created.window="expenseModalOpen = false" x-on:expense-cancelled.window="cancelModalOpen = false">
    @php
        $chartMax = max(0, ...$months->pluck('expense')->all());
        $expensePoints = [];
        foreach ($months->values() as $i => $m) {
            $x = $months->count() > 1 ? ($i * 100 / ($months->count() - 1)) : 50;
            $expenseY = $chartMax > 0 ? 92 - (($m['expense'] / $chartMax) * 76) : 84;
            $expensePoints[] = round($x, 2).','.round($expenseY, 2);
        }
        $palette = ['#ff4457','#ff9f0a','#855cff','#147dff','#10d978'];
        $stops = [];
        $cursor = 0;
        foreach ($categoryDistribution as $i => $row) {
            $start = $cursor;
            $cursor += $row['percent'];
            $stops[] = ($palette[$i % count($palette)]).' '.$start.'% '.$cursor.'%';
        }
        $categoryGradient = count($stops) ? 'conic-gradient('.implode(',', $stops).')' : '#102237';
    @endphp

    <div class="fin-top-row">
        <div class="fin-tabs">
            <a href="{{ route('expenses.index') }}" wire:navigate class="fin-tab active">المصروفات</a>
            <a href="{{ route('payments.index') }}" wire:navigate class="fin-tab">المدفوعات</a>
        </div>
        <div class="fin-actions">

            @if($canManage)
                <button type="button" class="fin-btn primary" x-on:click="expenseModalOpen = true; paymentMethod = 'cash'; newCategory = @js($categories->isEmpty()); $wire.openCreate()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>
                    إضافة مصروف
                </button>
            @endif
        </div>
    </div>

    <div class="fin-module-strip expense-strip">
        <div><strong>إدارة المصروفات</strong><span>تسجيل ومراجعة وتصنيف كل المبالغ الخارجة من النادي مع الاحتفاظ بسجل الإلغاء والتدقيق.</span></div>
        <span class="fin-module-chip">مصروفات تشغيلية</span>
    </div>

    @if(session('success'))
        <div class="fin-flash">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="fin-kpis fin-kpis-expenses">
        <section class="fin-card fin-kpi expense-primary">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">إجمالي المصروفات</div><div class="fin-kpi-value">{{ number_format((float)$stats->approved_total,0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub red">{{ number_format((int)$stats->approved_count) }} عملية معتمدة</div></div>
            <div class="fin-iconbox red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7h18v12H3zM3 10h18M8 15h4"/><path d="m17 13 3 3m0-3-3 3"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">مصروفات هذا الشهر</div><div class="fin-kpi-value">{{ number_format((float)$stats->month_total,0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub orange">الشهر الحالي</div></div>
            <div class="fin-iconbox orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">مصروفات اليوم</div><div class="fin-kpi-value">{{ number_format((float)$stats->today_total,0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub">حتى الآن</div></div>
            <div class="fin-iconbox purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">مدفوع نقدًا</div><div class="fin-kpi-value">{{ number_format((float)$stats->cash_total,0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub green">مصروفات نقدية معتمدة</div></div>
            <div class="fin-iconbox green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">تحويل / صرافة</div><div class="fin-kpi-value">{{ number_format((float)$stats->transfer_total,0) }} <small>{{ $filterCurrency }}</small></div><div class="fin-kpi-sub blue">مصروفات محولة</div></div>
            <div class="fin-iconbox"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10h18M5 10v8M9 10v8M15 10v8M19 10v8M2 20h20M12 3l9 5H3z"/></svg></div>
        </section>
        <section class="fin-card fin-kpi">
            <div class="fin-kpi-copy"><div class="fin-kpi-label">مصروفات ملغاة</div><div class="fin-kpi-value">{{ number_format((int)$stats->cancelled_count) }}</div><div class="fin-kpi-sub red">محفوظة ولا تُحذف</div></div>
            <div class="fin-iconbox red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg></div>
        </section>
    </div>

    <section class="fin-card fin-toolbar">
        <div class="fin-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="finance-search" class="fin-field" type="search" wire:model.live.debounce.400ms="search" placeholder="ابحث عن اسم مصروف، تصنيف أو مرجع تحويل...">
        </div>
        <select class="fin-select fin-category" wire:model.live="filterCategory"><option value="">كل التصنيفات</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        <select class="fin-select" wire:model.live="filterStatus"><option value="approved">المعتمدة</option><option value="cancelled">الملغاة</option><option value="all">كل الحالات</option></select>
        <select class="fin-select" wire:model.live="filterCurrency"><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select>
        <input class="fin-field fin-date-start" type="date" wire:model.live="fromDate" title="من تاريخ">
        <button class="fin-reset" type="button" wire:click="resetFilters" title="إعادة تعيين الفلاتر"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4v6h6M20 20v-6h-6"/><path d="M6 16a7 7 0 0 0 12-5M18 8A7 7 0 0 0 6 13"/></svg></button>
    </section>

    <div class="fin-layout">
        <div class="fin-main-col">
            <div class="fin-visual-grid expenses-visual-grid">
                <section class="fin-card fin-section expense-trend-card">
                    <div class="fin-section-head"><div><div class="fin-section-title">حركة المصروفات خلال آخر 6 أشهر</div><div class="fin-section-sub">متابعة اتجاه الإنفاق المعتمد فقط — بدون خلطه بالإيرادات</div></div><span class="fin-currency-pill expense-pill">{{ $filterCurrency }}</span></div>
                    @if($chartMax > 0)
                        <div class="fin-chart expense-chart">
                            <div class="fin-chart-grid"><span></span><span></span><span></span><span></span></div>
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="حركة المصروفات">
                                <polyline points="{{ implode(' ', $expensePoints) }}" fill="none" stroke="#ff596b" stroke-width="1.75" vector-effect="non-scaling-stroke"/>
                            </svg>
                            <div class="fin-chart-labels">@foreach($months as $m)<span>{{ $m['label'] }}</span>@endforeach</div>
                        </div>
                    @else
                        <div class="fin-empty-chart">لا توجد مصروفات كافية لعرض الاتجاه حتى الآن.</div>
                    @endif
                    <div class="fin-chart-legend"><span><b style="color:#ff596b">●</b> المصروفات المعتمدة</span></div>
                </section>

                <section class="fin-card fin-section expense-category-card">
                    <div class="fin-section-head"><div><div class="fin-section-title">أين يذهب الإنفاق؟</div><div class="fin-section-sub">توزيع المصروفات حسب التصنيف</div></div></div>
                    <div class="fin-donut-wrap">
                        <div class="fin-donut" style="background:{{ $categoryGradient }}"><div class="fin-donut-center">{{ number_format((float)$stats->approved_total,0) }}<small>{{ $filterCurrency }}</small></div></div>
                        <div class="fin-legend">
                            @forelse($categoryDistribution as $i => $row)
                                <div class="fin-legend-row"><span class="fin-dot" style="background:{{ $palette[$i % count($palette)] }}"></span><span>{{ $row['name'] }}</span><strong>{{ $row['percent'] }}%</strong></div>
                            @empty
                                <div class="fin-section-sub">لا توجد مصروفات معتمدة بهذه العملة.</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <section class="fin-card fin-table-card">
                <div class="fin-table-head"><div><div class="fin-section-title">سجل المصروفات</div><div class="fin-section-sub">كل العمليات المحفوظة في السجل المالي</div></div></div>
                <div class="fin-table-scroll">
                    <table class="fin-table">
                        <thead><tr><th>#</th><th>التاريخ</th><th>المصروف</th><th>التصنيف</th><th>طريقة الدفع</th><th>المبلغ</th><th>المرجع</th><th>الإيصال</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                        <tbody>
                        @forelse($expenses as $expense)
                            <tr>
                                <td>{{ $expense->id }}</td>
                                <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                                <td><strong>{{ $expense->title }}</strong>@if($expense->notes)<div class="fin-muted">{{ \Illuminate\Support\Str::limit($expense->notes, 38) }}</div>@endif</td>
                                <td>{{ $expense->category?->name ?? '—' }}</td>
                                <td>{{ $expense->payment_method === 'cash' ? 'نقدي' : 'تحويل / صرافة' }}@if($expense->transfer_service)<div class="fin-muted">{{ $expense->transfer_service }}</div>@endif</td>
                                <td><strong style="color:#ffad29">{{ number_format((float)$expense->amount,0) }} {{ $expense->currency }}</strong></td>
                                <td>{{ $expense->transfer_reference ?: '—' }}</td>
                                <td>@if($expense->receipt_path)<a class="fin-receipt-link" href="{{ route('expenses.receipt', $expense) }}" target="_blank" rel="noopener">عرض الفاتورة</a>@else<span class="fin-muted">سجل قديم بلا فاتورة</span>@endif</td>
                                <td><span class="fin-badge {{ $expense->status === 'approved' ? 'green' : 'red' }}">{{ $expense->status === 'approved' ? 'معتمد' : 'ملغي' }}</span></td>
                                <td><div class="fin-table-actions">@if($canManage && $expense->status !== 'cancelled')<button type="button" class="fin-icon-btn danger" x-on:click="cancelModalOpen = true; $wire.confirmCancel({{ $expense->id }})" title="إلغاء المصروف"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="m9 9 6 6M15 9l-6 6"/></svg></button>@else<span class="fin-muted">—</span>@endif</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="10"><div class="fin-empty">لا توجد مصروفات مطابقة للفلاتر الحالية.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="fin-table-foot"><span>عرض {{ $expenses->firstItem() ?? 0 }} - {{ $expenses->lastItem() ?? 0 }} من {{ $expenses->total() }} مصروف</span><div>{{ $expenses->onEachSide(1)->links() }}</div></div>
            </section>
        </div>

        <aside class="fin-side">
            <section class="fin-card fin-side-card expense-summary-card">
                <div class="fin-section-head"><div><div class="fin-section-title">ملخص المصروفات</div><div class="fin-section-sub">{{ $filterCurrency }} • السجل المعتمد</div></div></div>
                <div class="fin-summary">
                    <div class="fin-summary-row"><span>الإجمالي المعتمد</span><strong class="red">{{ number_format((float)$stats->approved_total,0) }}</strong></div>
                    <div class="fin-summary-row"><span>هذا الشهر</span><strong class="orange">{{ number_format((float)$stats->month_total,0) }}</strong></div>
                    <div class="fin-summary-row"><span>اليوم</span><strong>{{ number_format((float)$stats->today_total,0) }}</strong></div>
                    <div class="fin-summary-row"><span>عدد العمليات</span><strong>{{ number_format((int)$stats->approved_count) }}</strong></div>
                    <div class="fin-summary-row"><span>عمليات ملغاة</span><strong class="red">{{ number_format((int)$stats->cancelled_count) }}</strong></div>
                </div>
            </section>

            <section class="fin-card fin-side-card">
                <div class="fin-section-head"><div><div class="fin-section-title">حسب طريقة الدفع</div><div class="fin-section-sub">مصروفات معتمدة</div></div></div>
                <div class="fin-summary"><div class="fin-summary-row"><span>نقدي</span><strong>{{ number_format((float)$stats->cash_total,0) }}</strong></div><div class="fin-summary-row"><span>تحويل / صرافة</span><strong>{{ number_format((float)$stats->transfer_total,0) }}</strong></div><div class="fin-summary-row"><span>الإجمالي</span><strong class="orange">{{ number_format((float)$stats->approved_total,0) }}</strong></div></div>
            </section>

            <section class="fin-card fin-side-card">
                <div class="fin-section-head"><div><div class="fin-section-title">أحدث المصروفات</div><div class="fin-section-sub">آخر العمليات</div></div></div>
                <div class="fin-recent">@forelse($recentExpenses as $expense)<div class="fin-recent-item"><div class="fin-recent-top"><span class="fin-recent-title">{{ $expense->title }}</span><strong style="color:{{ $expense->status === 'cancelled' ? '#ff5a6d' : '#ffad29' }}">{{ number_format((float)$expense->amount,0) }}</strong></div><div class="fin-recent-meta">{{ $expense->category?->name ?? 'بدون تصنيف' }} • {{ $expense->expense_date?->format('Y-m-d') }} • {{ $expense->status === 'cancelled' ? 'ملغي' : 'معتمد' }}</div></div>@empty<div class="fin-section-sub">لا توجد عمليات بعد.</div>@endforelse</div>
            </section>


        </aside>
    </div>

    <div class="fin-modal-backdrop" x-show="expenseModalOpen" x-cloak x-on:click.self="expenseModalOpen = false; $wire.closeCreate()" x-on:keydown.escape.window="expenseModalOpen = false; $wire.closeCreate()">
        <form class="fin-modal large" wire:submit="create">
            <div class="fin-modal-head">
                <div><div class="fin-modal-title">إضافة مصروف</div><div class="fin-modal-sub">سجّل المصروف بتصنيفه وفاتورته؛ وسيبقى كل شيء محفوظًا في السجل المالي.</div></div>
                <button type="button" class="fin-close" x-on:click="expenseModalOpen = false; $wire.closeCreate()">×</button>
            </div>
            @if($errors->any())<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-grid">
                <div class="fin-form-group">
                    <label class="fin-label">اسم المصروف <span class="fin-required">*</span></label>
                    <input class="fin-field" wire:model="title" placeholder="مثال: فاتورة كهرباء" required>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label">المبلغ <span class="fin-required">*</span></label>
                    <input class="fin-field wg-money-input" type="text" inputmode="decimal" x-money wire:model="amount" placeholder="0" required>
                </div>

                <div class="fin-form-group full fin-category-builder">
                    <div class="fin-category-heading">
                        <label class="fin-label">تصنيف المصروف <span class="fin-required">*</span></label>
                        <div class="fin-category-modes">
                            @if($categories->isNotEmpty())
                                <button type="button" class="fin-category-mode" x-bind:class="{ 'active': !newCategory }" x-on:click="newCategory = false; $wire.$set('createNewCategory', false, false)">اختيار موجود</button>
                            @endif
                            <button type="button" class="fin-category-mode" x-bind:class="{ 'active': newCategory }" x-on:click="newCategory = true; $wire.$set('createNewCategory', true, false)">+ تصنيف جديد</button>
                        </div>
                    </div>
                    <div x-show="!newCategory" x-cloak>
                        <select class="fin-select" wire:model="category_id">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="fin-new-category" x-show="newCategory" x-cloak>
                        <input x-ref="newCategoryName" class="fin-field" wire:model="new_category_name" placeholder="مثال: صيانة الأجهزة" maxlength="120">
                        <div class="fin-category-help">يُحفظ التصنيف الجديد ويظهر تلقائيًا في المصروفات القادمة.</div>
                        <div class="fin-category-suggestions">
                            <span>اقتراحات:</span>
                            @foreach($categorySuggestions as $suggestion)
                                <button type="button" x-on:click="$refs.newCategoryName.value = @js($suggestion); $refs.newCategoryName.dispatchEvent(new Event('input', { bubbles: true }))">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">العملة <span class="fin-required">*</span></label>
                    <select class="fin-select" wire:model="currency" required><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select>
                </div>
                <div class="fin-form-group">
                    <label class="fin-label">تاريخ المصروف <span class="fin-required">*</span></label>
                    <input class="fin-field" type="date" wire:model="expense_date" required>
                </div>
                <div class="fin-form-group full">
                    <label class="fin-label">طريقة الدفع <span class="fin-required">*</span></label>
                    <div class="fin-payment-methods">
                        <button type="button" class="fin-method" x-bind:class="{ 'active': paymentMethod === 'cash' }" x-on:click="paymentMethod = 'cash'; $wire.$set('payment_method', 'cash', false)"><span class="fin-radio-dot"></span><span class="fin-method-copy"><strong>نقدي</strong><span>دفع نقدًا في النادي</span></span><span class="fin-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="3"/></svg></span></button>
                        <button type="button" class="fin-method" x-bind:class="{ 'active': paymentMethod === 'transfer' }" x-on:click="paymentMethod = 'transfer'; $wire.$set('payment_method', 'transfer', false)"><span class="fin-radio-dot"></span><span class="fin-method-copy"><strong>تحويل أو صرافة محلية</strong><span>تحويل عبر خدمة أو صرافة معتمدة</span></span><span class="fin-method-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10h18M5 10v8M9 10v8M15 10v8M19 10v8M2 20h20M12 3l9 5H3z"/></svg></span></button>
                    </div>
                </div>
                <div class="fin-form-group" x-show="paymentMethod === 'transfer'" x-cloak>
                    <label class="fin-label">جهة التحويل <span class="fin-required">*</span></label>
                    <select class="fin-select" wire:model="transfer_service"><option value="العمقي">العمقي</option><option value="الكريمي">الكريمي</option><option value="البسيري">البسيري</option></select>
                </div>
                <div class="fin-form-group" x-show="paymentMethod === 'transfer'" x-cloak>
                    <label class="fin-label">رقم مرجع التحويل</label>
                    <input class="fin-field" wire:model="transfer_reference" placeholder="رقم الحوالة أو المرجع">
                </div>
                <div class="fin-form-group full">
                    <label class="fin-label">سبب وتفاصيل المصروف (اختياري)</label>
                    <textarea class="fin-field fin-textarea" wire:model="notes" placeholder="مثال: صيانة جهاز المشي رقم 3 وقطع الغيار المستخدمة..."></textarea>
                </div>
                <div class="fin-form-group full">
                    <label class="fin-label">الفاتورة أو الإيصال <span class="fin-required">*</span></label>
                    <div class="fin-receipt-required">لا يمكن حفظ المصروف بدون مستند يوضح الجهة والسبب.</div>
                    <div class="fin-file">
                        <div>
                            <input type="file" wire:model="receipt" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small>إلزامي — JPG, PNG, PDF حتى 2MB • يُحفظ في التخزين الخاص</small>
                            <span class="fin-file-status" wire:loading wire:target="receipt">جاري رفع الفاتورة...</span>
                            @if($receipt)<span class="fin-file-ready">تم اختيار: {{ $receipt->getClientOriginalName() }}</span>@endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="fin-modal-foot">
                <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="create,receipt"><span wire:loading.remove wire:target="create">حفظ المصروف</span><span wire:loading wire:target="create">جاري الحفظ...</span></button>
                <button class="fin-btn" type="button" x-on:click="expenseModalOpen = false; $wire.closeCreate()">إلغاء</button>
            </div>
        </form>
    </div>
    <div class="fin-modal-backdrop" x-show="cancelModalOpen" x-cloak x-on:click.self="cancelModalOpen = false; $wire.$set('showCancelModal', false)" x-on:keydown.escape.window="cancelModalOpen = false; $wire.$set('showCancelModal', false)">
        <form class="fin-modal small" wire:submit="cancel">
            <div class="fin-modal-head"><div><div class="fin-modal-title">إلغاء المصروف</div><div class="fin-modal-sub">لن يتم حذف السجل المالي، وسيتم حفظ سبب الإلغاء</div></div><button type="button" class="fin-close" x-on:click="cancelModalOpen = false; $wire.$set('showCancelModal', false)">×</button></div>
            @if($errors->any())<div class="fin-errors">{{ $errors->first() }}</div>@endif
            <div class="fin-form-group"><label class="fin-label">سبب الإلغاء <span class="fin-required">*</span></label><textarea class="fin-field fin-textarea" wire:model="cancellation_reason" placeholder="اكتب سبب الإلغاء بوضوح..." required></textarea></div>
            <div class="fin-modal-foot"><button class="fin-btn danger" type="submit" wire:loading.attr="disabled" wire:target="cancel"><span wire:loading.remove wire:target="cancel">تأكيد الإلغاء</span><span wire:loading wire:target="cancel">جاري الإلغاء...</span></button><button class="fin-btn" type="button" x-on:click="cancelModalOpen = false; $wire.$set('showCancelModal', false)">رجوع</button></div>
        </form>
    </div>
</div>
