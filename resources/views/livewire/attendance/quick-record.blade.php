<div
    class="wg-quick-attendance"
    x-data="{ open: false }"
    x-on:wg-open-quick-attendance.window="open=true; $nextTick(() => document.querySelector('[data-wg-quick-attendance-input]')?.focus())"
    x-on:quick-attendance-recorded.window="open=true; $nextTick(() => document.querySelector('[data-wg-quick-attendance-input]')?.focus())"
    x-on:keydown.escape.window="if(open){ window.wgQuickAttendanceCloseCamera?.(); open=false }"
>
    <div class="wg-quick-attendance-backdrop" x-cloak x-show="open" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="wg-quick-attendance-title">
        <div class="wg-quick-attendance-dialog" x-on:click.outside="window.wgQuickAttendanceCloseCamera?.(); open=false">
            <div class="wg-quick-attendance-head">
                <div class="wg-quick-attendance-title">
                    <span class="wg-quick-attendance-mark">
                        <svg viewBox="0 0 24 24"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8zM14 14h2v2h-2z"/></svg>
                    </span>
                    <div><h2 id="wg-quick-attendance-title">تسجيل الحضور السريع</h2><p>سجّل حضور العضو دون مغادرة الصفحة الحالية</p></div>
                </div>
                <button type="button" class="wg-quick-attendance-close" wire:click="clear" x-on:click="window.wgQuickAttendanceCloseCamera?.(); open=false" aria-label="إغلاق">×</button>
            </div>

            <form wire:submit="record" class="wg-quick-attendance-form" data-wg-quick-attendance-form>
                @if($result)
                    <div class="wg-quick-attendance-success">
                        <span>✓</span>
                        <div>
                            <strong>تم تسجيل حضور {{ $result['member_name'] }} بنجاح</strong>
                            <small>{{ $result['membership_code'] }} · {{ $result['package'] }} · {{ $result['entered_at'] }}</small>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="wg-quick-attendance-errors">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <label for="wg-quick-attendance-identifier">كود العضوية أو رقم الهاتف أو الباركود</label>
                <div class="wg-quick-attendance-input-wrap">
                    <button type="button" onclick="window.wgQuickAttendanceOpenCamera?.()" title="المسح بالكاميرا" aria-label="المسح بالكاميرا">
                        <svg viewBox="0 0 24 24"><path d="M4 7h3l2-2h6l2 2h3v12H4z"/><circle cx="12" cy="13" r="4"/></svg>
                    </button>
                    <input
                        id="wg-quick-attendance-identifier"
                        wire:model="identifier"
                        data-wg-quick-attendance-input
                        autocomplete="off"
                        placeholder="امسح الكود أو اكتب بيانات العضو..."
                    >
                    <svg class="wg-quick-attendance-scan" viewBox="0 0 24 24"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8zM14 14h2v2h-2z"/></svg>
                </div>

                <div class="wg-quick-attendance-actions">
                    <button type="button" class="is-camera" onclick="window.wgQuickAttendanceOpenCamera?.()">
                        <svg viewBox="0 0 24 24"><path d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"/></svg>
                        مسح بالكاميرا
                    </button>
                    <button type="submit" class="is-record" wire:loading.attr="disabled" wire:target="record">
                        <span wire:loading.remove wire:target="record">تسجيل الحضور</span>
                        <span wire:loading wire:target="record">جارٍ التحقق...</span>
                    </button>
                </div>
            </form>

            <div id="wgQuickAttendanceCamera" class="wg-quick-attendance-camera" aria-hidden="true">
                <div class="wg-quick-attendance-camera-head"><strong>وجّه الكاميرا إلى الكود</strong><button type="button" onclick="window.wgQuickAttendanceCloseCamera?.()">×</button></div>
                <video id="wgQuickAttendanceVideo" playsinline muted></video>
                <p id="wgQuickAttendanceCameraMessage">يتم البحث عن QR أو Barcode تلقائيًا.</p>
            </div>
        </div>
    </div>

    @script
    <script>
        (() => {
            let quickAttendanceStream = null;
            let quickAttendanceRunning = false;

            window.wgQuickAttendanceCloseCamera = () => {
                quickAttendanceRunning = false;
                if (quickAttendanceStream) {
                    quickAttendanceStream.getTracks().forEach(track => track.stop());
                    quickAttendanceStream = null;
                }
                const camera = document.getElementById('wgQuickAttendanceCamera');
                if (camera) {
                    camera.classList.remove('is-open');
                    camera.setAttribute('aria-hidden', 'true');
                }
            };

            window.wgQuickAttendanceOpenCamera = async () => {
                const camera = document.getElementById('wgQuickAttendanceCamera');
                const video = document.getElementById('wgQuickAttendanceVideo');
                const message = document.getElementById('wgQuickAttendanceCameraMessage');
                if (!camera || !video || !message) return;

                camera.classList.add('is-open');
                camera.setAttribute('aria-hidden', 'false');

                if (!navigator.mediaDevices?.getUserMedia) {
                    message.textContent = 'الكاميرا غير متاحة في هذا المتصفح. يمكنك كتابة الكود يدويًا.';
                    return;
                }

                try {
                    quickAttendanceStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                    video.srcObject = quickAttendanceStream;
                    await video.play();
                    quickAttendanceRunning = true;

                    if (!('BarcodeDetector' in window)) {
                        message.textContent = 'الكاميرا تعمل، لكن المسح التلقائي غير مدعوم. استخدم قارئ الباركود أو الإدخال اليدوي.';
                        return;
                    }

                    const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8'] });
                    message.textContent = 'وجّه الكاميرا إلى الكود...';

                    const scan = async () => {
                        if (!quickAttendanceRunning) return;
                        try {
                            const codes = await detector.detect(video);
                            if (codes.length) {
                                const input = document.querySelector('[data-wg-quick-attendance-input]');
                                const form = document.querySelector('[data-wg-quick-attendance-form]');
                                if (input && form) {
                                    input.value = codes[0].rawValue;
                                    input.dispatchEvent(new Event('input', { bubbles: true }));
                                    window.wgQuickAttendanceCloseCamera();
                                    setTimeout(() => form.requestSubmit(), 100);
                                    return;
                                }
                            }
                        } catch (error) {}
                        requestAnimationFrame(scan);
                    };
                    scan();
                } catch (error) {
                    message.textContent = 'تعذر فتح الكاميرا. تأكد من منح صلاحية الكاميرا أو استخدم الإدخال اليدوي.';
                }
            };
        })();
    </script>
    @endscript
</div>