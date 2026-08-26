<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div>
            <h1 class="wg-title">الباقات</h1>
            <div class="wg-subtitle">الرئيسية · الاشتراكات · الباقات</div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('subscriptions.index') }}" wire:navigate class="wg-btn">الاشتراكات</a>
            <button class="wg-btn wg-btn-primary" type="button" wire:click="openCreateModal">＋ إضافة باقة جديدة</button>
        </div>
    </div>

    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="wg-errors">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="wg-grid-stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
        <div class="wg-card wg-stat"><small>إجمالي الباقات</small><strong>{{ $counts['total'] }}</strong><div class="wg-stat-icon wg-blue">□</div></div>
        <div class="wg-card wg-stat"><small>الباقات النشطة</small><strong>{{ $counts['active'] }}</strong><div class="wg-stat-note wg-green">متاحة للاشتراكات الجديدة</div><div class="wg-stat-icon wg-green">✓</div></div>
        <div class="wg-card wg-stat"><small>الباقات غير النشطة</small><strong>{{ $counts['inactive'] }}</strong><div class="wg-stat-note wg-red">غير متاحة للاشتراكات الجديدة</div><div class="wg-stat-icon wg-red">×</div></div>
    </div>

    <div class="wg-table-wrap">
        <table class="wg-table">
            <thead><tr><th>#</th><th>اسم الباقة</th><th>المدة</th><th>السعر YER</th><th>السعر SAR</th><th>الحالة</th><th>الوصف</th><th>الإجراءات</th></tr></thead>
            <tbody>
            @forelse($packages as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td><strong style="color:#f2f6fd">{{ $p->name }}</strong></td>
                    <td>{{ $p->duration_value }} {{ match($p->duration_unit){'day'=>'يوم','week'=>'أسبوع','month'=>'شهر','year'=>'سنة',default=>$p->duration_unit} }}</td>
                    <td dir="ltr">{{ $p->price_yer !== null ? \App\Support\NumberFormatter::money($p->price_yer).' YER' : '—' }}</td>
                    <td dir="ltr">{{ $p->price_sar !== null ? \App\Support\NumberFormatter::money($p->price_sar).' SAR' : '—' }}</td>
                    <td><span class="wg-badge {{ $p->is_active ? 'wg-badge-green' : 'wg-badge-orange' }}">{{ $p->is_active ? 'نشط' : 'غير نشط' }}</span></td>
                    <td class="wg-muted">{{ $p->description ?: '—' }}</td>
                    <td><button class="wg-btn wg-btn-sm" wire:click="toggle({{ $p->id }})" wire:confirm="تأكيد تغيير حالة الباقة؟">{{ $p->is_active ? 'إيقاف' : 'تفعيل' }}</button></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:38px;color:#7f8ea3">لا توجد باقات بعد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $packages->links() }}</div>

    @if($showModal)
    <div class="wg-modal-backdrop" wire:click.self="closeCreateModal" style="display:flex;">
        <div class="wg-modal" dir="rtl">
            <form wire:submit="create">
                <div class="wg-modal-head">
                    <div>
                        <h3 class="wg-section-title" style="font-size:17px">إضافة باقة جديدة</h3>
                        <div class="wg-subtitle">السعر يحدده صاحب النادي وليس ثابتًا في النظام.</div>
                    </div>
                    <button class="wg-modal-x" type="button" wire:click="closeCreateModal">×</button>
                </div>
                <div class="wg-modal-body">
                    <div class="wg-two">
                        <div><label class="wg-label">اسم الباقة *</label><input wire:model="name" class="wg-field" placeholder="مثال: باقة 6 أشهر" required></div>
                        <div>
                            <label class="wg-label">المدة *</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                <input wire:model="duration_value" type="number" min="1" class="wg-field" required>
                                <select wire:model="duration_unit" class="wg-select">
                                    <option value="day">يوم</option>
                                    <option value="week">أسبوع</option>
                                    <option value="month">شهر</option>
                                    <option value="year">سنة</option>
                                </select>
                            </div>
                        </div>
                        <div><label class="wg-label">السعر YER</label><input wire:model="price_yer" type="number" step="0.01" class="wg-field" placeholder="0"></div>
                        <div><label class="wg-label">السعر SAR</label><input wire:model="price_sar" type="number" step="0.01" class="wg-field" placeholder="0"></div>
                    </div>
                    <div style="margin-top:12px">
                        <label class="wg-label">الوصف (اختياري)</label>
                        <textarea wire:model="description" class="wg-textarea" placeholder="وصف الباقة..."></textarea>
                    </div>
                </div>
                <div class="wg-modal-foot">
                    <button type="submit" class="wg-btn wg-btn-primary">حفظ الباقة</button>
                    <button type="button" class="wg-btn" wire:click="closeCreateModal">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
