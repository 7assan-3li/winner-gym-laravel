<div class="wg-nut-page wg-nut-measurements-page" dir="rtl">
    @php
        $cardMeta = [
            'weight' => ['الوزن','is-blue','⚖'], 'height' => ['الطول','is-green','↕'],
            'body_fat' => ['دهون الجسم','is-orange','◉'], 'muscle' => ['نسبة العضلات','is-purple','♧'],
            'water' => ['نسبة الماء','is-blue','◌'], 'visceral_fat' => ['الدهون الحشوية','is-red','◎'],
            'muscle_mass' => ['كتلة العضلات','is-green','♧'], 'bone_mass' => ['كتلة العظام','is-green','◇'], 'waist' => ['محيط الخصر','is-purple','⌁'],
            'chest' => ['محيط الصدر','is-orange','▱'], 'arm' => ['محيط الذراع','is-red','⌇'], 'hip' => ['محيط الورك','is-purple','◫'],
            'thigh' => ['محيط الفخذ','is-blue','◫'],
        ];
        $latestClient = $latestMeasurement?->member?->full_name ?? $latestMeasurement?->nutritionClient?->full_name;
    @endphp

    @if(session('success'))<div class="wg-nut-flash is-success"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m5 12 4 4L19 6"/></svg><span>{{ session('success') }}</span></div>@endif
    @if($errors->any())<div class="wg-nut-flash is-error"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4m0 4h.01M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg><div>@foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach</div></div>@endif

    <section class="wg-nut-commandbar">
        <nav class="wg-nut-tabs"><a href="{{ route('nutrition.appointments') }}" wire:navigate>المواعيد</a><a href="{{ route('nutrition.measurements') }}" wire:navigate class="is-active">القياسات</a></nav>
        <div class="wg-nut-primary-actions"><button class="wg-nut-btn wg-nut-btn-primary" type="button" wire:click="openMeasurement"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h16M7 16l3-3 3 2 4-6M18 4v6M15 7h6"/></svg>تسجيل قياس جديد</button><a class="wg-nut-btn" href="{{ route('nutrition.appointments') }}" wire:navigate><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg>العودة للمواعيد</a></div>
    </section>

    <section class="wg-nut-card wg-nut-measure-hero">
        <div class="wg-nut-measurement-head">
            <div><span>آخر قراءة</span><h2>{{ $latestClient ?: 'لا توجد قياسات بعد' }}</h2><p>@if($latestMeasurement) آخر قياس: {{ $latestMeasurement->measured_at->timezone('Asia/Aden')->format('Y-m-d h:i A') }} @else ابدأ بتسجيل أول قياس لعضو أو عميل تغذية. @endif</p></div>
            <div class="wg-nut-measure-bmi"><span>BMI</span><strong>{{ $latestMeasurement?->bmi ?? '—' }}</strong><small>يُحسب تلقائياً من الوزن والطول</small></div>
        </div>
        <div class="wg-nut-measure-grid">
            @foreach($cardMeta as $code => [$label,$color,$icon])
                @php $v = $latestValues[$code] ?? null; @endphp
                <article class="{{ $color }}"><i>{{ $icon }}</i><div><span>{{ $label }}</span><strong>{{ $v ? rtrim(rtrim(number_format((float)$v->value,3,'.',''),'0'),'.') : '—' }} @if($v)<small>{{ $v->type->unit }}</small>@endif</strong></div></article>
            @endforeach
        </div>
    </section>

    <section class="wg-nut-card wg-nut-history-card">
        <div class="wg-nut-table-top"><div><h2>سجل القياسات</h2><p>تاريخ تطور قياسات أعضاء وعملاء التغذية</p></div><div class="wg-nut-table-filters"><input id="nutrition-search" wire:model.live.debounce.350ms="search" type="search" placeholder="ابحث باسم العميل أو الهاتف..."></div></div>
        <div class="wg-nut-table-scroll"><table class="wg-nut-table wg-nut-measure-table"><thead><tr><th>التاريخ</th><th>العميل</th><th>BMI</th><th>الوزن</th><th>دهون الجسم</th><th>العضلات</th><th>الماء</th><th>الخصر</th><th>الصدر</th><th>ملاحظات</th></tr></thead><tbody>
            @forelse($measurements as $m)
                @php $vals = $m->values->mapWithKeys(fn($v)=>[$v->type->code=>$v]); @endphp
                <tr><td class="wg-nut-time">{{ $m->measured_at->timezone('Asia/Aden')->format('Y-m-d') }}<small>{{ $m->measured_at->timezone('Asia/Aden')->format('h:i A') }}</small></td><td><div class="wg-nut-measure-client"><span>{{ mb_substr($m->member?->full_name ?? $m->nutritionClient?->full_name ?? 'ع',0,1) }}</span><strong>{{ $m->member?->full_name ?? $m->nutritionClient?->full_name }}</strong></div></td><td><span class="wg-nut-bmi-pill">{{ $m->bmi ?? '—' }}</span></td><td>{{ $vals->get('weight')?->value ?? '—' }} <small>{{ $vals->get('weight')?->type?->unit }}</small></td><td>{{ $vals->get('body_fat')?->value ?? '—' }} <small>{{ $vals->get('body_fat')?->type?->unit }}</small></td><td>{{ $vals->get('muscle')?->value ?? '—' }} <small>{{ $vals->get('muscle')?->type?->unit }}</small></td><td>{{ $vals->get('water')?->value ?? '—' }} <small>{{ $vals->get('water')?->type?->unit }}</small></td><td>{{ $vals->get('waist')?->value ?? '—' }} <small>{{ $vals->get('waist')?->type?->unit }}</small></td><td>{{ $vals->get('chest')?->value ?? '—' }} <small>{{ $vals->get('chest')?->type?->unit }}</small></td><td class="wg-nut-notes-cell">{{ $m->notes ?: '—' }}</td></tr>
            @empty<tr><td colspan="10" class="wg-nut-empty"><strong>لا توجد قياسات</strong><span>سجل أول قياس لبدء متابعة التقدم.</span></td></tr>@endforelse
        </tbody></table></div><div class="wg-nut-table-footer"><span>عرض {{ $measurements->firstItem() ?? 0 }} - {{ $measurements->lastItem() ?? 0 }} من {{ $measurements->total() }} قياس</span><div>{{ $measurements->onEachSide(1)->links() }}</div></div>
    </section>

    @if($showMeasurementModal)
    <div class="wg-nut-modal-backdrop" wire:click.self="closeMeasurement">
        <section class="wg-nut-modal wg-nut-modal-measurement">
            <button class="wg-nut-modal-x" type="button" wire:click="closeMeasurement">×</button>
            <header><i class="is-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20h16M7 16l3-3 3 2 4-6M18 4v6M15 7h6"/></svg></i><div><h2>تسجيل قياس جديد</h2><p>أدخل القيم المتوفرة فقط. يحسب النظام BMI تلقائياً عند توفر الوزن والطول.</p></div></header>
            <form wire:submit="save">
                <div class="wg-nut-form-grid">
                    <label><span>نوع العميل *</span><select wire:model.live="client_type"><option value="member">عضو</option><option value="external">غير عضو</option></select></label>
                    @if($client_type === 'member')<label><span>العضو *</span><select wire:model="member_id"><option value="">اختر العضو</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->full_name }} — {{ $m->membership_code }}</option>@endforeach</select></label>@else<label><span>عميل التغذية *</span><select wire:model="nutrition_client_id"><option value="">اختر العميل</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->phone }}</option>@endforeach</select></label>@endif
                    <label><span>الأخصائي *</span><select wire:model="nutritionist_id" {{ auth()->user()->role === 'nutritionist' ? 'disabled' : '' }}><option value="">اختر الأخصائي</option>@foreach($nutritionists as $n)<option value="{{ $n->id }}">{{ $n->name ?: $n->username }}</option>@endforeach</select></label>
                </div>
                @if($appointment_id)<div class="wg-nut-linked-appointment"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg><span>القياس مرتبط بالموعد #{{ $appointment_id }}</span></div>@endif
                <div class="wg-nut-measure-form-grid">
                    @foreach($types as $type)<label><span>{{ $type->name_ar }} @if($type->unit)<small>{{ $type->unit }}</small>@endif</span><input wire:model="values.{{ $type->code }}" type="number" min="0" step="0.001" placeholder="0"></label>@endforeach
                </div>
                <label class="wg-nut-full-label"><span>ملاحظات الأخصائي</span><textarea wire:model="notes" rows="3" placeholder="ملاحظات عن التقدم أو القياسات..."></textarea></label>
                <footer><button type="button" class="wg-nut-btn" wire:click="closeMeasurement">إلغاء</button><button class="wg-nut-btn wg-nut-btn-primary" type="submit">حفظ القياسات</button></footer>
            </form>
        </section>
    </div>
    @endif
</div>
