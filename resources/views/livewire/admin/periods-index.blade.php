<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div><h1 class="wg-title">الفترات التشغيلية</h1><div class="wg-subtitle">فترتان للرجال وفترتان للنساء لكل فرع، مع إمكانية إضافة فترات أخرى</div></div>
        <div style="display:flex;gap:8px"><button wire:click="openCreate('men')" class="wg-btn">＋ فترة رجال</button><button wire:click="openCreate('women')" class="wg-btn wg-btn-primary">＋ فترة نساء</button></div>
    </div>
    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif

    <div class="wg-card wg-card-pad">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px"><div><h2 class="wg-section-title">الجدول الحالي</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">الأوقات الافتراضية تركناها قابلة للتحديد من الإدارة حتى تضع الساعات النهائية التي تعتمدونها.</div></div></div>
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px">
            @foreach($periods as $period)
                <div class="wg-card" style="padding:14px;border-color:{{ $period->gender === 'women' ? '#2c2655' : '#12335c' }}">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px"><div><strong style="font-size:12px;color:#fff">{{ $period->name }}</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">{{ $period->branch?->name ?: 'بدون فرع' }}</div></div><span class="wg-badge {{ $period->gender === 'women' ? 'wg-badge-purple' : 'wg-badge-blue' }}">{{ $period->gender === 'women' ? 'نساء' : 'رجال' }} {{ $period->slot_order }}</span></div>
                    <div style="font-size:20px;font-weight:900;margin-top:16px" dir="ltr">{{ $period->start_time ? substr((string)$period->start_time,0,5) : '--:--' }} <span class="wg-muted">→</span> {{ $period->end_time ? substr((string)$period->end_time,0,5) : '--:--' }}</div>
                    <div style="display:flex;justify-content:space-between;margin-top:12px"><span class="wg-muted" style="font-size:10px">{{ $period->users_count }} موظف</span><span class="wg-badge {{ $period->is_active ? 'wg-badge-green' : 'wg-badge-red' }}">{{ $period->is_active ? 'نشطة' : 'متوقفة' }}</span></div>
                    <div class="wg-divider" style="margin:12px 0"></div><div style="display:flex;gap:6px"><button wire:click="edit({{ $period->id }})" class="wg-btn wg-btn-sm">تعديل الوقت</button><button wire:click="toggle({{ $period->id }})" class="wg-btn wg-btn-sm {{ $period->is_active ? 'wg-btn-danger' : 'wg-btn-success' }}">{{ $period->is_active ? 'إيقاف' : 'تفعيل' }}</button></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="wg-two">
        <div class="wg-card wg-card-pad"><h2 class="wg-section-title">قواعد الفترات</h2><div class="wg-muted" style="font-size:10px;line-height:2;margin-top:9px">• لا يسمح بتداخل فترتين من نفس الجنس في الفرع نفسه.<br>• يمكن ربط الموظف بفترة محددة أو جعله مرنًا.<br>• إيقاف الفترة لا يحذف السجل أو الموظفين المرتبطين بها.<br>• كل فرع يمكن أن يملك جدوله الخاص.</div></div>
        <div class="wg-card wg-card-pad"><h2 class="wg-section-title">التنسيق المقترح</h2><div class="wg-muted" style="font-size:10px;line-height:2;margin-top:9px">رجال 1 · رجال 2 · نساء 1 · نساء 2. أنت تحدد ساعات البداية والنهاية النهائية من هذه الصفحة، لذلك لن نثبت أوقاتًا غير مؤكدة داخل النظام.</div></div>
    </div>

    @if($showEditor)
    <div class="wg-modal-backdrop"><div class="wg-modal" dir="rtl"><div class="wg-modal-head"><h2 class="wg-section-title">{{ $editingId ? 'تعديل الفترة' : 'إضافة فترة' }}</h2><button wire:click="$set('showEditor',false)" class="wg-modal-x">×</button></div><form wire:submit="save"><div class="wg-modal-body">
        @if($errors->any())<div class="wg-errors" style="margin-bottom:14px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <div class="wg-two">
            <div><label class="wg-label">الفرع *</label><select wire:model="branch_id" class="wg-select">@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div><label class="wg-label">النوع *</label><select wire:model="gender" class="wg-select"><option value="men">رجال</option><option value="women">نساء</option></select></div>
            <div><label class="wg-label">اسم الفترة *</label><input wire:model="name" class="wg-field"></div>
            <div><label class="wg-label">رقم الفترة *</label><input wire:model="slot_order" type="number" min="1" max="20" class="wg-field"></div>
            <div><label class="wg-label">من الساعة</label><input wire:model="start_time" type="time" class="wg-field" dir="ltr"></div>
            <div><label class="wg-label">إلى الساعة</label><input wire:model="end_time" type="time" class="wg-field" dir="ltr"></div>
            <div style="grid-column:1/-1"><label class="wg-label">ملاحظات</label><textarea wire:model="notes" class="wg-textarea"></textarea></div>
        </div><label style="display:flex;align-items:center;gap:7px;font-size:11px;margin-top:14px"><input wire:model="is_active" type="checkbox"> الفترة نشطة</label>
    </div><div class="wg-modal-foot"><button class="wg-btn wg-btn-primary">حفظ الفترة</button><button type="button" wire:click="$set('showEditor',false)" class="wg-btn">إلغاء</button></div></form></div></div>
    @endif
</div>
