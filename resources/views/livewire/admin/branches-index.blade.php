<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div><h1 class="wg-title">الفروع</h1><div class="wg-subtitle">إضافة الفروع وإدارتها وتعيين الفرع الرئيسي</div></div>
        <button wire:click="openCreate" class="wg-btn wg-btn-primary">＋ إضافة فرع</button>
    </div>
    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif
    @if($errors->has('branch'))<div class="wg-errors">{{ $errors->first('branch') }}</div>@endif

    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
        @forelse($branches as $branch)
            <div class="wg-card wg-card-pad">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                    <div><div style="font-weight:800;color:#fff;font-size:14px">{{ $branch->name }}</div><div class="wg-muted" style="font-size:10px;margin-top:4px">{{ $branch->code }} · {{ $branch->address ?: 'العنوان غير محدد' }}</div></div>
                    <span class="wg-badge {{ $branch->is_main ? 'wg-badge-blue' : ($branch->is_active ? 'wg-badge-green' : 'wg-badge-red') }}">{{ $branch->is_main ? 'الرئيسي' : ($branch->is_active ? 'نشط' : 'مؤرشف') }}</span>
                </div>
                <div class="wg-finance-grid" style="margin-top:14px">
                    <div><span>الموظفون</span><strong>{{ $branch->users_count }}</strong></div>
                    <div><span>الفترات</span><strong>{{ $branch->periods_count }}</strong></div>
                    <div><span>مدير الفرع</span><strong style="font-size:11px">{{ $branch->manager_name ?: 'غير محدد' }}</strong></div>
                    <div><span>الهاتف</span><strong style="font-size:11px" dir="ltr">{{ $branch->phone ?: '—' }}</strong></div>
                </div>
                <div class="wg-divider" style="margin:14px 0"></div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <button wire:click="edit({{ $branch->id }})" class="wg-btn wg-btn-sm">تعديل</button>
                    @if(!$branch->is_main)<button wire:click="makeMain({{ $branch->id }})" wire:confirm="تعيين هذا الفرع كفرع رئيسي؟" class="wg-btn wg-btn-sm">تعيين رئيسي</button>@endif
                    <button wire:click="toggle({{ $branch->id }})" wire:confirm="تأكيد تغيير حالة الفرع؟" class="wg-btn wg-btn-sm {{ $branch->is_active ? 'wg-btn-danger' : 'wg-btn-success' }}">{{ $branch->is_active ? 'أرشفة' : 'إعادة تفعيل' }}</button>
                </div>
            </div>
        @empty<div class="wg-card wg-card-pad wg-muted">لا توجد فروع بعد.</div>@endforelse
    </div>

    <div class="wg-card wg-card-pad"><h2 class="wg-section-title">لماذا الأرشفة بدل الحذف؟</h2><p class="wg-muted" style="font-size:10px;line-height:1.9;margin:8px 0 0">لأن الفروع ترتبط بالموظفين والفترات والسجلات. الأرشفة تمنع الاستخدام الجديد وتحافظ على التاريخ والتقارير القديمة.</p></div>

    @if($showEditor)
    <div class="wg-modal-backdrop">
        <div class="wg-modal wg-modal-lg" dir="rtl">
            <div class="wg-modal-head"><div><h2 class="wg-section-title">{{ $editingId ? 'تعديل الفرع' : 'إضافة فرع جديد' }}</h2></div><button wire:click="$set('showEditor',false)" class="wg-modal-x">×</button></div>
            <form wire:submit="save"><div class="wg-modal-body">
                @if($errors->any())<div class="wg-errors" style="margin-bottom:14px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                <div class="wg-two">
                    <div><label class="wg-label">اسم الفرع *</label><input wire:model="name" class="wg-field"></div>
                    <div><label class="wg-label">كود الفرع *</label><input wire:model="code" class="wg-field" dir="ltr" placeholder="MAIN / BR-02"></div>
                    <div><label class="wg-label">الهاتف</label><input wire:model="phone" class="wg-field" dir="ltr"></div>
                    <div><label class="wg-label">مدير الفرع</label><input wire:model="manager_name" class="wg-field"></div>
                    <div style="grid-column:1/-1"><label class="wg-label">العنوان</label><input wire:model="address" class="wg-field"></div>
                    <div style="grid-column:1/-1"><label class="wg-label">ملاحظات</label><textarea wire:model="notes" class="wg-textarea"></textarea></div>
                </div>
                <div style="display:flex;gap:18px;margin-top:14px"><label style="display:flex;align-items:center;gap:7px;font-size:11px"><input wire:model="is_active" type="checkbox"> الفرع نشط</label><label style="display:flex;align-items:center;gap:7px;font-size:11px"><input wire:model="is_main" type="checkbox"> الفرع الرئيسي</label></div>
            </div><div class="wg-modal-foot"><button class="wg-btn wg-btn-primary">حفظ الفرع</button><button type="button" wire:click="$set('showEditor',false)" class="wg-btn">إلغاء</button></div></form>
        </div>
    </div>
    @endif
</div>
