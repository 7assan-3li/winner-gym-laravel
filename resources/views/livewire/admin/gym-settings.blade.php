<div class="wg-page" dir="rtl">
    <div class="wg-page-head"><div><h1 class="wg-title">إعدادات النظام</h1><div class="wg-subtitle">هوية النادي، العملات، الدفع والخصائص العامة</div></div><button form="settings-form" class="wg-btn wg-btn-primary">حفظ الإعدادات</button></div>
    @include('livewire.admin._tabs')
    @if(session('success'))<div class="wg-flash">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="wg-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form id="settings-form" wire:submit="save" class="wg-two">
        <div style="display:grid;gap:12px">
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">بيانات النادي</h2><div class="wg-two" style="margin-top:13px">
                <div><label class="wg-label">اسم النادي *</label><input wire:model="gym_name" class="wg-field"></div><div><label class="wg-label">الموقع *</label><input wire:model="location" class="wg-field"></div>
                <div><label class="wg-label">الهاتف</label><input wire:model="phone" class="wg-field" dir="ltr"></div><div><label class="wg-label">المسؤول العام</label><input wire:model="manager_name" class="wg-field"></div>
            </div></div>
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">العملات والمنطقة الزمنية</h2><div class="wg-two" style="margin-top:13px"><div><label class="wg-label">المنطقة الزمنية</label><select wire:model="timezone" class="wg-select"><option value="Asia/Aden">Asia/Aden — اليمن</option></select></div><div><label class="wg-label">العملة الافتراضية</label><select wire:model="default_currency" class="wg-select"><option value="YER">YER — ريال يمني</option><option value="SAR">SAR — ريال سعودي</option></select></div></div><div style="display:flex;gap:18px;margin-top:13px"><label style="font-size:11px"><input wire:model="currency_yer" type="checkbox"> تفعيل YER</label><label style="font-size:11px"><input wire:model="currency_sar" type="checkbox"> تفعيل SAR</label></div></div>
        </div>
        <div style="display:grid;gap:12px">
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">سياسة الدفع</h2><div style="display:grid;gap:12px;margin-top:13px"><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">مرجع التحويل</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">إلزام رقم مرجع عند الدفع بتحويل</div></div><input wire:model="require_transfer_reference" type="checkbox"></label><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">إثبات الدفع</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">إلزام رفع صورة الإيصال</div></div><input wire:model="require_payment_proof" type="checkbox"></label></div></div>
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">التكاملات والخدمات</h2><div style="display:grid;gap:12px;margin-top:13px"><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">واتساب</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">تفعيل قواعد ورسائل واتساب</div></div><input wire:model="whatsapp_enabled" type="checkbox"></label><label class="wg-finance-box" style="display:flex;justify-content:space-between;align-items:center"><div><strong style="font-size:11px">استعلام العضو</strong><div class="wg-muted" style="font-size:9px;margin-top:4px">السماح بصفحة الاستعلام العامة</div></div><input wire:model="member_inquiry_enabled" type="checkbox"></label></div></div>
        </div>
    </form>
    <div class="wg-card wg-card-pad"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px"><div><h2 class="wg-section-title">فترات الرجال والنساء</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">تم فصل الفترات عن الإعدادات العامة حتى تدير فترتين للرجال وفترتين للنساء لكل فرع.</div></div><a href="{{ route('admin.periods') }}" wire:navigate class="wg-btn">فتح إدارة الفترات</a></div></div>
</div>
