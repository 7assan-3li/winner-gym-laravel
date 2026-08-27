<div class="wg-inventory-page wg-sales-page" id="sales-pos-top" dir="rtl"
     x-data="{ payment: $wire.entangle('payment_method') }">
    @php
        $u = auth()->user();
        $canProducts = $u->role === 'owner' || $u->hasGymPermission('products.view') || $u->hasGymPermission('products.manage') || $u->hasGymPermission('inventory.view') || $u->hasGymPermission('inventory.manage');
        $canPurchases = $u->role === 'owner' || $u->hasGymPermission('purchases.view') || $u->hasGymPermission('purchases.manage') || $u->hasGymPermission('inventory.manage');
    @endphp

    @if(session('success'))<div class="wg-pos-sale-success"><span>✓</span><div><strong>تم إصدار الفاتورة بنجاح</strong><small>{{ session('success') }}</small></div>@if($lastCompletedSaleId)<a href="{{ route('inventory.sales.receipt', $lastCompletedSaleId) }}" target="_blank">طباعة الإيصال</a>@endif</div>@endif
    @if($errors->any() && !$cancelSaleId)<div class="wg-inv-errors">@foreach($errors->all() as $error)<span>• {{ $error }}</span>@endforeach</div>@endif
    <section class="wg-inv-commandbar wg-inv-commandbar-clean wg-sales-commandbar">
        <nav class="wg-inv-tabs" aria-label="أقسام المخزون">
            @if($canProducts)<a href="{{ route('inventory.products') }}" wire:navigate>المنتجات</a>@endif
            @if($canPurchases)<a href="{{ route('inventory.purchases') }}" wire:navigate>المشتريات</a>@endif
            <a href="{{ route('inventory.sales') }}" wire:navigate class="is-active">المبيعات</a>
        </nav>

    </section>

    @if($canCreate)
    <section class="wg-pos-workspace">
        <article class="wg-inv-card wg-sales-products-panel">
            <div class="wg-sales-panel-head"><div><h3>منتجات متجر WINNER GYM</h3><span>{{ number_format($products->count()) }} منتج متاح · اضغط على البطاقة لإضافتها إلى الفاتورة</span></div><label class="wg-inv-search-small wg-sales-product-search"><input type="search" wire:model.live.debounce.250ms="productSearch" placeholder="ابحث بالاسم أو امسح الباركود..." autofocus><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></label></div>
            <div class="wg-sales-product-grid">
                @forelse($products as $product)
                <button type="button" x-data="{ adding: false }" x-on:click="adding = true; $wire.addProduct({{ $product->id }}).finally(() => adding = false)" x-bind:disabled="adding" x-bind:class="{ 'is-adding': adding }" class="wg-sales-product-card" aria-label="إضافة {{ $product->name }} إلى الفاتورة">
                    <span class="wg-sales-product-image">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4"/></svg>
                        @if($product->image_path)<img src="{{ route('inventory.product-image', $product) }}" alt="{{ $product->name }}" onerror="this.style.display='none'">@endif
                    </span>
                    <span class="wg-sales-product-copy">
                        <strong>{{ $product->name }}</strong>
                        <small>{{ $product->category?->name ?? 'بدون تصنيف' }}@if($product->barcode) · {{ $product->barcode }}@endif</small>
                        <span class="wg-sales-stock-badge"><b>متوفر</b> {{ number_format($product->current_quantity) }} وحدة</span>
                    </span>
                    <span class="wg-sales-product-price"><small>سعر البيع</small>{{ number_format((float)$product->selling_price,0) }} <em>{{ $product->currency }}</em></span>
                    <i><b x-show="!adding">＋</b><b x-cloak x-show="adding" class="wg-mini-spinner"></b><em x-text="adding ? 'جارٍ...' : 'إضافة'"></em></i>
                </button>
                @empty<div class="wg-pos-empty"><strong>لا توجد منتجات متوفرة للبيع</strong><span>أضف كمية من صفحة المشتريات أو جرّب عبارة بحث أخرى.</span></div>@endforelse
            </div>
        </article>

        <aside class="wg-inv-card wg-sales-cart-panel">
            <div class="wg-sales-cart-title"><div><h3>فاتورة البيع</h3><span>{{ collect($cart)->sum('quantity') }} قطعة في السلة</span></div>@if(count($cart))<button type="button" wire:click="clearCart">تفريغ</button>@endif</div>
            @error('cart')<div class="wg-pos-error">{{ $message }}</div>@enderror

            <div class="wg-sales-cart-items">
                @forelse($cart as $row)
                <div class="wg-sales-cart-row">
                    <div><strong>{{ $row['name'] }}</strong><small>{{ number_format($row['price'],0) }} {{ $row['currency'] }} · متوفر {{ $row['stock'] }}</small></div>
                    <div class="wg-pos-qty"><button type="button" wire:click="decreaseQuantity({{ $row['id'] }})">−</button><span>{{ $row['quantity'] }}</span><button type="button" wire:click="increaseQuantity({{ $row['id'] }})">+</button></div>
                    <b>{{ number_format($row['price']*$row['quantity'],0) }} <small>{{ $row['currency'] }}</small></b>
                    <button type="button" wire:click="removeProduct({{ $row['id'] }})" class="wg-pos-remove">×</button>
                </div>
                @empty<div class="wg-pos-cart-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18v12H3zM7 10h10"/></svg><strong>السلة فارغة</strong><span>اختر منتجاً من الجهة المقابلة.</span></div>@endforelse
            </div>

            <div class="wg-pos-payment-choice">
                <span>طريقة الدفع</span>
                <button type="button" x-on:click="payment = 'cash'" x-bind:class="{ 'is-active': payment === 'cash' }">نقدي</button>
                <button type="button" x-on:click="payment = 'transfer'" x-bind:class="{ 'is-active': payment === 'transfer' }">تحويل</button>
            </div>
            <div class="wg-pos-transfer-grid" x-cloak x-show="payment === 'transfer'" x-transition>
                <label class="wg-pos-transfer-field"><span>جهة التحويل <b>*</b></span><select wire:model="transfer_service"><option value="العمقي">العمقي</option><option value="الكريمي">الكريمي</option><option value="البسيري">البسيري</option></select></label>
                <label class="wg-pos-transfer-field"><span>رقم الحوالة / المرجع <b>*</b></span><input type="text" wire:model="transfer_reference" placeholder="أدخل المرجع كما في الإيصال"></label>
                <label class="wg-pos-transfer-field">
                    <span>سند التحويل</span>
                    <input type="file" wire:model="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small wire:loading wire:target="payment_proof" style="color:#38bdf8;font-weight:750;">⏳ جارٍ رفع السند، يرجى الانتظار...</small>
                    @if($payment_proof)
                        <small style="color:#22c55e;font-weight:750;display:block;margin-top:3px;">✓ تم رفع السند بنجاح وجاهز للحفظ</small>
                    @endif
                </label>
            </div>
            <details class="wg-pos-customer-details">
                <summary>بيانات العميل والخصم <small>اختياري</small></summary>
                <div class="wg-sales-checkout-fields">
                    <label><span>عضو النادي</span><select wire:model="member_id"><option value="">عميل عادي / غير عضو</option>@foreach($members as $member)<option value="{{ data_get($member, 'id') }}">{{ data_get($member, 'full_name') }} · {{ data_get($member, 'membership_code') }}</option>@endforeach</select></label>
                    <label><span>اسم المشتري</span><input type="text" wire:model="customer_name" placeholder="مثال: محمد أحمد"></label>
                    @if($canDiscount)<label><span>خصم رسمي</span><div class="wg-pos-money-input"><input type="number" min="0" step="0.01" wire:model.blur="discount_value"><span>{{ $cartCurrency }}</span></div></label>@endif
                </div>
            </details>

            <div class="wg-sales-total-box"><div><span>الإجمالي</span><strong>{{ number_format($cartSubtotal,0) }} {{ $cartCurrency }}</strong></div>@if($cartDiscount>0)<div><span>الخصم</span><strong class="is-orange">- {{ number_format($cartDiscount,0) }} {{ $cartCurrency }}</strong></div>@endif<div class="is-total"><span>المبلغ المطلوب</span><strong>{{ number_format($cartTotal,0) }} {{ $cartCurrency }}</strong></div></div>
            <button type="button" wire:click="completeSale" wire:loading.attr="disabled" wire:target="completeSale,payment_proof" class="wg-sales-pay-button" @disabled(empty($cart))>
                <span wire:loading.remove wire:target="completeSale,payment_proof">إصدار الفاتورة وتأكيد البيع</span>
                <span wire:loading wire:target="completeSale">جارٍ إصدار الفاتورة...</span>
                <span wire:loading wire:target="payment_proof">جارٍ رفع السند...</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m5 12 4 4L19 6"/></svg>
            </button>
            <div class="wg-sales-auto-note">بعد التأكيد: <strong>المخزون ينقص</strong> · <strong>المبيعات تُحفظ</strong> · <strong>الإيراد يظهر في المالية</strong>.</div>
        </aside>
    </section>
    @endif

    <section class="wg-inv-kpis wg-sales-kpis wg-sales-kpis-restored" aria-label="ملخص المبيعات">
        <article><i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg></i><span>عمليات البيع اليوم</span><strong>{{ $todayCount }}</strong><small>فاتورة مكتملة</small></article>
        <article><i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16v12H4zM7 10h10M7 14h5"/></svg></i><span>مبيعات اليوم YER</span><strong>{{ number_format($todayYER,0) }}</strong><small>إيراد المنتجات</small></article>
        <article><i class="is-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 6h16v12H4zM7 10h10M7 14h5"/></svg></i><span>مبيعات الشهر YER</span><strong>{{ number_format($monthYER,0) }}</strong><small>{{ number_format($monthUnits) }} وحدة مباعة</small></article>
        <article><i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 19l5-6 4 3 7-10"/></svg></i><span>ربح المنتجات YER</span><strong>{{ number_format($profitYER,0) }}</strong><small>بعد التكلفة والخصم</small></article>
        <article><i class="is-cyan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg></i><span>مبيعات الشهر SAR</span><strong>{{ \App\Support\NumberFormatter::money($monthSAR) }}</strong><small>ربح {{ \App\Support\NumberFormatter::money($profitSAR) }} SAR</small></article>
    </section>

    <div class="wg-sales-history-heading" id="sales-history"><div><h2>سجل المبيعات</h2><p>راجع الفواتير واطبعها أو ألغِ العملية حسب الصلاحية.</p></div><a href="#sales-pos-top">العودة للفاتورة</a></div>
    <article class="wg-inv-card wg-sales-history">
        <div class="wg-inv-card-head wg-ledger-head"><div><h3>سجل المبيعات</h3><span>جميع الفواتير المكتملة والملغاة محفوظة للمراجعة.</span></div><div class="wg-inv-ledger-filters"><label class="wg-inv-search-small"><input type="search" wire:model.live.debounce.300ms="historySearch" placeholder="رقم البيع، العميل أو العضو..."><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></label><select wire:model.live="statusFilter"><option value="">كل الحالات</option><option value="completed">مكتمل</option><option value="cancelled">ملغي</option></select><select wire:model.live="currencyFilter"><option value="">كل العملات</option><option value="YER">YER</option><option value="SAR">SAR</option></select><button type="button" wire:click="resetHistoryFilters"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/></svg></button></div></div>
        <div class="wg-inv-table-scroll"><table class="wg-inv-table wg-sales-table"><thead><tr><th>رقم البيع</th><th>العميل</th><th>المنتجات</th><th>الإجمالي</th><th>طريقة الدفع</th><th>التاريخ</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>
        @forelse($sales as $sale)
            @php $customer = $sale->member?->full_name ?: ($sale->customer_name ?: 'عميل نقدي'); $units=$sale->items->sum('quantity'); @endphp
            <tr><td><strong class="wg-code">{{ $sale->sale_number }}</strong></td><td><strong>{{ $customer }}</strong>@if($sale->member)<small class="wg-subline">{{ $sale->member->membership_code }}</small>@endif</td><td><strong>{{ $sale->items->count() }} منتج</strong><small class="wg-subline">{{ number_format($units) }} وحدة</small></td><td class="wg-inv-money"><strong>{{ \App\Support\NumberFormatter::money($sale->total_amount) }}</strong> <small>{{ $sale->currency }}</small>@if((float)$sale->discount_amount>0)<small class="wg-subline is-orange">خصم {{ \App\Support\NumberFormatter::money($sale->discount_amount) }}</small>@endif</td><td><div>{{ $sale->payment_method==='transfer' ? 'تحويل' : 'نقدي' }}</div>@if($sale->transfer_reference)<small class="wg-subline" dir="ltr">{{ $sale->transfer_service ? $sale->transfer_service.' · ' : '' }}{{ $sale->transfer_reference }}</small>@endif @if($sale->proof_path)<div style="margin-top:4px"><a href="{{ route('inventory.sales.proof', $sale) }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;color:#38bdf8;font-size:10px;font-weight:700;text-decoration:none;background:rgba(56,189,248,0.12);padding:2px 7px;border-radius:4px;border:1px solid rgba(56,189,248,0.3);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>عرض السند</a></div>@endif</td><td>{{ optional($sale->sold_at)->timezone('Asia/Aden')->format('Y-m-d h:i A') }}</td><td>@if($sale->status==='completed')<span class="wg-inv-status is-ok">مكتمل</span>@else<span class="wg-inv-status is-inactive">ملغي</span>@endif</td><td><div class="wg-inv-row-actions"><a href="{{ route('inventory.sales.receipt', $sale) }}" target="_blank" class="wg-pos-print-action" title="طباعة الإيصال"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M7 8V3h10v5M7 17h10v4H7z"/><path d="M5 17H3v-7h18v7h-2"/></svg></a>@if($canCancel && $sale->status==='completed')<button type="button" wire:click="startCancel({{ $sale->id }})" class="is-danger" title="إلغاء البيع وإرجاع المخزون"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 6 12 12M18 6 6 18"/></svg></button>@endif</div></td></tr>
        @empty<tr><td colspan="8" class="wg-inv-table-empty">لا توجد مبيعات مطابقة.</td></tr>@endforelse
        </tbody></table></div>
        <div class="wg-inv-table-footer"><span>عرض {{ $sales->firstItem() ?? 0 }} - {{ $sales->lastItem() ?? 0 }} من {{ $sales->total() }} عملية بيع</span><div>{{ $sales->onEachSide(1)->links() }}</div></div>
    </article>

    @if($cancelSaleId)
    <div class="wg-inv-modal-backdrop" wire:click.self="$set('cancelSaleId', null)"><form wire:submit="cancel" class="wg-inv-confirm-modal"><button type="button" wire:click="$set('cancelSaleId', null)">×</button><i class="is-red">!</i><h3>إلغاء عملية البيع؟</h3><p>سيتم إرجاع كل الكميات إلى المخزون، وتبقى الفاتورة محفوظة بحالة «ملغي».</p><textarea wire:model="cancellation_reason" placeholder="اكتب سبب الإلغاء..."></textarea>@error('cancellation_reason')<small class="is-error">{{ $message }}</small>@enderror<div><button type="button" wire:click="$set('cancelSaleId', null)" class="wg-inv-modal-cancel">رجوع</button><button type="submit" class="wg-danger-action">تأكيد الإلغاء</button></div></form></div>
    @endif
</div>
