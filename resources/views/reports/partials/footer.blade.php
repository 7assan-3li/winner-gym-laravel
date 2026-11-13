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
        if (! file_exists($fullPath)) {
            $fullPath = public_path('storage/' . $path);
        }
        if (! file_exists($fullPath)) {
            return null;
        }

        if ($isPdf) {
            $mime = @mime_content_type($fullPath) ?: 'image/png';
            $data = @file_get_contents($fullPath);
            return $data ? ('data:' . $mime . ';base64,' . base64_encode($data)) : null;
        }

        return asset('storage/' . $path);
    };

    $footerImgSrc = $resolveImg($settings['footer_image'] ?? null);
    $showSignatures = isset($showSignatures) ? $showSignatures : ($settings['show_signatures'] ?? true);
    $showPrintMeta = isset($showPrintMeta) ? $showPrintMeta : ($settings['show_print_meta'] ?? true);
@endphp

<div class="wg-report-footer" style="width:100%;margin-top:20px;direction:rtl;">
    @if($footerMode === 'custom_image' && $footerImgSrc)
        {{-- النمط الثاني: صورة تذييل سفلية جاهزة --}}
        <div style="width:100%;text-align:center;margin-top:10px;">
            <img src="{{ $footerImgSrc }}" alt="تذييل التقرير" style="width:100%;max-height:120px;display:block;margin:0 auto;">
        </div>
    @else
        {{-- النمط الأول: كليشة تذييل نصية رسمية متوافقة مع المتصفح و mPDF --}}
        @if($showSignatures)
            <table style="width:100%;border-collapse:collapse;margin-top:20px;border-top:1px dashed #cbd5e1;padding-top:10px;">
                <tr>
                    <td style="width:48%;vertical-align:top;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fcfdfd;">
                        <div style="font-size:11px;font-weight:bold;color:#0f172a;margin-bottom:6px;">الموظف المسلّم / الكاشير:</div>
                        <div style="font-size:10px;color:#334155;margin-bottom:16px;">الاسم: <strong>{{ $signEmployee ?? (auth()->user()?->name ?? '........................') }}</strong></div>
                        <div style="font-size:10px;color:#64748b;">التوقيع: ........................................</div>
                    </td>

                    <td style="width:4%;border:none;"></td>

                    <td style="width:48%;vertical-align:top;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fcfdfd;">
                        <div style="font-size:11px;font-weight:bold;color:#0f172a;margin-bottom:6px;">المستلم / إدارة الصالة:</div>
                        <div style="font-size:10px;color:#334155;margin-bottom:16px;">الاسم: <strong>{{ $signManager ?? '........................' }}</strong></div>
                        <div style="font-size:10px;color:#64748b;">التوقيع والختم: ........................................</div>
                    </td>
                </tr>
            </table>
        @endif

        @if(!empty($settings['footer_text']))
            <div style="margin-top:14px;text-align:center;font-size:10px;color:#64748b;font-weight:bold;">
                {{ $settings['footer_text'] }}
            </div>
        @endif

        @if($showPrintMeta)
            <table style="width:100%;border-collapse:collapse;margin-top:10px;border-top:1px solid #f1f5f9;padding-top:4px;font-size:9px;color:#94a3b8;">
                <tr>
                    <td style="text-align:right;border:none;padding:2px 0;">
                        وقت الطباعة: {{ now('Asia/Aden')->format('Y-m-d h:i A') }} — مستخرج التقرير: {{ auth()->user()?->name ?? 'النظام' }}
                    </td>
                    <td style="text-align:left;direction:ltr;border:none;padding:2px 0;">
                        WINNER GYM System
                    </td>
                </tr>
            </table>
        @endif
    @endif
</div>
