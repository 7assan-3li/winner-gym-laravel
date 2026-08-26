<div class="wg-attendance-page" dir="rtl">
    <div class="wg-att-layout">
        <section class="wg-att-main">
            <div class="wg-att-scan-card">
                <div class="wg-att-card-title">
                    <h2>تسجيل حضور عضو</h2>
                    <p>ابحث عن العضو بالاسم أو الهاتف أو كود العضوية أو امسح الكود</p>
                </div>

                @if(session('success'))
                    <div class="wg-att-flash wg-att-flash-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="wg-att-flash wg-att-flash-error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0z"/></svg>
                        <div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                    </div>
                @endif

                <form wire:submit="record" class="wg-att-scan-form" data-attendance-form>
                    <div class="wg-att-scan-input-wrap">
                        <button type="button" class="wg-att-camera-inline" onclick="window.wgAttendanceOpenCamera()" title="فتح الكاميرا">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 7h3l2-2h6l2 2h3v12H4z"/><circle cx="12" cy="13" r="4"/></svg>
                        </button>
                        <input
                            id="attendance-identifier"
                            wire:model="identifier"
                            data-attendance-input
                            autocomplete="off"
                            autofocus
                            placeholder="امسح الكود أو اكتب كود العضوية / رقم الهاتف..."
                        >
                        <svg class="wg-att-scan-symbol" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8zM14 14h2v2h-2z"/></svg>
                    </div>
                    <div class="wg-att-or">أو استخدم أحد الخيارات التالية</div>
                    <div class="wg-att-scan-actions">
                        <button type="button" class="wg-att-camera-button" onclick="window.wgAttendanceOpenCamera()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8zM14 14h2v2h-2z"/></svg>
                            استخدام الكاميرا للمسح
                        </button>
                        <button type="submit" class="wg-att-record-button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                            تسجيل الحضور
                        </button>
                    </div>
                </form>
            </div>

            <div class="wg-att-ready-card">
                <div class="wg-att-fingerprint">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 11a3 3 0 0 1 3 3c0 2.8-.7 5.2-2.2 7M9.5 20.3c1-2 1.5-4.1 1.5-6.3a1 1 0 1 1 2 0c0 2.4-.5 4.7-1.5 6.8M7 19c1.3-2.6 2-5.4 2-8a3 3 0 0 1 6 0c0 3.5-.8 6.8-2.5 9.7M5 16c.7-1.7 1-3.4 1-5a6 6 0 0 1 12 0c0 2.8-.4 5.5-1.3 8M4.2 12A7.8 7.8 0 0 1 12 4.2 7.8 7.8 0 0 1 19.8 12"/></svg>
                </div>
                <strong>جاهز لتسجيل الحضور</strong>
                <span>النظام يتحقق تلقائيًا من حالة العضو والاشتراك والأقساط والفترة وساعات الدخول</span>
            </div>

            <div class="wg-att-table-card">
                <div class="wg-att-table-head">
                    <div class="wg-att-table-title">
                        <h3>سجل الحضور</h3>
                        <span>إجمالي {{ number_format($rows->total()) }} تسجيل</span>
                    </div>
                    <div class="wg-att-table-filters">
                        <input type="date" wire:model.live="date" class="wg-att-date-input">
                        <select wire:model.live="periodFilter">
                            <option value="all">كل الفترات</option>
                            <option value="men">فترة الرجال</option>
                            <option value="women">فترة النساء</option>
                        </select>
                        <select wire:model.live="methodFilter">
                            <option value="all">كل طرق التسجيل</option>
                            <option value="membership_code">كود العضوية</option>
                            <option value="phone">الهاتف</option>
                            <option value="barcode">Barcode</option>
                            <option value="qr">QR</option>
                            <option value="name">الاسم</option>
                        </select>
                        <a class="wg-att-export" href="{{ route('reports.pdf', ['from' => $date, 'to' => $date, 'gender' => 'all', 'currency' => 'YER']) }}" target="_blank">
                            تصدير
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3v12M8 11l4 4 4-4M4 19h16"/></svg>
                        </a>
                    </div>
                </div>

                <div class="wg-att-table-scroll">
                    <table class="wg-att-table">
                        <thead>
                            <tr>
                                <th>الوقت</th>
                                <th>اسم العضو</th>
                                <th>كود العضوية</th>
                                <th>طريقة التسجيل</th>
                                <th>الفترة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $attendance)
                                @php
                                    $methodLabel = match($attendance->method) {
                                        'phone' => 'الهاتف',
                                        'name' => 'الاسم',
                                        'barcode' => 'Barcode',
                                        'qr' => 'QR',
                                        default => 'كود العضوية',
                                    };
                                    $isSelected = $selected?->id === $attendance->id;
                                @endphp
                                <tr class="{{ $isSelected ? 'is-selected' : '' }}">
                                    <td class="wg-att-time">{{ $attendance->entered_at->timezone('Asia/Aden')->format('h:i') }} <small>{{ $attendance->entered_at->timezone('Asia/Aden')->format('A') === 'AM' ? 'ص' : 'م' }}</small></td>
                                    <td>
                                        <button type="button" class="wg-att-member-button" wire:click="selectAttendance({{ $attendance->id }})">
                                            <span class="wg-att-row-avatar">{{ mb_substr($attendance->member->full_name, 0, 1) }}</span>
                                            <span>{{ $attendance->member->full_name }}</span>
                                        </button>
                                    </td>
                                    <td class="wg-att-code">{{ $attendance->member->membership_code }}</td>
                                    <td>{{ $methodLabel }}</td>
                                    <td>{{ $attendance->member->assigned_period === 'women' ? 'النساء' : 'الرجال' }}</td>
                                    <td><span class="wg-att-status">حضور</span></td>
                                    <td>
                                        <button type="button" class="wg-att-row-action" wire:click="selectAttendance({{ $attendance->id }})" title="عرض التفاصيل">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="wg-att-empty">لا توجد سجلات حضور مطابقة لهذا اليوم.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="wg-att-table-footer">
                    <div>عرض {{ $rows->firstItem() ?? 0 }} - {{ $rows->lastItem() ?? 0 }} من {{ number_format($rows->total()) }} تسجيل</div>
                    <div class="wg-att-pagination" dir="ltr">
                        <button wire:click="previousPage" @disabled($rows->onFirstPage())>‹</button>
                        @php
                            $startPage = max(1, $rows->currentPage() - 2);
                            $endPage = min($rows->lastPage(), $startPage + 4);
                            $startPage = max(1, $endPage - 4);
                        @endphp
                        @for($page = $startPage; $page <= $endPage; $page++)
                            <button wire:click="gotoPage({{ $page }})" class="{{ $rows->currentPage() === $page ? 'is-active' : '' }}">{{ $page }}</button>
                        @endfor
                        <button wire:click="nextPage" @disabled(!$rows->hasMorePages())>›</button>
                    </div>
                </div>
            </div>
        </section>

        <aside class="wg-att-aside">
            <div class="wg-att-side-card wg-att-member-card">
                <h3>معلومات العضو</h3>
                @if($selectedMember)
                    <div class="wg-att-member-head">
                        <div class="wg-att-member-avatar">{{ mb_substr($selectedMember->full_name, 0, 1) }}</div>
                        <div>
                            <strong>{{ $selectedMember->full_name }}</strong>
                            <span>{{ $selectedMember->membership_code }}</span>
                            <em class="{{ $selectedMember->status === 'active' ? 'is-green' : 'is-orange' }}">{{ $selectedMember->status === 'active' ? 'نشط' : 'غير نشط' }}</em>
                        </div>
                    </div>
                    <div class="wg-att-member-info-list">
                        <div><span>الباقة</span><strong>{{ $selectedSubscription?->package_name_snapshot ?? '—' }}</strong></div>
                        <div><span>تاريخ الانتهاء</span><strong>{{ $selectedSubscription?->end_date?->format('Y-m-d') ?? '—' }}</strong></div>
                        <div><span>المتبقي</span><strong>{{ $selectedRemainingDays !== null ? $selectedRemainingDays.' يوم' : '—' }}</strong></div>
                        <div><span>الفترة</span><strong>{{ $selectedMember->assigned_period === 'women' ? 'فترة النساء' : 'فترة الرجال' }}</strong></div>
                    </div>
                @else
                    <div class="wg-att-side-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/></svg>
                        <span>سجّل حضور عضو ليظهر ملخصه هنا.</span>
                    </div>
                @endif
            </div>

            <div class="wg-att-success-card {{ $selected ? '' : 'is-muted' }}">
                <div class="wg-att-success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <div>
                    <strong>{{ session('success') ? 'تم تسجيل الحضور بنجاح' : ($selected ? 'آخر حضور مسجل' : 'في انتظار تسجيل حضور') }}</strong>
                    <span>
                        @if($selected)
                            {{ $selected->attendance_date->format('Y-m-d') }} · {{ $selected->entered_at->timezone('Asia/Aden')->format('h:i') }} {{ $selected->entered_at->timezone('Asia/Aden')->format('A') === 'AM' ? 'ص' : 'م' }}
                        @else
                            سيظهر وقت التسجيل هنا
                        @endif
                    </span>
                </div>
                <button type="button" wire:click="clearSelected">تسجيل عضو آخر</button>
            </div>

            <div class="wg-att-side-card wg-att-actions-card">
                <h3>إجراءات سريعة</h3>
                <a href="{{ route('members.index') }}" wire:navigate>
                    <span>عرض سجل الأعضاء</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="3"/><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/></svg>
                </a>
                <a href="{{ route('subscriptions.index') }}" wire:navigate>
                    <span>عرض الاشتراكات</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 5h16v15H4zM8 3v4M16 3v4M4 10h16"/></svg>
                </a>
                <button type="button" wire:click="clearSelected">
                    <span>إعادة البحث</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                </button>
                <a href="{{ route('reports.index') }}" wire:navigate>
                    <span>تقارير الحضور</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                </a>
            </div>

            <div class="wg-att-side-card wg-att-stats-card">
                <h3>إحصائيات اليوم</h3>
                <div class="wg-att-mini-stats">
                    <div><strong>{{ number_format($stats['total']) }}</strong><span>إجمالي الحضور</span><i class="is-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/></svg></i></div>
                    <div><strong>{{ number_format($stats['men']) }}</strong><span>فترة الرجال</span><i class="is-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg></i></div>
                    <div><strong>{{ number_format($stats['women']) }}</strong><span>فترة النساء</span><i class="is-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="9" r="4"/><path d="M12 13v8M9 18h6"/></svg></i></div>
                    <div><strong>{{ number_format($stats['rejected']) }}</strong><span>محاولات مرفوضة</span><i class="is-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 9v4M12 17h.01M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0z"/></svg></i></div>
                </div>
            </div>
        </aside>
    </div>

    <div id="wgAttendanceCamera" class="wg-att-camera-modal" aria-hidden="true">
        <div class="wg-att-camera-dialog">
            <div class="wg-att-camera-head">
                <strong>مسح كود العضوية</strong>
                <button type="button" onclick="window.wgAttendanceCloseCamera()">×</button>
            </div>
            <video id="wgAttendanceVideo" playsinline muted></video>
            <p id="wgAttendanceCameraMessage">وجّه الكاميرا إلى QR أو Barcode الخاص بالعضو.</p>
        </div>
    </div>
