<div class="wg-page" dir="rtl">
    <div class="wg-page-head">
        <div><h1 class="wg-title">الإدارة</h1><div class="wg-subtitle">مركز التحكم الكامل في النظام</div></div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('staff.index') }}" wire:navigate class="wg-btn">إضافة موظف</a>
            <a href="{{ route('admin.branches') }}" wire:navigate class="wg-btn wg-btn-primary">إدارة الفروع والفترات</a>
        </div>
    </div>

    @include('livewire.admin._tabs')

    <div class="wg-grid-stats">
        @php($cards = [
            ['إجمالي الموظفين',$stats['employees'],'wg-blue'],
            ['حسابات نشطة',$stats['active_employees'],'wg-green'],
            ['الفروع النشطة',$stats['branches'],'wg-purple'],
            ['الفترات النشطة',$stats['periods'],'wg-orange'],
            ['صلاحيات مفعلة',$stats['permissions'],'wg-blue'],
            ['عمليات اليوم',$stats['audit_today'],'wg-green'],
        ])
        @foreach($cards as [$label,$value,$color])
            <div class="wg-card wg-stat"><small>{{ $label }}</small><strong class="{{ $color }}">{{ number_format($value) }}</strong><div class="wg-stat-note">محدث لحظيًا</div></div>
        @endforeach
    </div>

    <div class="wg-columns">
        <section style="display:grid;gap:14px;min-width:0">
            <div class="wg-card wg-card-pad">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <div><h2 class="wg-section-title">الفروع والفترات التشغيلية</h2><div class="wg-muted" style="font-size:10px;margin-top:4px">يمكن لكل فرع امتلاك فترتين للرجال وفترتين للنساء أو أكثر عند الحاجة.</div></div>
                    <a href="{{ route('admin.periods') }}" wire:navigate class="wg-blue" style="font-size:11px;text-decoration:none">إدارة الفترات ←</a>
                </div>
                <div class="wg-two">
                    @forelse($branches as $branch)
                        <div class="wg-finance-box">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                                <div><strong style="font-size:13px">{{ $branch->name }}</strong><div class="wg-muted" style="font-size:10px;margin-top:4px">{{ $branch->address ?: 'بدون عنوان' }}</div></div>
                                <span class="wg-badge {{ $branch->is_main ? 'wg-badge-blue' : 'wg-badge-green' }}">{{ $branch->is_main ? 'الرئيسي' : 'نشط' }}</span>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px">
                                <div><span>الموظفون</span><strong>{{ $branch->users_count }}</strong></div>
                                <div><span>الفترات</span><strong>{{ $branch->periods_count }}</strong></div>
                            </div>
                        </div>
                    @empty
                        <div class="wg-muted">لا توجد فروع.</div>
                    @endforelse
                </div>
                <div class="wg-divider" style="margin:14px 0"></div>
                <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px">
                    @foreach($periods as $period)
                        <div class="wg-card" style="padding:12px">
                            <div style="display:flex;justify-content:space-between;gap:8px"><strong style="font-size:11px">{{ $period->name }}</strong><span class="wg-badge {{ $period->gender === 'women' ? 'wg-badge-purple' : 'wg-badge-blue' }}">{{ $period->gender === 'women' ? 'نساء' : 'رجال' }}</span></div>
                            <div class="wg-muted" style="font-size:10px;margin-top:7px">{{ $period->branch?->name }}</div>
                            <div style="font-size:14px;font-weight:800;margin-top:8px" dir="ltr">{{ $period->start_time ? substr((string)$period->start_time,0,5).' — '.substr((string)$period->end_time,0,5) : 'حدد الوقت' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="wg-two">
                <div class="wg-card">
                    <div class="wg-card-pad" style="display:flex;justify-content:space-between;align-items:center"><h2 class="wg-section-title">آخر نشاط إداري</h2><a href="{{ route('audit.index') }}" wire:navigate class="wg-blue" style="font-size:10px;text-decoration:none">السجل الكامل</a></div>
                    @forelse($recentAudit as $log)
                        <div class="wg-alert"><div><div style="font-weight:700;color:#e8eef7">{{ $log->action }}</div><div class="wg-muted" style="font-size:10px;margin-top:3px">{{ $log->user_name ?: 'النظام' }} · {{ $log->category }}</div></div><span class="wg-muted" style="font-size:10px">{{ \Illuminate\Support\Carbon::parse($log->created_at)->diffForHumans() }}</span></div>
                    @empty<div class="wg-card-pad wg-muted" style="font-size:11px">لا توجد عمليات مسجلة.</div>@endforelse
                </div>
                <div class="wg-card wg-card-pad">
                    <h2 class="wg-section-title">جاهزية الإدارة</h2>
                    <div style="display:grid;gap:9px;margin-top:13px">
                        <div class="wg-finance-box"><span>قواعد واتساب</span><strong class="wg-purple">{{ $whatsapp['active'] }} / {{ $whatsapp['rules'] }}</strong></div>
                        <div class="wg-finance-box"><span>رسائل مرسلة</span><strong class="wg-green">{{ number_format($whatsapp['sent']) }}</strong></div>
                        <div class="wg-finance-box"><span>آخر نسخة احتياطية</span><strong style="font-size:12px">{{ $latestBackup?->created_at ? \Illuminate\Support\Carbon::parse($latestBackup->created_at)->diffForHumans() : 'لا توجد' }}</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <aside style="display:grid;gap:14px;align-content:start">
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title" style="margin-bottom:12px">إجراءات الإدارة</h2><div class="wg-quick">
                <a href="{{ route('staff.index') }}" wire:navigate class="wg-btn">＋ إضافة / تعديل موظف</a>
                <a href="{{ route('admin.permissions') }}" wire:navigate class="wg-btn">◈ الصلاحيات والاستثناءات</a>
                <a href="{{ route('admin.branches') }}" wire:navigate class="wg-btn">⌖ الفروع</a>
                <a href="{{ route('admin.periods') }}" wire:navigate class="wg-btn">◷ فترات الرجال والنساء</a>
                <a href="{{ route('whatsapp.index') }}" wire:navigate class="wg-btn">✦ أتمتة واتساب</a>
                <a href="{{ route('backups.index') }}" wire:navigate class="wg-btn">▣ إنشاء نسخة احتياطية</a>
                <a href="{{ route('gym.settings') }}" wire:navigate class="wg-btn">⚙ إعدادات النظام</a>
            </div></div>
            <div class="wg-card wg-card-pad"><h2 class="wg-section-title">سياسة آمنة</h2><p class="wg-muted" style="font-size:10px;line-height:1.9;margin:10px 0 0">تعطيل الموظف أو الفرع يحافظ على السجلات التاريخية بدل حذفها. العمليات الحساسة تظهر في سجل التدقيق.</p></div>
        </aside>
    </div>
</div>
