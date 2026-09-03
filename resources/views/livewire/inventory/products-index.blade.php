<div class="wg-inventory-page wg-products-focused wg-products-finance-style" dir="rtl"
     x-data="{ createOpen: $wire.entangle('showCreateModal'), editOpen: $wire.entangle('showEditModal'), newCategory: @js($categories->isEmpty()) }"
     x-on:product-saved.window="createOpen = false; editOpen = false">
    @if (session('success'))
        <div class="wg-inv-flash">{{ session('success') }}</div>
    @endif

    @if ($errors->any() && !$showCreateModal && !$showEditModal)
        <div class="wg-inv-errors">
            @foreach($errors->all() as $error)<span>• {{ $error }}</span>@endforeach
        </div>
    @endif

    <section class="wg-inv-commandbar">
        <nav class="wg-inv-tabs" aria-label="أقسام المخزون">
            <a href="{{ route('inventory.products') }}" wire:navigate class="is-active">المنتجات</a>
            @if($canViewPurchases)<a href="{{ route('inventory.purchases') }}" wire:navigate>المشتريات</a>@endif
            @if($canViewSales)<a href="{{ route('inventory.sales') }}" wire:navigate>المبيعات</a>@endif
        </nav>

        <div class="wg-inv-actions-primary">
            @if($canManageProducts)
                <button type="button" x-on:click="createOpen = true; newCategory = @js($categories->isEmpty())" wire:click="openCreate" class="wg-inv-btn wg-inv-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14M5 12h14"/></svg>
                    <span>إضافة منتج جديد</span>
                </button>
            @endif
        </div>
    </section>

    <section class="wg-inv-kpis">
        <article class="wg-inv-kpi">
            <div><span>إجمالي المنتجات</span><strong>{{ number_format($totalProducts) }}</strong><small>منتج مسجل</small></div>
            <i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4M12 11v10"/></svg></i>
        </article>
        <article class="wg-inv-kpi">
            <div><span>إجمالي المخزون</span><strong>{{ number_format($totalUnits) }}</strong><small class="wg-green">وحدة متاحة</small></div>
            <i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4"/></svg></i>
        </article>
        <article class="wg-inv-kpi wg-inv-money-kpi">
            <div><span>قيمة المخزون</span><strong>{{ number_format($inventoryValueYER, 0) }}</strong><small>YER</small>@if($inventoryValueSAR > 0)<em>{{ \App\Support\NumberFormatter::money($inventoryValueSAR) }} SAR</em>@endif</div>
            <i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.7-.8-1.8-1.2-3-1.2-1.7 0-3 1-3 2.4 0 1.5 1.3 2.1 3 2.4s3 .9 3 2.4c0 1.4-1.3 2.4-3 2.4-1.3 0-2.5-.5-3.3-1.4M12 5.5v13"/></svg></i>
        </article>
        <article class="wg-inv-kpi">
            <div><span>منتجات منخفضة</span><strong>{{ number_format($lowStockCount) }}</strong><small class="wg-red">تحتاج متابعة</small></div>
            <i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3 2.8 20h18.4L12 3zM12 9v5M12 17h.01"/></svg></i>
        </article>
    </section>

    <section class="wg-inv-middle-grid">
        <article class="wg-inv-card wg-inv-distribution-card">
            <h3>توزيع المنتجات حسب الفئة</h3>
            <div class="wg-inv-distribution-body">
                <div class="wg-inv-donut" style="background:{{ $categoryGradient }}"><div><strong>{{ number_format($totalProducts) }}</strong><span>منتج</span></div></div>
                <div class="wg-inv-distribution-list">
                    @forelse($distribution as $row)
                        <div><span><i style="background:{{ $row['color'] }}"></i>{{ $row['category']->name }}</span><strong>{{ $row['count'] }} <em>({{ number_format($row['percent'],1) }}%)</em></strong></div>
                    @empty
                        <p>لا توجد بيانات فئات بعد.</p>
                    @endforelse
                </div>
            </div>
        </article>

        <aside class="wg-inv-card wg-inv-alert-card">
            <div class="wg-inv-card-head"><h3>تنبيهات المخزون</h3><span>{{ $lowStockCount }}</span></div>
            <div class="wg-inv-alert-list">
                @forelse($lowStockAlerts as $product)
                    <div class="wg-inv-alert-item">
                        <div class="wg-inv-product-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4"/></svg></div>
                        <div><strong>{{ $product->name }}</strong><span>المخزون: {{ number_format($product->current_quantity) }} · الحد الأدنى: {{ number_format($product->minimum_quantity) }}</span></div>
                        <em>منخفض</em>
                    </div>
                @empty
                    <div class="wg-inv-alert-empty">لا توجد تنبيهات مخزون حالياً.</div>
                @endforelse
            </div>
            <button type="button" wire:click="$set('statusFilter','low')" class="wg-inv-alert-all">عرض المنتجات المنخفضة ←</button>
        </aside>
    </section>

    <section class="wg-inv-bottom-grid">
        <article class="wg-inv-card wg-inv-products-card">
            <div class="wg-inv-products-head">
                <div><h3>قائمة المنتجات</h3><p>إدارة المنتجات والأسعار وحالة المخزون من مكان واحد</p></div>
                <div class="wg-inv-filters">
                    <div class="wg-inv-search-field">
                        <input id="inventory-search" type="search" wire:model.live.debounce.300ms="search" placeholder="ابحث عن منتج أو باركود...">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    </div>
                    <select wire:model.live="categoryFilter"><option value="">كل الفئات</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                    <select wire:model.live="statusFilter"><option value="">كل الحالات</option><option value="active">نشط</option><option value="low">مخزون منخفض</option><option value="inactive">غير نشط</option></select>
                    <button wire:click="resetFilters" type="button" title="إعادة تعيين"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6"/></svg></button>
                </div>
            </div>

            <div class="wg-inv-table-scroll">
                <table class="wg-inv-table">
                    <thead><tr><th>#</th><th>المنتج</th><th>الفئة</th><th>المخزون</th><th>تكلفة الشراء</th><th>سعر البيع</th><th>ربح الوحدة</th><th>القيمة الحالية</th><th>الحالة</th><th>آخر تحديث</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                    @forelse($products as $index => $product)
                        @php
                            $isLow = $product->status === 'active' && $product->current_quantity <= $product->minimum_quantity;
                            $inventoryValue = (float) $product->purchase_cost * (int) $product->current_quantity;
                            $unitProfit = (float) $product->selling_price - (float) $product->purchase_cost;
                            $profitMargin = (float) $product->selling_price > 0 ? ($unitProfit / (float) $product->selling_price) * 100 : 0;
                        @endphp
                        <tr>
                            <td>{{ $products->firstItem() + $index }}</td>
                            <td><div class="wg-inv-product-cell"><div class="wg-inv-product-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4"/></svg>@if($product->image_path)<img src="{{ route('inventory.product-image', $product) }}" alt="{{ $product->name }}" onerror="this.style.display='none'">@endif</div><div><strong>{{ $product->name }}</strong><span>{{ $product->barcode ?: 'بدون باركود' }}</span></div></div></td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td><strong class="{{ $isLow ? 'is-low' : 'is-ok' }}">{{ number_format($product->current_quantity) }}</strong></td>
                            <td class="wg-inv-money">{{ \App\Support\NumberFormatter::money($product->purchase_cost) }} <small>{{ $product->currency }}</small></td>
                            <td class="wg-inv-money">{{ \App\Support\NumberFormatter::money($product->selling_price) }} <small>{{ $product->currency }}</small></td>
                            <td class="wg-inv-money"><strong class="{{ $unitProfit >= 0 ? 'wg-profit-positive' : 'wg-profit-negative' }}">{{ \App\Support\NumberFormatter::money($unitProfit) }}</strong> <small>{{ $product->currency }}</small><small class="wg-subline">{{ number_format($profitMargin, 0) }}%</small></td>
                            <td class="wg-inv-money">{{ \App\Support\NumberFormatter::money($inventoryValue) }} <small>{{ $product->currency }}</small></td>
                            <td>@if($product->status === 'inactive')<span class="wg-inv-status is-inactive">غير نشط</span>@elseif($isLow)<span class="wg-inv-status is-low">منخفض</span>@else<span class="wg-inv-status is-ok">متوفر</span>@endif</td>
                            <td>{{ optional($product->updated_at)->timezone('Asia/Aden')->format('Y-m-d') }}</td>
                            <td>
                                <div class="wg-inv-row-actions">
                                    @if($canManageProducts)
                                        <button type="button" x-on:click="editOpen = true; newCategory = false" wire:click="openEdit({{ $product->id }})" title="تعديل"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m4 16-1 5 5-1L19 9l-4-4L4 16zM13 7l4 4"/></svg></button>
                                        <button type="button" wire:click="toggle({{ $product->id }})" title="{{ $product->status === 'active' ? 'إيقاف' : 'تفعيل' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M9 9v6M15 9v6"/></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="wg-inv-table-empty">لا توجد منتجات مطابقة للفلاتر الحالية.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="wg-inv-table-footer">
                <span>عرض {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} من {{ number_format($products->total()) }} منتج</span>
                <div>{{ $products->onEachSide(1)->links() }}</div>
            </div>
        </article>
    </section>

    <div class="fin-modal-backdrop wg-product-fin-backdrop" x-cloak x-show="createOpen || editOpen" x-transition.opacity
         x-on:keydown.escape.window="createOpen = false; editOpen = false; $wire.closeModal()"
         x-on:click.self="createOpen = false; editOpen = false; $wire.closeModal()">
        <form class="fin-modal large wg-product-fin-modal" wire:submit="{{ $showEditModal ? 'updateProduct' : 'create' }}"
              x-data="{ previewUrl: null }" x-show="createOpen || editOpen" x-transition.scale.origin.center>
            <div class="fin-modal-head">
                <div>
                    <div class="fin-modal-title">{{ $showEditModal ? 'تعديل المنتج' : 'إضافة منتج جديد' }}</div>
                    <div class="fin-modal-sub">{{ $showEditModal ? 'حدّث بيانات المنتج وسعر البيع والصورة؛ المخزون والتكلفة يتحدثان من المشتريات.' : 'أدخل بيانات المنتج وصورته وتصنيفه، ثم احفظه ليظهر مباشرة في المخزون.' }}</div>
                </div>
                <button type="button" class="fin-close" x-on:click="createOpen = false; editOpen = false; $wire.closeModal()">×</button>
            </div>

            <div class="wg-modal-loading" wire:loading.flex wire:target="openCreate,openEdit">
                <span></span><strong>جارٍ تجهيز بيانات المنتج...</strong>
            </div>

            @if($errors->any())
                <div class="fin-errors">{{ $errors->first() }}</div>
            @endif

            <div class="fin-form-grid">
                <div class="fin-form-group">
                    <label class="fin-label">اسم المنتج <span class="fin-required">*</span></label>
                    <input class="fin-field" wire:model="name" type="text" placeholder="مثال: مياه معدنية 500 مل" autocomplete="off" required>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">الباركود (اختياري)</label>
                    <input class="fin-field" wire:model="barcode" type="text" placeholder="امسح أو اكتب الباركود" autocomplete="off">
                </div>

                <div class="fin-form-group full fin-category-builder wg-product-fin-category">
                    <div class="fin-category-heading">
                        <label class="fin-label">تصنيف المنتج <span class="fin-required">*</span></label>
                        <div class="fin-category-modes">
                            @if($categories->isNotEmpty())
                                <button type="button" class="fin-category-mode" x-bind:class="{ 'active': !newCategory }"
                                        x-on:click="newCategory = false; $wire.$set('new_category_name', '', false)">اختيار موجود</button>
                            @endif
                            <button type="button" class="fin-category-mode" x-bind:class="{ 'active': newCategory }"
                                    x-on:click="newCategory = true; $wire.$set('category_id', null, false)">+ تصنيف جديد</button>
                        </div>
                    </div>

                    <div x-show="!newCategory" x-cloak>
                        <select class="fin-select" wire:model="category_id">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                        </select>
                    </div>

                    <div class="fin-new-category" x-show="newCategory" x-cloak>
                        <input x-ref="newProductCategoryName" class="fin-field" wire:model="new_category_name" placeholder="مثال: مشروبات" maxlength="100">
                        <div class="fin-category-help">يُحفظ التصنيف الجديد ويظهر تلقائياً عند إضافة أو تعديل المنتجات القادمة.</div>
                        <div class="fin-category-suggestions">
                            <span>اقتراحات:</span>
                            @foreach($categorySuggestions as $suggestion)
                                <button type="button" x-on:click="$refs.newProductCategoryName.value = @js($suggestion); $refs.newProductCategoryName.dispatchEvent(new Event('input', { bubbles: true }))">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">العملة <span class="fin-required">*</span></label>
                    <select class="fin-select" wire:model="currency" @disabled($showEditModal) required>
                        <option value="YER">YER — ريال يمني</option>
                        <option value="SAR">SAR — ريال سعودي</option>
                    </select>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">حد تنبيه المخزون <span class="fin-required">*</span></label>
                    <input class="fin-field" wire:model="minimum_quantity" type="number" min="0" placeholder="0" required>
                    <div class="wg-product-fin-help">يظهر تنبيه عندما تصل الكمية لهذا الحد أو أقل.</div>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">تكلفة شراء الوحدة <span class="fin-required">*</span></label>
                    <input class="fin-field wg-money-input" wire:model="purchase_cost" type="text" inputmode="decimal" x-money @readonly($showEditModal) placeholder="0" required>
                    <div class="wg-product-fin-help">{{ $showEditModal ? 'يحسبها النظام تلقائياً عند اعتماد المشتريات.' : 'المبلغ المدفوع للحصول على وحدة واحدة.' }}</div>
                </div>

                <div class="fin-form-group">
                    <label class="fin-label">سعر بيع الوحدة <span class="fin-required">*</span></label>
                    <input class="fin-field wg-money-input" wire:model="selling_price" type="text" inputmode="decimal" x-money placeholder="0" required>
                    <div class="wg-product-fin-help">السعر الذي يدفعه العميل عند البيع.</div>
                </div>

                @if(!$showEditModal)
                    <div class="fin-form-group full wg-product-opening-balance">
                        <label class="fin-label">الكمية الموجودة حالياً (اختياري)</label>
                        <input class="fin-field" wire:model="opening_quantity" type="number" min="0" placeholder="0">
                        <div class="wg-product-fin-help">استخدمها للرصيد الافتتاحي فقط؛ أي بضاعة جديدة بعد ذلك تُسجّل من المشتريات.</div>
                    </div>
                @endif

                <div class="fin-form-group full">
                    <label class="fin-label">صورة المنتج (اختياري)</label>
                    <div class="fin-file wg-product-fin-file">
                        <input wire:model="product_image" type="file" accept="image/png,image/jpeg,image/webp"
                               x-on:change="if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                        <div class="wg-product-fin-image">
                            <span class="wg-product-image-preview">
                                <svg x-show="!previewUrl" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4 8-4"/></svg>
                                @if($showEditModal && $editingProductId)<img x-show="!previewUrl" src="{{ route('inventory.product-image', $editingProductId) }}" alt="" onerror="this.style.display='none'">@endif
                                <img x-show="previewUrl" style="display: none" x-bind:src="previewUrl" alt="معاينة صورة المنتج">
                            </span>
                            <div><strong>اختر صورة المنتج</strong><small>JPG أو PNG أو WEBP حتى 2MB — ستظهر المعاينة هنا مباشرة.</small></div>
                        </div>
                        <span class="fin-file-status" wire:loading wire:target="product_image">جاري رفع الصورة...</span>
                        @if($product_image)<span class="fin-file-ready">تم اختيار الصورة بنجاح.</span>@endif
                    </div>
                </div>

                <div class="fin-form-group full">
                    <label class="fin-label">معلومات إضافية (اختياري)</label>
                    <textarea class="fin-field fin-textarea" wire:model="notes" placeholder="الحجم، النكهة، المورد، أو أي معلومة تساعد في إدارة المنتج..."></textarea>
                </div>
            </div>

            <div class="fin-modal-foot">
                <button class="fin-btn primary" type="submit" wire:loading.attr="disabled" wire:target="create,updateProduct,product_image">
                    <span wire:loading.remove wire:target="create,updateProduct">{{ $showEditModal ? 'حفظ التعديلات' : 'حفظ المنتج' }}</span>
                    <span wire:loading wire:target="create,updateProduct">جاري الحفظ...</span>
                </button>
                <button class="fin-btn" type="button" x-on:click="createOpen = false; editOpen = false; $wire.closeModal()">إلغاء</button>
            </div>
        </form>
    </div>
</div>