</div>

@script
<script>
(() => {
    let stream = null;
    let running = false;

    const closeCamera = () => {
        running = false;
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        const modal = document.getElementById('wgAttendanceCamera');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }
    };

    window.wgAttendanceCloseCamera = closeCamera;

    window.wgAttendanceOpenCamera = async () => {
        const modal = document.getElementById('wgAttendanceCamera');
        const video = document.getElementById('wgAttendanceVideo');
        const message = document.getElementById('wgAttendanceCameraMessage');
        if (!modal || !video) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        if (!navigator.mediaDevices?.getUserMedia) {
            message.textContent = 'الكاميرا غير متاحة في هذا المتصفح. استخدم قارئ الباركود أو اكتب الكود يدويًا.';
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
            video.srcObject = stream;
            await video.play();
            running = true;

            if (!('BarcodeDetector' in window)) {
                message.textContent = 'الكاميرا تعمل، لكن المسح التلقائي غير مدعوم هنا. استخدم قارئ باركود USB أو اكتب الكود يدويًا.';
                return;
            }

            const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8'] });
            message.textContent = 'وجّه الكاميرا إلى الكود...';

            const scan = async () => {
                if (!running) return;
                try {
                    const codes = await detector.detect(video);
                    if (codes.length) {
                        const value = codes[0].rawValue;
                        const input = document.querySelector('[data-attendance-input]');
                        const form = document.querySelector('[data-attendance-form]');
                        if (input && form) {
                            input.value = value;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            closeCamera();
                            setTimeout(() => form.requestSubmit(), 80);
                            return;
                        }
                    }
                } catch (e) {}
                requestAnimationFrame(scan);
            };

            scan();
        } catch (error) {
            message.textContent = 'تعذر فتح الكاميرا. تأكد من منح صلاحية الكاميرا أو استخدم الإدخال اليدوي.';
        }
    };

    Livewire.on('attendance-recorded', () => {
        setTimeout(() => document.querySelector('[data-attendance-input]')?.focus(), 120);
    });
    Livewire.on('attendance-focus', () => {
        setTimeout(() => document.querySelector('[data-attendance-input]')?.focus(), 120);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeCamera();
    });
})();
</script>
@endscript
