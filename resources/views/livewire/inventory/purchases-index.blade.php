<div class="wg-inventory-page wg-procurement-page" dir="rtl"
     x-data="{
        purchaseOpen: $wire.entangle('showCreateModal'),
        cancelId: $wire.entangle('cancelPurchaseId'),
        payment: $wire.entangle('payment_method'),
        currency: $wire.entangle('currency'),
        rows: @js($items),
        productOptions: @js($products->map(fn ($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'currency' => $product->currency,
            'stock' => (int) $product->current_quantity,
            'cost' => (float) $product->purchase_cost,
        ])->values()),
        freshRow() { return { product_id: '', quantity: 1, unit_cost: 0 }; },
        syncRows() {
            const cleaned = this.rows.map(r => ({
                product_id: r.product_id,
                quantity: r.quantity,
                unit_cost: window.wgCleanMoney ? window.wgCleanMoney(r.unit_cost) : String(r.unit_cost || 0).replace(/,/g, '')
            }));
            $wire.set('items', cleaned, false);
        },
        resetRows() { this.rows = [this.freshRow()]; this.syncRows(); },
        addRow() { this.rows.push(this.freshRow()); this.syncRows(); },
        removeRow(index) { this.rows.splice(index, 1); if (!this.rows.length) this.rows.push(this.freshRow()); this.syncRows(); },
        availableProducts() { return this.productOptions.filter(product => product.currency === this.currency); },
        pickProduct(row) {
            const product = this.productOptions.find(option => String(option.id) === String(row.product_id));
            if (product && (!row.unit_cost || Number(row.unit_cost) === 0)) {
                row.unit_cost = window.wgFormatMoney ? window.wgFormatMoney(product.cost) : product.cost;
            }
            this.syncRows();
        },
        subtotal() {
            return this.rows.reduce((sum, row) => {
                const q = parseFloat(row.quantity) || 0;
                const c = parseFloat(window.wgCleanMoney ? window.wgCleanMoney(row.unit_cost) : String(row.unit_cost || 0).replace(/,/g, '')) || 0;
                return sum + (q * c);
            }, 0);
        },
        formatMoney(value) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value || 0); }
     }"
     x-on:purchase-saved.window="purchaseOpen = false; resetRows()">
    @php
        $u = auth()->user();
        $canSales = $u->role === 'owner' || $u->hasGymPermission('sales.view') || $u->hasGymPermission('sales.create') || $u->hasGymPermission('sales.cancel') || $u->hasGymPermission('inventory.manage');
        $canProducts = $u->role === 'owner' || $u->hasGymPermission('products.view') || $u->hasGymPermission('products.manage') || $u->hasGymPermission('inventory.view') || $u->hasGymPermission('inventory.manage');
    @endphp

    @if(session('success'))<div class="wg-inv-flash">{{ session('success') }}</div>@endif
    @if($errors->any() && !$showCreateModal && !$cancelPurchaseId)<div class="wg-inv-errors">@foreach($errors->all() as $error)<span>• {{ $error }}</span>@endforeach</div>@endif

    <section class="wg-inv-commandbar wg-inv-commandbar-clean">
        <nav class="wg-inv-tabs" aria-label="أقسام المخزون">
            @if($canProducts)<a href="{{ route('inventory.products') }}" wire:navigate>المنتجات</a>@endif
            <a href="{{ route('inventory.purchases') }}" wire:navigate class="is-active">المشتريات</a>
            @if($canSales)<a href="{{ route('inventory.sales') }}" wire:navigate>المبيعات</a>@endif
        </nav>
        @if($canManage)
            <button type="button" x-on:click="purchaseOpen = true; resetRows()" wire:click="openCreate" class="wg-inv-btn wg-inv-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg><span>تسجيل شراء جديد</span>
            </button>
        @endif
    </section>

    <section class="wg-inv-kpis wg-purchase-kpis wg-purchase-kpis-useful">
        <article><i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16v13H4zM8 4h8v3M8 12h8"/></svg></i><span>بانتظار اعتماد المدير</span><strong>{{ $pendingCount }}</strong><small>شراء معلق لا يغيّر المخزون</small></article>
        <article><i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h16v13H4zM8 12l2 2 5-5"/></svg></i><span>المعتمدة هذا الشهر</span><strong>{{ $approvedMonthCount }}</strong><small>{{ number_format($receivedUnitsMonth) }} وحدة دخلت المخزون</small></article>
        <article><i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12h16M12 4v16"/></svg></i><span>مشتريات الشهر YER</span><strong>{{ number_format($approvedYER, 0) }}</strong><small>قيمة معتمدة</small></article>
        <article><i class="is-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg></i><span>مشتريات الشهر SAR</span><strong>{{ \App\Support\NumberFormatter::money($approvedSAR) }}</strong><small>قيمة معتمدة</small></article>
        <article><i class="is-cyan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M12 5v14"/></svg></i><span>قيمة معلقة</span><strong>{{ number_format($pendingYER,0) }} <small>YER</small></strong><small>{{ \App\Support\NumberFormatter::money($pendingSAR) }} SAR</small></article>
        <article><i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg></i><span>ملغاة هذا الشهر</span><strong>{{ $cancelledMonthCount }}</strong><small>لم تغيّر المخزون</small></article>
    </section>

    <section class="wg-purchase-layout wg-purchase-layout-full">
        <article class="wg-inv-card wg-purchase-ledger">
            <div class="wg-inv-card-head wg-ledger-head">
                <div><h3>سجل المشتريات</h3><span>الفاتورة والمورد والقيمة والحالة في سجل واحد قابل للمراجعة.</span></div>
                <div class="wg-inv-ledger-filters">
                    <label class="wg-inv-search-small"><input type="search" wire:model.live.debounce.300ms="search" placeholder="رقم الشراء، المورد أو الفاتورة..."><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></label>
                    <select wire:model.live="statusFilter"><option value="">كل الحالات</option><option value="pending">معلق</option><option value="approved">معتمد</option><option value="cancelled">ملغي</option></select>
                    <select wire:model.live="currencyFilter"><option value="">كل العملات</option><option value="YER">YER</option><option value="SAR">SAR</option></select>
                    <button type="button" wire:click="resetFilters" title="إعادة تعيين"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/></svg></button>
                </div>
            </div>

            <div class="wg-inv-table-scroll"><table class="wg-inv-table wg-purchase-table"><thead><tr><th>مرجع الشراء</th><th>المورد والفاتورة</th><th>المنتجات</th><th>الإجمالي</th><th>الدفع</th><th>التاريخ</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>
            @forelse($purchases as $purchase)
                @php
                    $total = $purchase->items->sum(fn($item) => (float) $item->line_total);
                    $units = $purchase->items->sum('quantity');
                    $productNames = $purchase->items->pluck('product.name')->filter()->take(2)->join('، ');
                @endphp
                <tr wire:key="purchase-{{ $purchase->id }}">
                    <td><strong class="wg-code">{{ $purchase->purchase_number }}</strong></td>
                    <td><strong>{{ $purchase->supplier_name ?: 'مورد غير محدد' }}</strong><small class="wg-subline">فاتورة: {{ $purchase->supplier_invoice ?: '—' }}</small></td>
                    <td><strong>{{ $purchase->items->count() }} منتج · {{ number_format($units) }} وحدة</strong><small class="wg-subline">{{ $productNames ?: '—' }}@if($purchase->items->count() > 2) …@endif</small></td>
                    <td class="wg-inv-money"><strong>{{ \App\Support\NumberFormatter::money($total) }}</strong> <small>{{ $purchase->currency }}</small></td>
                    <td>
                        <div style="font-weight:750;">{{ $purchase->payment_method === 'transfer' ? 'تحويل' : 'نقدي' }}</div>
                        @if($purchase->transfer_service)
                            <small class="wg-subline" dir="ltr">{{ $purchase->transfer_service }}@if($purchase->transfer_reference) · {{ $purchase->transfer_reference }}@endif</small>
                        @elseif($purchase->transfer_reference)
                            <small class="wg-subline" dir="ltr">{{ $purchase->transfer_reference }}</small>
                        @endif
                        @if($purchase->proof_path)
                            <div style="margin-top:6px;">
                                <a href="{{ route('inventory.purchases.document', $purchase) }}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:5px;color:#38bdf8;background:rgba(56,189,248,0.15);border:1px solid rgba(56,189,248,0.4);padding:4px 11px;border-radius:6px;font-size:11px;font-weight:800;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,0.25);">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span>عرض فاتورة المورد</span>
                                </a>
                            </div>
                        @endif
                    </td>
                    <td>{{ $purchase->purchase_date?->format('Y-m-d') }}@if($purchase->approved_at)<small class="wg-subline">اعتمد {{ $purchase->approved_at->timezone('Asia/Aden')->format('Y-m-d') }}</small>@endif</td>
                    <td>@if($purchase->status==='approved')<span class="wg-inv-status is-ok">معتمد</span>@elseif($purchase->status==='cancelled')<span class="wg-inv-status is-inactive">ملغي</span>@else<span class="wg-inv-status is-low">بانتظار الاعتماد</span>@endif</td>
                    <td><div class="wg-inv-row-actions">
                        @if($canManage && $purchase->status==='pending')
                            <button type="button" wire:click="approve({{ $purchase->id }})" wire:loading.attr="disabled" wire:target="approve({{ $purchase->id }})" class="is-approve" title="اعتماد وإضافة الكمية للمخزون"><svg wire:loading.remove wire:target="approve({{ $purchase->id }})" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m5 12 4 4L19 6"/></svg><span class="wg-action-spinner" wire:loading wire:target="approve({{ $purchase->id }})"></span></button>
                            <button type="button" x-on:click="cancelId = {{ $purchase->id }}" wire:click="startCancel({{ $purchase->id }})" class="is-danger" title="إلغاء"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                        @endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="8" class="wg-inv-table-empty">لا توجد عمليات شراء مطابقة.</td></tr>@endforelse
            </tbody></table></div>
            <div class="wg-inv-table-footer"><span>عرض {{ $purchases->firstItem() ?? 0 }} - {{ $purchases->lastItem() ?? 0 }} من {{ $purchases->total() }} عملية</span><div>{{ $purchases->onEachSide(1)->links() }}</div></div>
        </article>
    </section>

    <div class="wg-inv-modal-backdrop" x-cloak x-show="purchaseOpen" x-transition.opacity
         x-on:keydown.escape.window="purchaseOpen = false; $wire.closeCreate()"
         x-on:click.self="purchaseOpen = false; $wire.closeCreate()">
        <div class="wg-purchase-modal wg-purchase-modal-improved" x-show="purchaseOpen" x-transition.scale.origin.center>
            <div class="wg-inv-modal-head">
                <button type="button" x-on:click="purchaseOpen = false; $wire.closeCreate()">×</button>
                <div><h2>تسجيل شراء جديد</h2><span>سجّل فاتورة المورد والمنتجات، ثم راجعها قبل اعتماد دخول المخزون.</span></div>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 6h18l-2 12H5L3 6zM8 6l2-3h4l2 3"/></svg></i>
            </div>

            <div class="wg-modal-loading" wire:loading.flex wire:target="openCreate"><span></span><strong>جارٍ تجهيز نموذج الشراء...</strong></div>

            <form wire:submit="create" x-on:submit="syncRows()">
                <div class="wg-inv-modal-body">
                    <div class="wg-purchase-draft-state"><i>!</i><div><strong>سيُحفظ كشراء معلق</strong><span>لا تزيد الكميات ولا تتغير تكلفة المخزون إلا بعد اعتماد المدير.</span></div></div>

                    <div class="wg-purchase-meta-grid">
                        <label><span>تاريخ الشراء <b>*</b></span><input type="date" wire:model="purchase_date"></label>
                        <label><span>اسم المورد <b>*</b></span><input type="text" wire:model="supplier_name" placeholder="مثال: شركة المكملات"></label>
                        <label><span>رقم فاتورة المورد <b>*</b></span><input type="text" wire:model="supplier_invoice" placeholder="INV-10025"></label>
                        <label><span>العملة <b>*</b></span><select x-model="currency" x-on:change="resetRows()"><option value="YER">ريال يمني (YER)</option><option value="SAR">ريال سعودي (SAR)</option></select></label>
                        <label><span>طريقة الدفع <b>*</b></span><select x-model="payment"><option value="cash">نقدي</option><option value="transfer">تحويل / صرافة</option></select></label>
                        <label x-cloak x-show="payment === 'transfer'"><span>جهة التحويل <b>*</b></span><select wire:model="transfer_service"><option value="العمقي">العمقي</option><option value="الكريمي">الكريمي</option><option value="البسيري">البسيري</option></select></label><label x-cloak x-show="payment === 'transfer'"><span>مرجع التحويل <b>*</b></span><input type="text" wire:model="transfer_reference" placeholder="رقم الحوالة أو المرجع"></label>
                    </div>

                    <div class="wg-purchase-items-head"><div><strong>المنتجات المشتراة</strong><span>أضف كل منتج مع الكمية وتكلفة شراء الوحدة الفعلية.</span></div><button type="button" x-on:click="addRow()">+ إضافة سطر منتج</button></div>
                    <div class="wg-purchase-items">
                        <template x-for="(row, index) in rows" x-bind:key="index">
                            <div class="wg-purchase-item-row">
                                <label><span>المنتج <b>*</b></span><select x-model="row.product_id" x-on:change="pickProduct(row)"><option value="">اختر المنتج</option><template x-for="product in availableProducts()" x-bind:key="product.id"><option x-bind:value="product.id" x-text="product.name + ' · المخزون ' + product.stock"></option></template></select></label>
                                <label><span>الكمية <b>*</b></span><input type="number" min="1" x-model.number="row.quantity" x-on:input="syncRows()"></label>
                                <label><span>تكلفة الوحدة <b>*</b></span><div class="wg-pos-money-input"><input type="text" inputmode="decimal" x-money x-model="row.unit_cost" x-on:input="syncRows()"><span x-text="currency"></span></div></label>
                                <button type="button" x-on:click="removeRow(index)" title="حذف السطر">×</button>
                            </div>
                        </template>
                        <div class="wg-purchase-no-products" x-show="availableProducts().length === 0">لا توجد منتجات نشطة بهذه العملة. عرّف المنتج أولاً من صفحة المنتجات.</div>
                    </div>

                    <div class="wg-purchase-bottom">
                        <div>
                            <label>
                                <span>فاتورة المورد / سند الشراء <b>*</b></span>
                                <input type="file" wire:model="purchase_document" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                <small wire:loading wire:target="purchase_document" style="color:#38bdf8;font-weight:700;">⏳ جارٍ رفع المستند، يرجى الانتظار...</small>
                                @if($purchase_document)
                                    <small style="color:#22c55e;font-weight:700;display:block;margin-top:4px;">✓ تم رفع الملف بنجاح وجاهز للحفظ</small>
                                @endif
                            </label>
                        </div>
                        <label><span>ملاحظات <em>اختياري</em></span><textarea wire:model="notes" placeholder="تفاصيل الاستلام أو شرط المورد أو أي ملاحظة للمراجعة..."></textarea></label>
                        <div><span>إجمالي فاتورة الشراء</span><strong><span x-text="formatMoney(subtotal())"></span> <span x-text="currency"></span></strong><small>المجموع محسوب من الكمية × تكلفة الوحدة.</small></div>
                    </div>

                    @if($errors->any())<div class="wg-pos-error wg-modal-error-list">@foreach($errors->all() as $error)<span>• {{ $error }}</span>@endforeach</div>@endif
                </div>
                <div class="wg-inv-modal-foot">
                    <button type="button" x-on:click="purchaseOpen = false; $wire.closeCreate()" class="wg-inv-modal-cancel">إلغاء</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="create,purchase_document" class="wg-inv-modal-save">
                        <span wire:loading.remove wire:target="create,purchase_document">حفظ كشراء معلق ✓</span>
                        <span wire:loading wire:target="create">جارٍ حفظ الشراء...</span>
                        <span wire:loading wire:target="purchase_document">جارٍ رفع الفاتورة...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="wg-inv-modal-backdrop" x-cloak x-show="cancelId !== null" x-transition.opacity x-on:click.self="cancelId = null; $wire.set('cancelPurchaseId', null)">
        <form wire:submit="cancel" class="wg-inv-confirm-modal">
            <button type="button" x-on:click="cancelId = null; $wire.set('cancelPurchaseId', null)">×</button>
            <i class="is-red">!</i><h3>إلغاء عملية الشراء؟</h3>
            <p>يمكن إلغاء العملية لأنها لم تُعتمد بعد، ولن يتغير المخزون.</p>
            <textarea wire:model="cancellation_reason" placeholder="اكتب سبب الإلغاء..."></textarea>
            @error('cancellation_reason')<small class="is-error">{{ $message }}</small>@enderror
            <div><button type="button" x-on:click="cancelId = null; $wire.set('cancelPurchaseId', null)" class="wg-inv-modal-cancel">رجوع</button><button type="submit" wire:loading.attr="disabled" wire:target="cancel" class="wg-danger-action">تأكيد الإلغاء</button></div>
        </form>
    </div>
</div>