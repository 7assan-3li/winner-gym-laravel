@php
    $settings = $reportSettings ?? [
        'footer_mode' => app(\App\Services\SettingsService::class)->get('report.footer_mode', 'structured'),
        'footer_image' => app(\App\Services\SettingsService::class)->get('report.footer_image_path'),
        'footer_text' => app(\App\Services\SettingsService::class)->get('report.footer_text', 'هذا التقرير معتمد ومستخرج آلياً من نظام WINNER GYM'),
        'show_signatures' => (bool) app(\App\Services\SettingsService::class)->get('report.show_signatures', true),
        'show_print_meta' => (bool) app(\App\Services\SettingsService::class)->get('report.show_print_meta', true),
    ];

    $isPdf = $isPdf ?? false;
    $footerMode = $settings['footer_mode'] ?? 'structured';

    $resolveImg = function(?string $path) use ($isPdf) {
        if (! $path) return null;
        $fullPath = storage_path('app/public/' . $path);
        if ($isPdf) {
            return file_exists($fullPath) ? $fullPath : null;
        }
        return asset('storage/' . $path);
    };

    $footerImgSrc = $resolveImg($settings['footer_image'] ?? null);
    $showSignatures = isset($showSignatures) ? $showSignatures : ($settings['show_signatures'] ?? true);
    $showPrintMeta = isset($showPrintMeta) ? $showPrintMeta : ($settings['show_print_meta'] ?? true);
@endphp

<footer class="wg-report-footer" style="width:100%;margin-top:20px;direction:rtl;font-family:inherit;">
    @if($footerMode === 'custom_image' && $footerImgSrc)
        {{-- النمط الثاني: صورة تذييل سفلية جاهزة --}}
        <div class="wg-report-footer-banner" style="width:100%;text-align:center;margin-top:10px;">
            <img src="{{ $footerImgSrc }}" alt="تذييل التقرير" style="width:100%;max-height:120px;object-fit:contain;display:block;margin:0 auto;">
        </div>
    @else
        {{-- النمط الأول: كليشة تذييل نصية رسمية --}}
        @if($showSignatures)
            <div class="wg-report-signatures" style="display:table;width:100%;margin-top:24px;padding-top:12px;border-top:1px dashed #cbd5e1;page-break-inside:avoid;">
                <div style="display:table-cell;width:48%;vertical-align:top;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fcfdfd;">
                    <div style="font-size:11px;font-weight:800;color:#0f172a;margin-bottom:6px;">الموظف المسلّم / الكاشير:</div>
                    <div style="font-size:10px;color:#334155;margin-bottom:18px;">الاسم: <strong>{{ $signEmployee ?? (auth()->user()?->name ?? '........................') }}</strong></div>
                    <div style="font-size:10px;color:#64748b;">التوقيع: ........................................</div>
                </div>

                <div style="display:table-cell;width:4%;"></div>

                <div style="display:table-cell;width:48%;vertical-align:top;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fcfdfd;">
                    <div style="font-size:11px;font-weight:800;color:#0f172a;margin-bottom:6px;">المستلم / إدارة الصالة:</div>
                    <div style="font-size:10px;color:#334155;margin-bottom:18px;">الاسم: <strong>{{ $signManager ?? '........................' }}</strong></div>
                    <div style="font-size:10px;color:#64748b;">التوقيع والختم: ........................................</div>
                </div>
            </div>
        @endif

        @if(!empty($settings['footer_text']))
            <div style="margin-top:16px;text-align:center;font-size:10px;color:#64748b;font-weight:600;">
                {{ $settings['footer_text'] }}
            </div>
        @endif

        @if($showPrintMeta)
            <div style="display:table;width:100%;margin-top:10px;padding-top:6px;border-top:1px solid #f1f5f9;font-size:9px;color:#94a3b8;">
                <div style="display:table-cell;text-align:right;">
                    وقت الطباعة: {{ now('Asia/Aden')->format('Y-m-d h:i A') }} — مستخرج التقرير: {{ auth()->user()?->name ?? 'النظام' }}
                </div>
                <div style="display:table-cell;text-align:left;direction:ltr;">
                    WINNER GYM System
                </div>
            </div>
        @endif
    @endif
</footer>
